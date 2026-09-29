<?php
/**
 * Preview Email -- plugin-local unit tests for Zen Cart 3.0.0's test runner.
 *
 * Zen Cart 3.0.0 discovers zc_plugins/<Plugin>/<version>/tests/Unit/*Test.php
 * and runs them with its own phpunit. These cover the pure functions in
 * shared/functions.php with a throwaway template directory; nothing here
 * touches a database or sends mail.
 *
 * @package  PreviewEmail
 * @license  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PreviewEmailFunctionsTest extends TestCase
{
    private static string $dir = '';

    public static function setUpBeforeClass(): void
    {
        if (!defined('IS_ADMIN_FLAG')) {
            define('IS_ADMIN_FLAG', true);
        }
        self::$dir = str_replace('\\', '/', sys_get_temp_dir()) . '/preview_email_test_' . getmypid() . '/';
        @mkdir(self::$dir, 0o777, true);
        file_put_contents(self::$dir . 'email_template_checkout.html', '<html><body>$EMAIL_FIRST_NAME $ORDER_TOTALS $NOT_FILLED</body></html>');
        file_put_contents(self::$dir . 'email_template_default.html', '<html><body>$EMAIL_MESSAGE_HTML</body></html>');
        if (!defined('DIR_FS_EMAIL_TEMPLATES')) {
            define('DIR_FS_EMAIL_TEMPLATES', self::$dir);
        }
        if (!defined('DIR_FS_CATALOG')) {
            define('DIR_FS_CATALOG', self::$dir);
        }
        $_SESSION['languages_code'] = 'en';
        require_once __DIR__ . '/preview_email_test_stubs.php';
        require_once dirname(__DIR__, 2) . '/shared/functions.php';
    }

    public static function tearDownAfterClass(): void
    {
        foreach ((array)glob(self::$dir . '*') as $f) {
            @unlink($f);
        }
        @rmdir(self::$dir);
    }

    public function testTemplateResolutionStripsExtraAndFallsBackToDefault(): void
    {
        $t = preview_email_resolve_template('checkout_extra', 'checkout_process');
        $this->assertSame('checkout', $t['name']);
        $this->assertFalse($t['fallback']);

        $t = preview_email_resolve_template('mystery', 'nowhere');
        $this->assertSame('default', $t['name']);
        $this->assertTrue($t['fallback']);
    }

    public function testTemplateFilesAndPlaceholders(): void
    {
        $files = preview_email_template_files();
        $this->assertArrayHasKey('checkout', $files);
        $this->assertSame(['EMAIL_FIRST_NAME', 'NOT_FILLED', 'ORDER_TOTALS'], preview_email_template_placeholders($files['checkout']));
    }

    public function testUnfilledPlaceholdersIgnoreStylesheetAndLowerCase(): void
    {
        $html = '<style>a{b:"$IN_CSS"}</style><body>$A_ONE $5 $x $B_TWO $A_ONE</body>';
        $this->assertSame(['A_ONE', 'B_TWO'], preview_email_unfilled_placeholders($html));
        $this->assertSame([], preview_email_unfilled_placeholders('<p>clean</p>'));
    }

    public function testTextPartIsGeneratedFromHtmlWhenEmpty(): void
    {
        $message = ['text' => '', 'module' => 'checkout', 'block' => ['EMAIL_MESSAGE_HTML' => '<p>Hi <b>there</b></p><p>Two<br>Three</p>'], 'to_email' => 'x@example.com'];
        $text = preview_email_render_text($message);
        $this->assertStringContainsString("Hi there\n", $text);
        $this->assertStringContainsString("Two\nThree", $text);
        $this->assertSame(strip_tags($text), $text);
    }

    /**
     * Core's tag treatment is what it is: <strong> is stripped, a <div> is
     * protected and its closing tag loses the slash. The preview must show
     * what core sends, so this pins core's behavior rather than its intent.
     */
    public function testExplicitTextMirrorsCoresTagTreatment(): void
    {
        $message = ['text' => 'Keep <strong>x</strong>, drop <div>y</div>', 'module' => 'checkout', 'block' => [], 'to_email' => 'x@example.com'];
        $this->assertSame('Keep x, drop <div>y<div>', preview_email_render_text($message));
    }

    public function testConstFallback(): void
    {
        $this->assertSame('fb', preview_email_const('PREVIEW_EMAIL_TEST_UNDEFINED_CONSTANT', 'fb'));
    }

    public function testAvailability(): void
    {
        $this->assertTrue(preview_email_availability(['key' => 'a']));
        $this->assertSame('why', preview_email_availability(['available' => 'why']));
        $this->assertSame('called', preview_email_availability(['available' => static fn() => 'called']));
        $this->assertIsString(preview_email_availability(['available' => false]));
    }

    public function testForcedFormatFlag(): void
    {
        $this->assertSame('', preview_email_forced_format());
        $GLOBALS['preview_email_force_format'] = 'TEXT';
        $this->assertSame('TEXT', preview_email_forced_format());
        $GLOBALS['preview_email_force_format'] = 'nonsense';
        $this->assertSame('', preview_email_forced_format());
        unset($GLOBALS['preview_email_force_format']);
    }

    public function testDefinitionFileValidation(): void
    {
        $file = self::$dir . 'def.php';
        file_put_contents($file, '<?php return [["key" => "ok_one", "build" => "strtoupper"], ["key" => "BAD KEY", "build" => "strtoupper"], ["key" => "no_build"]];');
        $problems = [];
        $defs = preview_email_read_definition_file($file, 'test', 'Group', $problems);
        $this->assertCount(1, $defs);
        $this->assertSame('ok_one', $defs[0]['key']);
        $this->assertSame('Group', $defs[0]['group']);
        $this->assertCount(2, $problems);
        unlink($file);
    }
}
