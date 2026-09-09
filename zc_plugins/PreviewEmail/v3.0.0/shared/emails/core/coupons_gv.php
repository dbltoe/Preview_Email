<?php
/**
 * Preview Email -- coupon and gift certificate emails: the coupon mailing
 * from admin, the gift certificate mailing from admin, the release notice
 * when a queued certificate is approved, and the certificate a customer
 * sends to a friend.
 *
 * @package  PreviewEmail
 * @license  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

$previewEmailGroupCore = preview_email_const('PREVIEW_EMAIL_GROUP_CORE', 'Zen Cart');

/** The currencies object, created if the admin page has not already. */
$previewEmailCurrencies = static function () {
    return preview_email_currencies();
};

$previewEmailGvAvailable = static function () {
    return (preview_email_const('MODULE_ORDER_TOTAL_GV_STATUS') === 'true')
        ? true
        : preview_email_const('PREVIEW_EMAIL_NA_GV', 'The Gift Certificates order-total module is not installed (Modules > Order Total).');
};

$previewEmailRedeemUrl = static function (): string {
    if (preview_email_const('SEARCH_ENGINE_FRIENDLY_URLS') === 'true') {
        return HTTP_CATALOG_SERVER . DIR_WS_CATALOG . 'index.php/gv_redeem/gv_no/';
    }
    return HTTP_CATALOG_SERVER . DIR_WS_CATALOG . 'index.php?main_page=gv_redeem&gv_no=';
};

