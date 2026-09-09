<?php
/**
 * Preview Email -- Zen Cart's account emails: the welcome message, the
 * store's copy of it, and the password reset.
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
        'key' => 'welcome',
        'group' => $previewEmailGroupCore,
        'sort' => 20,
        'label' => preview_email_const('PREVIEW_EMAIL_LABEL_WELCOME', 'Welcome (new account)'),
        'describe' => preview_email_const('PREVIEW_EMAIL_DESC_WELCOME', 'Sent when a customer creates an account, including any new-account coupon or gift certificate you have configured.'),
        'module' => 'welcome',
        'page_base' => 'create_account',
        'build' => static function (array $def): array {
            global $db;
            preview_email_load_catalog_language('create_account');
            preview_email_load_catalog_language('email_extras');
            preview_email_load_catalog_language('english');
            $customer = preview_email_sample_customer();

            $greeting = sprintf(preview_email_const('EMAIL_GREET_NONE', 'Dear %s,'), $customer['firstname']);
            $textBody = preview_email_const('EMAIL_TEXT', 'Welcome to our store.');
            $contact = preview_email_const('EMAIL_CONTACT', '');
            $closure = preview_email_const('EMAIL_GV_CLOSURE', '');

            $text = $greeting . "\n\n" . preview_email_const('EMAIL_WELCOME', 'Welcome') . "\n\n" . $textBody . "\n\n";
            $block = [
                'EMAIL_GREETING' => $greeting,
                'EMAIL_FIRST_NAME' => $customer['firstname'],
                'EMAIL_LAST_NAME' => $customer['lastname'],
                'EMAIL_WELCOME' => str_replace('\n', '', preview_email_const('EMAIL_WELCOME', 'Welcome')),
                'EMAIL_MESSAGE_HTML' => str_replace('\n', '', $textBody),
                'EMAIL_CONTACT_OWNER' => str_replace('\n', '', $contact),
                'EMAIL_CLOSURE' => nl2br($closure),
            ];

            // The new-account coupon, when one is configured, exactly as
            // create_account_send_email.php adds it.
            $couponId = (int)preview_email_const('NEW_SIGNUP_DISCOUNT_COUPON', '0');
            if ($couponId > 0 && isset($db) && defined('TABLE_COUPONS') && defined('TABLE_COUPONS_DESCRIPTION')) {
                $c = $db->Execute("SELECT c.coupon_code, cd.coupon_name, cd.coupon_description FROM " . TABLE_COUPONS . " c JOIN " . TABLE_COUPONS_DESCRIPTION . " cd ON cd.coupon_id = c.coupon_id AND cd.language_id = " . (int)($_SESSION['languages_id'] ?? 1) . " WHERE c.coupon_id = " . $couponId . " LIMIT 1");
                if (!$c->EOF) {
                    $text .= preview_email_const('EMAIL_COUPON_INCENTIVE_HEADER', '') . "\n" . $c->fields['coupon_description'] . "\n\n" . sprintf(preview_email_const('EMAIL_COUPON_REDEEM', 'Coupon code: %s'), $c->fields['coupon_code']) . "\n\n";
                    $block['COUPON_TEXT_VOUCHER_IS'] = preview_email_const('EMAIL_COUPON_INCENTIVE_HEADER', '');
                    $block['COUPON_TEXT_TO_REDEEM'] = str_replace('%s', '', preview_email_const('EMAIL_COUPON_REDEEM', 'Coupon code: '));
                    $block['COUPON_CODE'] = (string)$c->fields['coupon_code'];
                    $block['COUPON_DESCRIPTION'] = (string)$c->fields['coupon_description'];
                }
            }
            // The new-account gift certificate, when one is configured.
            $gvAmount = (float)preview_email_const('NEW_SIGNUP_GIFT_VOUCHER_AMOUNT', '0');
            if ($gvAmount > 0) {
                $currencies = preview_email_currencies();
                $code = 'SAMPLEGV01';
                $link = zen_catalog_href_link(defined('FILENAME_GV_REDEEM') ? FILENAME_GV_REDEEM : 'gv_redeem', 'gv_no=' . $code, 'SSL', false);
                $text .= sprintf(preview_email_const('EMAIL_GV_INCENTIVE_HEADER', 'Congratulations, you have received a Gift Certificate for %s'), $currencies->format($gvAmount)) . "\n\n"
                    . sprintf(preview_email_const('EMAIL_GV_REDEEM', 'Redeem it with code %s'), $code) . "\n" . str_replace('&amp;', '&', $link) . "\n\n";
                $block['GV_WORTH'] = sprintf(preview_email_const('EMAIL_GV_INCENTIVE_HEADER', '%s'), $currencies->format($gvAmount));
                $block['GV_REDEEM'] = sprintf(preview_email_const('EMAIL_GV_REDEEM', '%s'), $code);
                $block['GV_CODE_NUM'] = $code;
                $block['GV_CODE_URL'] = '<a href="' . $link . '">' . preview_email_const('EMAIL_GV_LINK', 'Redeem here') . '</a>';
                $block['GV_LINK_OTHER'] = preview_email_const('EMAIL_GV_LINK_OTHER', '');
            }
            $text .= $contact . $closure;

            return [
                'subject' => preview_email_const('EMAIL_SUBJECT', 'Welcome to ' . preview_email_const('STORE_NAME')),
                'text' => $text,
                'block' => $block,
                'to_name' => $customer['name'],
                'to_email' => $customer['email'],
            ];
        },
    ],
    [
        'key' => 'welcome_extra',
        'group' => $previewEmailGroupCore,
        'sort' => 21,
        'label' => preview_email_const('PREVIEW_EMAIL_LABEL_WELCOME_EXTRA', 'Welcome, store copy'),
        'describe' => preview_email_const('PREVIEW_EMAIL_DESC_WELCOME_EXTRA', 'The copy sent to Send Extra Create Account Emails To.'),
        'module' => 'welcome_extra',
        'page_base' => 'create_account',
        'build' => preview_email_extra_builder('welcome'),
    ],
    [
        'key' => 'password_forgotten',
        'group' => $previewEmailGroupCore,
        'sort' => 22,
        'label' => preview_email_const('PREVIEW_EMAIL_LABEL_PASSWORD_FORGOTTEN', 'Password reset (customer)'),
        'describe' => preview_email_const('PREVIEW_EMAIL_DESC_PASSWORD_FORGOTTEN', 'Sent from the storefront Password Forgotten page. Zen Cart 2.x sends a reset link; 1.5.8 sends a new password.'),
        'module' => 'password_forgotten',
        'page_base' => 'password_forgotten',
        'build' => static function (array $def): array {
            preview_email_load_catalog_language('password_forgotten');
            preview_email_load_catalog_language('english');
            $customer = preview_email_sample_customer();
            if (defined('EMAIL_PASSWORD_RESET_BODY')) {
                $url = zen_catalog_href_link('password_reset', 'token=SAMPLETOKEN0123456789', 'SSL', false);
                $body = sprintf((string)EMAIL_PASSWORD_RESET_BODY, '203.0.113.10', preview_email_const('STORE_NAME'), str_replace('&amp;', '&', $url));
                $subject = preview_email_const('EMAIL_PASSWORD_RESET_SUBJECT', 'Password Reset');
            } else {
                $body = sprintf(preview_email_const('EMAIL_PASSWORD_REMINDER_BODY', 'A new password was requested. Your new password is: %s'), 'Xy7pQ2mR');
                $subject = preview_email_const('EMAIL_PASSWORD_REMINDER_SUBJECT', 'Password Reminder');
            }
            return [
                'subject' => $subject,
                'text' => $body,
                'block' => [
                    'EMAIL_CUSTOMERS_NAME' => $customer['name'],
                    'EMAIL_MESSAGE_HTML' => $body,
                ],
                'to_name' => $customer['name'],
                'to_email' => $customer['email'],
            ];
        },
    ],
];
