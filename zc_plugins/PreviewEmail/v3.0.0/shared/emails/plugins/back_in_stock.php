<?php
/**
 * Preview Email -- the Back in Stock notification, for either of the two
 * add-ons that send one: Ceon's Back In Stock Notifications and the
 * simpler Back in Stock alert. Carried over from Preview Email 2.x.
 *
 * Listed only when a store has email_template_back_in_stock_notification.html.
 *
 * @package  PreviewEmail
 * @license  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

$previewEmailBisTemplate = preview_email_resolve_template('back_in_stock_notification', 'back_in_stock_notification');
if ($previewEmailBisTemplate['fallback']) {
    return [];
}

$previewEmailBisIsCeon = is_file(DIR_FS_ADMIN . 'includes/classes/class.CeonBISNInstallOrUpgrade.php');

return [
    [
        'key' => 'back_in_stock',
        'group' => 'Back in Stock',
        'sort' => 10,
        'label' => $previewEmailBisIsCeon ? 'Back in Stock Notification (Ceon)' : 'Back in Stock Notification',
        'describe' => 'Sent to customers who asked to be told when a product is available again.',
        'module' => 'back_in_stock_notification',
        'page_base' => 'back_in_stock_notification',
        'build' => static function (array $def) use ($previewEmailBisIsCeon): array {
            $product = preview_email_sample_product();
            $link = zen_catalog_href_link('product_info', 'products_id=' . $product['id']);
            $image = ($product['image'] !== '')
                ? '<img style="max-width:100%;" src="' . HTTP_CATALOG_SERVER . DIR_WS_CATALOG . 'images/' . $product['image'] . '" alt="' . zen_output_string_protected($product['name']) . '">'
                : '';
            if ($previewEmailBisIsCeon) {
                preview_email_load_admin_language('back_in_stock_notifications');
                preview_email_load_catalog_language('back_in_stock_notifications', 'extra_definitions');
                $row = '<p class="BackInStockNotificationProduct"><a href="' . $link . '" target="_blank">' . zen_output_string_protected($product['name']) . '</a></p>' . "\n";
                return [
                    'subject' => preview_email_const('EMAIL_SUBJECT_SINGULAR', 'A product you wanted is back in stock'),
                    'text' => preview_email_const('EMAIL_INTRO_SINGULAR1', 'The product you asked about is back in stock:') . "\n\n" . $product['name'] . "\n" . str_replace('&amp;', '&', $link) . "\n\n" . preview_email_const('EMAIL_INTRO_SINGULAR2', ''),
                    'block' => [
                        'EMAIL_GREETING' => 'Mr. Prospect',
                        'EMAIL_INTRO_1' => preview_email_const('EMAIL_INTRO_SINGULAR1', 'The product you asked about is back in stock:'),
                        'EMAIL_INTRO_2' => preview_email_const('EMAIL_INTRO_SINGULAR2', ''),
                        'PRODUCTS_DETAIL_TITLE' => preview_email_const('PRODUCTS_DETAIL_TITLE_SINGULAR', 'Product'),
                        'PRODUCTS_DETAIL' => '<table class="product-details" border="0" width="100%" cellspacing="0" cellpadding="2">' . $row . '</table>',
                    ],
                    'to_name' => 'Mr. Prospect',
                    'to_email' => 'prospect@example.org',
                ];
            }
            preview_email_load_catalog_language('back_in_stock', 'extra_definitions');
            $top = preview_email_const('BACK_IN_STOCK_MAIL_TOP', 'Good news: ') . $product['name'] . "\n\n" . preview_email_const('BACK_IN_STOCK_MAIL_AVAILABLE', 'is available again.');
            $description = (preview_email_const('BACK_IN_STOCK_DESC_IN_EMAIL', '0') === '1' && function_exists('zen_get_products_description') && $product['id'] > 0)
                ? (string)zen_get_products_description($product['id'])
                : ' ';
            return [
                'subject' => 'Order ' . $product['name'] . ' now at ' . preview_email_const('STORE_NAME'),
                'text' => $top . "\n\n" . str_replace('&amp;', '&', $link) . "\n\n" . preview_email_const('BACK_IN_STOCK_MAIL_BOTTOM', ''),
                'block' => [
                    'CUSTOMERS_NAME' => 'Mr. Prospect',
                    'PRODUCT_NAME' => $product['name'],
                    'SPAM_LINK' => HTTP_CATALOG_SERVER . DIR_WS_CATALOG . 'index.php?main_page=back_in_stock&bis_id=1',
                    'TOP_MESSAGE' => $top,
                    'PRODUCT_DESCRIPTION' => $description,
                    'PRODUCT_IMAGE' => $image,
                    'PRODUCT_LINK' => $link,
                    'BOTTOM_MESSAGE' => preview_email_const('BACK_IN_STOCK_MAIL_BOTTOM', ''),
                ],
                'to_name' => 'Mr. Prospect',
                'to_email' => 'prospect@example.org',
            ];
        },
    ],
];
