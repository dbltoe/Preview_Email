<?php
/**
 * Preview Email -- the notice the Fraud Screen plugin sends staff when it
 * holds an order. Plain text on the default template.
 *
 * Listed only while Fraud Screen is installed.
 *
 * @package  PreviewEmail
 * @license  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

if (preview_email_installed_plugin_dir('FraudScreen') === '') {
    return [];
}

return [
    [
        'key' => 'fraud_screen_hold',
        'group' => 'Fraud Screen',
        'sort' => 10,
        'label' => 'Order Held for Review',
        'describe' => 'Sent to the Fraud Screen notification address when an order scores above the threshold.',
        'module' => 'default',
        'page_base' => 'checkout_process',
        'available' => static function () {
            return (trim(preview_email_const('FRAUD_SCREEN_NOTIFY_EMAIL')) !== '')
                ? true
                : 'No notification address is set in the Fraud Screen configuration, so this notice is never sent.';
        },
        'build' => static function (array $def): array {
            $oID = preview_email_sample_order_id();
            if ($oID < 1) {
                $oID = 1001;
            }
            $score = 65;
            $comment = 'Fraud Screen held this order (score ' . $score . '): billing country differs from IP country; free email domain; first order from this address.';
            $body = $comment . "\n\n" . sprintf('Order number %d, on %s.', $oID, preview_email_const('STORE_NAME'));
            return [
                'subject' => sprintf('Order #%d held by Fraud Screen (score %d)', $oID, $score),
                'text' => $body,
                'block' => ['EMAIL_MESSAGE_HTML' => nl2br($body)],
                'to_name' => '',
                'to_email' => trim(preview_email_const('FRAUD_SCREEN_NOTIFY_EMAIL', preview_email_const('STORE_OWNER_EMAIL_ADDRESS'))),
            ];
        },
    ],
];
