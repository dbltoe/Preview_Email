<?php
/**
 * Preview Email -- the order status update sent from admin, and the store's
 * copy of it.
 *
 * Built from a recent order that has a status history, the way
 * zen_update_orders_history() builds the live one.
 *
 * @package  PreviewEmail
 * @license  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

$previewEmailGroupCore = preview_email_const('PREVIEW_EMAIL_GROUP_CORE', 'Zen Cart');

return [
    [
        'key' => 'order_status',
        'group' => $previewEmailGroupCore,
        'sort' => 15,
        'label' => preview_email_const('PREVIEW_EMAIL_LABEL_ORDER_STATUS', 'Order Status Update'),
        'describe' => preview_email_const('PREVIEW_EMAIL_DESC_ORDER_STATUS', 'Sent to the customer when you change an order\'s status with "notify customer" ticked.'),
        'module' => 'order_status',
        'page_base' => 'order_status',
        'build' => static function (array $def): array {
            global $db;
            preview_email_load_admin_language('orders');
            preview_email_load_catalog_language('email_extras');
            preview_email_load_catalog_language('english');

            $notes = [];
            $oID = preview_email_sample_order_id(true);
            $name = '';
            $email = '';
            $datePurchased = date('Y-m-d H:i:s');
            $comments = preview_email_const('PREVIEW_EMAIL_SAMPLE_STATUS_COMMENT', 'Your order has shipped. Tracking number: 1Z999AA10123456784.');
            $currentStatus = 0;
            $newStatus = 0;
            if ($oID > 0 && isset($db)) {
                $o = $db->Execute("SELECT customers_name, customers_email_address, date_purchased, orders_status FROM " . TABLE_ORDERS . " WHERE orders_id = " . (int)$oID . " LIMIT 1");
                if (!$o->EOF) {
                    $name = (string)$o->fields['customers_name'];
                    $email = (string)$o->fields['customers_email_address'];
                    $datePurchased = (string)$o->fields['date_purchased'];
                    $newStatus = (int)$o->fields['orders_status'];
                }
                if (defined('TABLE_ORDERS_STATUS_HISTORY')) {
                    $h = $db->Execute("SELECT orders_status_id, comments FROM " . TABLE_ORDERS_STATUS_HISTORY . " WHERE orders_id = " . (int)$oID . " ORDER BY orders_status_history_id DESC LIMIT 2");
                    if (!$h->EOF) {
                        $newStatus = (int)$h->fields['orders_status_id'];
                        if (trim((string)$h->fields['comments']) !== '') {
                            $comments = (string)$h->fields['comments'];
                        }
                        $h->MoveNext();
                        $currentStatus = $h->EOF ? $newStatus : (int)$h->fields['orders_status_id'];
                    }
                }
            }
            if ($name === '') {
                $customer = preview_email_sample_customer();
                $name = $customer['name'];
                $email = $customer['email'];
                $oID = 1001;
                $currentStatus = 1;
                $newStatus = 3;
                $notes[] = preview_email_const('PREVIEW_EMAIL_NOTE_NO_ORDERS', 'This store has no orders yet, so the order shown is invented.');
            }

            $newName = function_exists('zen_get_orders_status_name') ? (string)zen_get_orders_status_name($newStatus) : '';
            $oldName = function_exists('zen_get_orders_status_name') ? (string)zen_get_orders_status_name($currentStatus) : '';
            if ($newName === '') {
                $newName = 'Delivered';
            }
            if ($oldName === '') {
                $oldName = 'Processing';
            }
            if ($newStatus !== $currentStatus) {
                $statusText = preview_email_const('OSH_EMAIL_TEXT_STATUS_UPDATED', 'Your order has been updated to the following status:');
                $statusValue = sprintf(preview_email_const('OSH_EMAIL_TEXT_STATUS_CHANGE', 'from %s to %s'), $oldName, $newName);
            } else {
                $statusText = preview_email_const('OSH_EMAIL_TEXT_STATUS_NO_CHANGE', 'Your order is currently at this status:');
                $statusValue = sprintf(preview_email_const('OSH_EMAIL_TEXT_STATUS_LABEL', 'Status: %s'), $newName);
            }
            $invoiceUrl = zen_catalog_href_link(defined('FILENAME_CATALOG_ACCOUNT_HISTORY_INFO') ? FILENAME_CATALOG_ACCOUNT_HISTORY_INFO : 'account_history_info', 'order_id=' . $oID, 'SSL');
            $updateMessage = preview_email_const('EMAIL_ORDER_UPDATE_MESSAGE', '');

            $text = preview_email_const('EMAIL_SALUTATION', 'Dear') . ' ' . $name . ", \n\n"
                . preview_email_const('STORE_NAME') . ' ' . preview_email_const('OSH_EMAIL_TEXT_ORDER_NUMBER', 'Order Number:') . ' ' . $oID . "\n\n"
                . preview_email_const('OSH_EMAIL_TEXT_INVOICE_URL', 'Detailed Invoice:') . ' ' . str_replace('&amp;', '&', $invoiceUrl) . "\n\n"
                . preview_email_const('OSH_EMAIL_TEXT_DATE_ORDERED', 'Date Ordered:') . ' ' . zen_date_long($datePurchased) . "\n\n"
                . strip_tags($comments) . $statusText . $statusValue
                . preview_email_const('OSH_EMAIL_TEXT_STATUS_PLEASE_REPLY', 'Please reply to this email if you have any questions.')
                . ($updateMessage !== '' ? "\n\n" . $updateMessage . "\n\n" : '');

            $block = [
                'EMAIL_ORDER_UPDATE_MESSAGE' => $updateMessage,
                'EMAIL_SALUTATION' => preview_email_const('EMAIL_SALUTATION', 'Dear'),
                'EMAIL_CUSTOMERS_NAME' => $name,
                'EMAIL_TEXT_ORDER_NUMBER' => preview_email_const('OSH_EMAIL_TEXT_ORDER_NUMBER', 'Order Number:') . ' ' . $oID,
                'EMAIL_TEXT_INVOICE_URL' => '<a href="' . $invoiceUrl . '">' . str_replace(':', '', preview_email_const('OSH_EMAIL_TEXT_INVOICE_URL', 'Detailed Invoice')) . '</a>',
                'EMAIL_TEXT_DATE_ORDERED' => preview_email_const('OSH_EMAIL_TEXT_DATE_ORDERED', 'Date Ordered:') . ' ' . zen_date_long($datePurchased),
                'EMAIL_TEXT_STATUS_COMMENTS' => (!preg_match('/(<br|<p|<div|<dd|<li|<span)/i', $comments) ? nl2br($comments, false) : $comments),
                'EMAIL_TEXT_STATUS_UPDATED' => str_replace("\n", '', $statusText),
                'EMAIL_TEXT_STATUS_LABEL' => str_replace("\n", '', $statusValue),
                'EMAIL_TEXT_NEW_STATUS' => $newName,
                'EMAIL_TEXT_STATUS_PLEASE_REPLY' => str_replace("\n", '', preview_email_const('OSH_EMAIL_TEXT_STATUS_PLEASE_REPLY', 'Please reply to this email if you have any questions.')),
                'EMAIL_PAYPAL_TRANSID' => '',
            ];
            return [
                'subject' => preview_email_const('OSH_EMAIL_TEXT_SUBJECT', 'Order Update') . ' #' . $oID,
                'text' => $text,
                'block' => $block,
                'to_name' => $name,
                'to_email' => $email,
                'notes' => $notes,
            ];
        },
    ],
    [
        'key' => 'order_status_extra',
        'group' => $previewEmailGroupCore,
        'sort' => 16,
        'label' => preview_email_const('PREVIEW_EMAIL_LABEL_ORDER_STATUS_EXTRA', 'Order Status Update, Store Copy'),
        'describe' => preview_email_const('PREVIEW_EMAIL_DESC_ORDER_STATUS_EXTRA', 'The copy sent to Send Extra Order Status Update Emails To.'),
        'module' => 'order_status_extra',
        'page_base' => 'order_status',
        'build' => preview_email_extra_builder('order_status'),
    ],
];
