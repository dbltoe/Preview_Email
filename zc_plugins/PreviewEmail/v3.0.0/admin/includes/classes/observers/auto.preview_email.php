<?php
/**
 * Preview Email -- admin observer that forces the format of a test send.
 *
 * zen_mail() decides between HTML and plain text by looking the recipient
 * up in the customers table; an address it does not know gets plain text.
 * That is right for real mail and useless for a test the admin has just
 * asked to see "as HTML". Core fires NOTIFY_EMAIL_DETERMINING_EMAIL_FORMAT
 * with the decided format as a by-reference parameter, on every release
 * from v1.5.8 to v3.0.0, so while a test send is in progress this observer
 * overwrites it with what was asked for. At any other time it does nothing.
 *
 * Auto-loaded by admin/includes/init_includes/init_observers.php on every
 * supported release. The filename and the class name must stay in step:
 * Zen Cart derives the class as 'zcObserver' . camelize('preview_email').
 *
 * @package  PreviewEmail
 * @license  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

require_once __DIR__ . '/../../../../shared/functions.php';

class zcObserverPreviewEmail extends base
{
    public function __construct()
    {
        $this->attach($this, ['NOTIFY_EMAIL_DETERMINING_EMAIL_FORMAT']);
    }

    /**
     * @param mixed $class
     * @param string $eventID
     * @param mixed $toAddress   the recipient (param1, by value)
     * @param mixed $format      the decided format (param2, by reference)
     * @param mixed $module      the email module name (param3)
     */
    public function update(&$class, $eventID, $toAddress, &$format, &$module, &$p4, &$p5, &$p6, &$p7, &$p8, &$p9)
    {
        if ($eventID !== 'NOTIFY_EMAIL_DETERMINING_EMAIL_FORMAT') {
            return;
        }
        $forced = preview_email_forced_format();
        if ($forced !== '') {
            $format = $forced;
        }
    }
}
