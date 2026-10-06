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
 * It also powers the opt-in "exact send-path" preview of the Order
 * Confirmation: when capture is armed (preview_email_capture_is_armed()), it
 * grabs the finished order email at NOTIFY_ORDER_INVOICE_CONTENT_READY_TO_SEND
 * and switches every send off, so running order::send_order_email() produces a
 * preview rather than real mail. When capture is not armed -- i.e. every real
 * checkout -- these handlers return immediately and change nothing.
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
        $this->attach($this, [
            'NOTIFY_EMAIL_DETERMINING_EMAIL_FORMAT',
            // Exact send-path capture (inert unless armed):
            'NOTIFY_ORDER_SEND_LOW_STOCK_EMAILS',
            'NOTIFY_ORDER_INVOICE_CONTENT_READY_TO_SEND',
            'NOTIFY_ORDER_INVOICE_CONTENT_FOR_ADDITIONAL_EMAILS',
        ]);
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

    /**
     * Suppress the low-stock notice during an exact-capture preview. (A loaded
     * order carries no low-stock text, so this is belt and suspenders.)
     */
    public function updateNotifyOrderSendLowStockEmails(&$class, $eventID, $p1 = null)
    {
        if (preview_email_capture_is_armed()) {
            $class->send_low_stock_emails = false;
        }
    }

    /**
     * Capture the finished customer email and stop its send.
     *
     * @param mixed  $data                 param1 (by value): array of context
     * @param mixed  $email_order          param2 (by ref): finished text email
     * @param mixed  $html_msg             param3 (by ref): finished HTML block array
     * @param mixed  $send_customer_email  param4 (by ref): set false to not send
     */
    public function updateNotifyOrderInvoiceContentReadyToSend(&$class, $eventID, $data, &$email_order, &$html_msg, &$send_customer_email, &$reply_to_name = '', &$reply_to_address = '')
    {
        if (!preview_email_capture_is_armed()) {
            return;
        }
        preview_email_capture_store((string)$email_order, (array)$html_msg);
        $send_customer_email = false;
    }

    /**
     * Stop the store-copy ("extra") email during an exact-capture preview.
     *
     * @param mixed $zf_insert_id       param1 (by value)
     * @param mixed $email_order        param2 (by ref)
     * @param mixed $html_msg           param3 (by ref)
     * @param mixed $sendExtraOrderEmail param4 (by ref): set false to not send
     */
    public function updateNotifyOrderInvoiceContentForAdditionalEmails(&$class, $eventID, $zf_insert_id, &$email_order, &$html_msg, &$sendExtraOrderEmail)
    {
        if (preview_email_capture_is_armed()) {
            $sendExtraOrderEmail = false;
        }
    }
}