return [
    [
        'key' => 'coupon',
        'group' => $previewEmailGroupCore,
        'sort' => 40,
        'label' => preview_email_const('PREVIEW_EMAIL_LABEL_COUPON', 'Coupon mailing'),
        'describe' => preview_email_const('PREVIEW_EMAIL_DESC_COUPON', 'Sent from Marketing > Coupon Admin > Email Coupon. Built from one of your coupons, or an invented one if there are none.'),
        'module' => 'coupon',
        'page_base' => 'coupon',
        'build' => static function (array $def): array {
            preview_email_load_admin_language('coupon_admin');
            $customer = preview_email_sample_customer();
            $coupon = preview_email_sample_coupon();
            $help = sprintf(preview_email_const('HTML_COUPON_HELP_DATE', ' (valid %s to %s)'), zen_date_short($coupon['start']), zen_date_short($coupon['expire']));
            $message = preview_email_const('PREVIEW_EMAIL_SAMPLE_COUPON_MESSAGE', 'As a thank-you for being a customer, here is a coupon for your next order.');
            $storeUrl = HTTP_CATALOG_SERVER . DIR_WS_CATALOG;
            $text = preview_email_const('EMAIL_SALUTATION', 'Dear') . ' ' . $customer['name'] . ",\n\n" . $message . "\n\n"
                . preview_email_const('TEXT_VOUCHER_IS', 'The coupon code is') . ' ' . $coupon['code'] . strip_tags($help) . "\n\n"
                . preview_email_const('TEXT_REMEMBER', 'Remember to use the code at checkout.') . "\n\n"
                . ($coupon['description'] !== '' ? $coupon['description'] . "\n\n" : '')
                . sprintf(preview_email_const('TEXT_VISIT', 'Visit %s'), $storeUrl);
            return [
                'subject' => preview_email_const('PREVIEW_EMAIL_SAMPLE_COUPON_SUBJECT', 'A coupon from ' . preview_email_const('STORE_NAME')),
                'text' => $text,
                'block' => [
                    'EMAIL_SALUTATION' => preview_email_const('EMAIL_SALUTATION', 'Dear'),
                    'EMAIL_FIRST_NAME' => $customer['firstname'],
                    'EMAIL_LAST_NAME' => $customer['lastname'],
                    'EMAIL_MESSAGE_HTML' => $message,
                    'COUPON_TEXT_TO_REDEEM' => preview_email_const('TEXT_TO_REDEEM', 'To use the coupon, enter this code at checkout:'),
                    'COUPON_TEXT_VOUCHER_IS' => preview_email_const('TEXT_VOUCHER_IS', 'The coupon is'),
                    'COUPON_CODE' => $coupon['code'] . $help,
                    'COUPON_DESCRIPTION' => $coupon['description'],
                    'COUPON_TEXT_REMEMBER' => preview_email_const('TEXT_REMEMBER', 'Remember to use the code at checkout.'),
                    'COUPON_REDEEM_STORENAME_URL' => sprintf(preview_email_const('TEXT_VISIT', 'Visit %s'), '<a href="' . $storeUrl . '">' . preview_email_const('STORE_NAME') . '</a>'),
                ],
                'to_name' => $customer['name'],
                'to_email' => $customer['email'],
            ];
        },
    ],
    [
        'key' => 'coupon_extra',
        'group' => $previewEmailGroupCore,
        'sort' => 41,
        'label' => preview_email_const('PREVIEW_EMAIL_LABEL_COUPON_EXTRA', 'Coupon mailing, store copy'),
        'describe' => preview_email_const('PREVIEW_EMAIL_DESC_COUPON_EXTRA', 'The copy sent to Send Extra Discount Coupon Admin Emails To.'),
        'module' => 'coupon_extra',
        'page_base' => 'coupon',
        'build' => preview_email_extra_builder('coupon'),
    ],
    [
        'key' => 'gv_mail',
        'group' => $previewEmailGroupCore,
        'sort' => 42,
        'label' => preview_email_const('PREVIEW_EMAIL_LABEL_GV_MAIL', 'Gift certificate mailing'),
        'describe' => preview_email_const('PREVIEW_EMAIL_DESC_GV_MAIL', 'Sent from Marketing > Gift Certificate Admin > Email Gift Certificate.'),
        'module' => 'gv_mail',
        'page_base' => 'gv_mail',
        'available' => $previewEmailGvAvailable,
        'build' => static function (array $def) use ($previewEmailCurrencies, $previewEmailRedeemUrl): array {
            preview_email_load_admin_language('gv_mail');
            preview_email_load_catalog_language('gv_mail');
            $currencies = $previewEmailCurrencies();
            $customer = preview_email_sample_customer();
            $code = 'SAMPLEGV01';
            $value = $currencies->format(25.00);
            $url = $previewEmailRedeemUrl();
            $message = preview_email_const('PREVIEW_EMAIL_SAMPLE_GV_MESSAGE', 'Thank you for being a customer. Please enjoy this gift certificate on your next order.');
            $text = preview_email_const('EMAIL_SALUTATION', 'Dear') . ' ' . $customer['name'] . ",\n\n" . $message . "\n\n"
                . sprintf(preview_email_const('TEXT_GV_ANNOUNCE', 'Congratulations, you have received a Gift Certificate worth %s'), $value) . "\n\n"
                . sprintf(preview_email_const('TEXT_GV_TO_REDEEM_TEXT', 'To redeem it, go to %s%s'), $url, $code);
            return [
                'subject' => preview_email_const('PREVIEW_EMAIL_SAMPLE_GV_SUBJECT', 'A gift certificate from ' . preview_email_const('STORE_NAME')),
                'text' => $text,
                'block' => [
                    'EMAIL_SALUTATION' => preview_email_const('EMAIL_SALUTATION', 'Dear'),
                    'EMAIL_FIRST_NAME' => $customer['firstname'],
                    'EMAIL_LAST_NAME' => $customer['lastname'],
                    'EMAIL_MESSAGE_HTML' => $message,
                    'GV_WORTH' => preview_email_const('TEXT_GV_WORTH', ''),
                    'GV_AMOUNT' => $value,
                    'GV_ANNOUNCE' => sprintf(preview_email_const('TEXT_GV_ANNOUNCE', 'Congratulations, you have received a Gift Certificate worth %s'), $value),
                    'GV_REDEEM' => sprintf(preview_email_const('TEXT_GV_TO_REDEEM_HTML', 'To redeem it, <a href="%s%s">click here</a>.'), $url, $code),
                ],
                'to_name' => $customer['name'],
                'to_email' => $customer['email'],
            ];
        },
    ],
    [
        'key' => 'gv_mail_extra',
        'group' => $previewEmailGroupCore,
        'sort' => 43,
        'label' => preview_email_const('PREVIEW_EMAIL_LABEL_GV_MAIL_EXTRA', 'Gift certificate mailing, store copy'),
        'describe' => preview_email_const('PREVIEW_EMAIL_DESC_GV_MAIL_EXTRA', 'The copy sent to Send Extra Gift Certificate Admin Emails To.'),
        'module' => 'gv_mail_extra',
        'page_base' => 'gv_mail',
        'available' => $previewEmailGvAvailable,
        'build' => preview_email_extra_builder('gv_mail'),
    ],
    [
        'key' => 'gv_queue',
        'group' => $previewEmailGroupCore,
        'sort' => 44,
        'label' => preview_email_const('PREVIEW_EMAIL_LABEL_GV_QUEUE', 'Gift certificate released from the queue'),
        'describe' => preview_email_const('PREVIEW_EMAIL_DESC_GV_QUEUE', 'Sent to the buyer when you release a purchased gift certificate in Marketing > Gift Certificate Queue.'),
        'module' => 'gv_queue',
        'page_base' => 'gv_queue',
        'available' => $previewEmailGvAvailable,
        'build' => static function (array $def) use ($previewEmailCurrencies): array {
            preview_email_load_admin_language('gv_queue');
            preview_email_load_catalog_language('gv_queue');
            $currencies = $previewEmailCurrencies();
            $customer = preview_email_sample_customer();
            $value = $currencies->format(25.00);
            $header = preview_email_const('TEXT_REDEEM_GV_MESSAGE_HEADER', 'Your Gift Certificate has been released.');
            $released = preview_email_const('TEXT_REDEEM_GV_MESSAGE_RELEASED', 'It is now available in your account.');
            $amount = sprintf(preview_email_const('TEXT_REDEEM_GV_MESSAGE_AMOUNT', 'Amount: %s'), '<strong>' . $value . '</strong>');
            $thanks = preview_email_const('TEXT_REDEEM_GV_MESSAGE_THANKS', 'Thank you.');
            $body = preview_email_const('TEXT_REDEEM_GV_MESSAGE_BODY', '');
            $footer = preview_email_const('TEXT_REDEEM_GV_MESSAGE_FOOTER', '');
            $text = preview_email_const('EMAIL_SALUTATION', 'Dear') . ' ' . $customer['name'] . ",\n\n" . $header . "\n" . $released . "\n" . strip_tags($amount) . "\n\n" . $body . "\n" . $thanks . "\n\n" . $footer;
            return [
                'subject' => preview_email_const('TEXT_REDEEM_GV_SUBJECT', 'Your Gift Certificate has been released'),
                'text' => $text,
                'block' => [
                    'EMAIL_SALUTATION' => preview_email_const('EMAIL_SALUTATION', 'Dear'),
                    'EMAIL_FIRST_NAME' => $customer['firstname'],
                    'EMAIL_LAST_NAME' => $customer['lastname'],
                    'GV_NOTICE_HEADER' => $header,
                    'GV_NOTICE_RELEASED' => $released,
                    'GV_NOTICE_AMOUNT_REDEEM' => $amount,
                    'GV_NOTICE_VALUE' => $value,
                    'GV_NOTICE_THANKS' => $thanks,
                    'TEXT_REDEEM_GV_MESSAGE_BODY' => $body,
                    'TEXT_REDEEM_GV_MESSAGE_FOOTER' => $footer,
                ],
                'to_name' => $customer['name'],
                'to_email' => $customer['email'],
            ];
        },
    ],
    [
        'key' => 'gv_send',
        'group' => $previewEmailGroupCore,
        'sort' => 45,
        'label' => preview_email_const('PREVIEW_EMAIL_LABEL_GV_SEND', 'Gift certificate sent by a customer'),
        'describe' => preview_email_const('PREVIEW_EMAIL_DESC_GV_SEND', 'What the recipient gets when a customer sends a gift certificate from their account.'),
        'module' => 'gv_send',
        'page_base' => 'gv_send',
        'available' => $previewEmailGvAvailable,
        'build' => static function (array $def) use ($previewEmailCurrencies): array {
            preview_email_load_catalog_language('gv_send');
            preview_email_load_catalog_language('english');
            $currencies = $previewEmailCurrencies();
            $customer = preview_email_sample_customer();
            $code = 'SAMPLEGV01';
            $amount = $currencies->format(25.00, false);
            $toName = preview_email_const('PREVIEW_EMAIL_SAMPLE_GV_RECIPIENT', 'Jordan Reyes');
            $message = preview_email_const('PREVIEW_EMAIL_SAMPLE_GV_SEND_MESSAGE', 'Happy birthday! Pick out something you like.');
            $redeemLink = zen_catalog_href_link(defined('FILENAME_GV_REDEEM') ? FILENAME_GV_REDEEM : 'gv_redeem', 'gv_no=' . $code, 'NONSSL', false);
            $separator = preview_email_const('EMAIL_SEPARATOR', '------------------------------------------------------');
            $text = sprintf(preview_email_const('EMAIL_GV_TEXT_HEADER', 'Congratulations, you have received a Gift Certificate worth %s'), $amount) . "\n\n"
                . sprintf(preview_email_const('EMAIL_GV_FROM', 'It was sent to you by %s'), $customer['name']) . "\n\n"
                . preview_email_const('EMAIL_GV_MESSAGE', 'With this message:') . "\n\n"
                . sprintf(preview_email_const('EMAIL_GV_SEND_TO', 'To: %s'), $toName) . "\n\n" . $message . "\n\n" . $separator . "\n\n"
                . sprintf(preview_email_const('EMAIL_GV_REDEEM', 'To redeem it, use code %s'), $code) . "\n" . str_replace('&amp;', '&', $redeemLink) . "\n\n"
                . preview_email_const('EMAIL_GV_FIXED_FOOTER', '') . "\n" . preview_email_const('EMAIL_GV_SHOP_FOOTER', '');
            return [
                'subject' => sprintf(preview_email_const('EMAIL_GV_TEXT_SUBJECT', 'A gift from %s'), $customer['name']),
                'text' => $text,
                'block' => [
                    'EMAIL_GV_TEXT_HEADER' => sprintf(preview_email_const('EMAIL_GV_TEXT_HEADER', 'Congratulations, you have received a Gift Certificate worth %s'), ''),
                    'EMAIL_GV_AMOUNT' => $amount,
                    'EMAIL_GV_FROM' => sprintf(preview_email_const('EMAIL_GV_FROM', 'It was sent to you by %s'), $customer['name']),
                    'EMAIL_GV_MESSAGE' => preview_email_const('EMAIL_GV_MESSAGE', 'With this message:') . '<br>',
                    'EMAIL_GV_SEND_TO' => '<tt>' . sprintf(preview_email_const('EMAIL_GV_SEND_TO', 'To: %s'), $toName) . '</tt><br>',
                    'EMAIL_MESSAGE_HTML' => $message,
                    'GV_REDEEM_HOW' => sprintf(preview_email_const('EMAIL_GV_REDEEM', 'To redeem it, use code %s'), '<strong>' . $code . '</strong>'),
                    'GV_REDEEM_URL' => '<a href="' . $redeemLink . '">' . preview_email_const('EMAIL_GV_LINK', 'Redeem it here') . '</a>',
                    'GV_REDEEM_CODE' => $code,
                    'EMAIL_GV_FIXED_FOOTER' => str_replace(["\r\n", "\n", "\r", '-----'], '', preview_email_const('EMAIL_GV_FIXED_FOOTER', '')),
                    'EMAIL_GV_SHOP_FOOTER' => preview_email_const('EMAIL_GV_SHOP_FOOTER', ''),
                ],
                'to_name' => $toName,
                'to_email' => 'jordan.reyes@example.com',
            ];
        },
    ],
    [
        'key' => 'gv_send_extra',
        'group' => $previewEmailGroupCore,
        'sort' => 46,
        'label' => preview_email_const('PREVIEW_EMAIL_LABEL_GV_SEND_EXTRA', 'Gift certificate sent by a customer, store copy'),
        'describe' => preview_email_const('PREVIEW_EMAIL_DESC_GV_SEND_EXTRA', 'The copy sent to Send Extra Gift Certificate Customer Emails To.'),
        'module' => 'gv_send_extra',
        'page_base' => 'gv_send',
        'available' => $previewEmailGvAvailable,
        'build' => preview_email_extra_builder('gv_send'),
    ],
];
