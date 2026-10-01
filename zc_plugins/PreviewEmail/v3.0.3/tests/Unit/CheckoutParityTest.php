<?php
/**
 * Order Confirmation preview stays a faithful mirror of the live order class.
 *
 * The checkout preview cannot call order::send_order_email() for a stored order
 * (that method builds products_ordered_html only at checkout, from the live cart
 * and session, and then sends), so shared/emails/core/checkout.php rebuilds the
 * same markup. This test guards that rebuild against drift: it reads THIS store's
 * includes/classes/order.php, pulls out core's own product-row, attribute and
 * order-totals expressions, evaluates them with sample data, evaluates our
 * builder's matching expressions with the same data, and asserts the output is
 * byte-identical. If a Zen Cart release changes the confirmation email, this
 * fails and checkout.php must be brought back in line.
 *
 * It reads no database and sends no mail. If the store's order class can't be
 * located (e.g. tests run outside a cart), it skips rather than fails.
 *
 * @package  PreviewEmail
 * @license  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CheckoutParityTest extends TestCase
{
    /** @var object */
    private static $currencies;
    private static string $coreSrc = '';
    private static string $ourSrc = '';

    public static function setUpBeforeClass(): void
    {
        if (!defined('TEXT_ONETIME_CHARGES_EMAIL')) {
            define('TEXT_ONETIME_CHARGES_EMAIL', 'One time charges - ');
        }
        if (!function_exists('Tests\\Unit\\zen_decode_specialchars') && !function_exists('zen_decode_specialchars')) {
            // Core defines this; provide a passthrough when running outside the cart.
            eval('function zen_decode_specialchars($s) { return $s; }');
        }
        // One currency stub shared by both sides, so only the markup can differ.
        self::$currencies = new class {
            public function display_price($p, $t, $q = 1) { return '$' . number_format((float)$p * (int)$q, 2); }
            public function format($n, $a = false, $b = null, $c = null) { return '$' . number_format((float)$n, 2); }
        };

        // Our builder's source.
        $our = dirname(__DIR__, 2) . '/shared/emails/core/checkout.php';
        self::$ourSrc = is_file($our) ? (string)file_get_contents($our) : '';

        // This store's order class: from DIR_FS_CATALOG if defined, else the cart
        // root (the working directory when Zen Cart's plugin-test runner invokes
        // phpunit). Left empty -> the test skips.
        $candidates = [];
        if (defined('DIR_FS_CATALOG')) {
            $candidates[] = DIR_FS_CATALOG . 'includes/classes/order.php';
        }
        $candidates[] = getcwd() . '/includes/classes/order.php';
        foreach ($candidates as $c) {
            if ($c && is_file($c)) {
                self::$coreSrc = (string)file_get_contents($c);
                break;
            }
        }
    }

    private static function rhs(string $src, string $pattern, string $what): string
    {
        if (preg_match($pattern, $src, $m) !== 1) {
            throw new \RuntimeException("could not locate $what");
        }
        return trim($m[1]);
    }

    /** Evaluate an extracted concatenation expression with a given variable scope. */
    private function evalExpr(string $expr, array $scope): string
    {
        $currencies = self::$currencies;
        extract($scope, EXTR_SKIP);
        return eval('return ' . $expr . ';');
    }

    public function testPreviewProductAndTotalsMarkupMatchTheLiveOrderClass(): void
    {
        if (self::$ourSrc === '') {
            self::fail('shared/emails/core/checkout.php could not be read');
        }
        if (self::$coreSrc === '') {
            self::markTestSkipped('order class not found (running outside a Zen Cart cart)');
        }

        // --- core's own expressions, from this store's order.php ---
        $coreRow = self::rhs(self::$coreSrc, '~\$this->products_ordered_html\s*\.=(.*?);[ \t]*\r?\n~s', 'core products_ordered_html');
        $coreRow = str_replace(['$this->products[$i]', '$this->products_ordered_attributes'], ['$P', '$ATTR'], $coreRow);
        $coreAttr = self::rhs(self::$coreSrc, '~\$this->products_ordered_attributes\s*\.=\s*(.*?products_options_name.*?);[ \t]*\r?\n~s', 'core attribute line');
        $coreAttr = str_replace(
            ["\$attributes_values->fields['products_options_name']", "\$this->products[\$i]['attributes'][\$j]['value']"],
            ['$OPTNAME', '$AVAL'],
            $coreAttr
        );
        $coreTotHead = self::rhs(self::$coreSrc, '~\$html_ot\s*=\s*(.*?);[ \t]*\r?\n~s', 'core $html_ot header');
        $coreTotRow = self::rhs(self::$coreSrc, '~\$html_ot\s*\.=\s*(.*?);[ \t]*\r?\n~s', 'core $html_ot row');
        $coreTotRow = str_replace('$order_totals[$i]', '$T', $coreTotRow);

        // --- our matching expressions, from checkout.php's real-order branch ---
        $ourRow = self::rhs(self::$ourSrc, '~\$productsHtml\s*\.=\s*\r?\n(.*?);[ \t]*\r?\n~s', 'our productsHtml row');
        $ourTotHead = self::rhs(self::$ourSrc, '~\$totalsHtml\s*=\s*(.*?);[ \t]*\r?\n~s', 'our totalsHtml header');
        $ourTotRow = self::rhs(self::$ourSrc, '~\$totalsHtml\s*\.=\s*([^;]*\(\$t\[.text.\]\)[^;]*);~', 'our totalsHtml row');

        // Two sample products: one with an attribute, one with a one-time charge.
        $products = [
            ['qty' => 2, 'name' => 'Blue Widget', 'model' => 'WID-1', 'final_price' => 9.99, 'tax' => 0.0, 'onetime_charges' => 0.0,
             'attributes' => [['option' => 'Size', 'value' => 'Large']]],
            ['qty' => 1, 'name' => 'Setup Service', 'model' => '', 'final_price' => 50.0, 'tax' => 0.0, 'onetime_charges' => 25.0,
             'attributes' => []],
        ];

        foreach ($products as $n => $prod) {
            // Build the attribute string core's way, feed the same string to both rows.
            $ATTR = '';
            foreach ($prod['attributes'] as $a) {
                $ATTR .= $this->evalExpr($coreAttr, ['OPTNAME' => $a['option'], 'AVAL' => (string)$a['value']]);
            }
            $core = $this->evalExpr($coreRow, ['P' => $prod, 'ATTR' => $ATTR]);
            $ours = $this->evalExpr($ourRow, [
                'p' => $prod,
                'model' => (string)$prod['model'],
                'onetime' => (float)$prod['onetime_charges'],
                'attributes' => $ATTR,
            ]);
            self::assertSame($core, $ours, "product row $n does not match order::send_order_email()");
        }

        // Totals: the header literal and each data row.
        self::assertSame($coreTotHead, $ourTotHead, 'order-totals header does not match order::send_order_email()');
        foreach ([['title' => 'Sub-Total:', 'text' => '$69.98'], ['title' => 'Total:', 'text' => '$69.98']] as $n => $tot) {
            $core = $this->evalExpr($coreTotRow, ['T' => $tot]);
            $ours = $this->evalExpr($ourTotRow, ['t' => $tot]);
            self::assertSame($core, $ours, "totals row $n does not match order::send_order_email()");
        }
    }
}
