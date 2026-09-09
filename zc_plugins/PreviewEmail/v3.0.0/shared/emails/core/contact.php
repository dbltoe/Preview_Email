<?php
/**
 * Preview Email -- the messages customers send the store: Contact Us,
 * Ask a Question, and the pending-review notice.
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
        'key' => 'contact_us',
        'group' => $previewEmailGroupCore,
        'sort' => 30,
        'label' => preview_email_const('PREVIEW_EMAIL_LABEL_CONTACT_US', 'Contact Us'),
        'describe' => preview_email_const('PREVIEW_EMAIL_DESC_CONTACT_US', 'What the store receives when a visitor uses the Contact Us page.'),
        'module' => 'contact_us',
        'page_base' => 'contact_us',
        'build' => static function (array $def): array {
            preview_email_load_catalog_language('contact_us');
            preview_email_load_catalog_language('english');
            $customer = preview_email_sample_customer();
            $message = preview_email_const('PREVIEW_EMAIL_SAMPLE_CONTACT_MESSAGE', 'Hello, I have a question about my recent order. Could you let me know when it will ship? Thank you.');
            $from = preview_email_const('OFFICE_FROM', 'From:') . ' ' . $customer['name'] . '<br>' . preview_email_const('OFFICE_EMAIL', 'E-Mail:') . '(' . $customer['email'] . ')';
            $extra = function_exists('email_collect_extra_info')
                ? email_collect_extra_info($customer['name'], $customer['email'], $customer['name'], $customer['email'], $customer['telephone'])
                : ['HTML' => '', 'TEXT' => ''];
            return [
                'subject' => preview_email_const('EMAIL_SUBJECT', 'Contact Us'),
                'text' => $message . (string)($extra['TEXT'] ?? ''),
                'block' => [
                    'CONTACT_US_OFFICE_FROM' => $from,
                    'EMAIL_MESSAGE_HTML' => $message,
                    'EXTRA_INFO' => (string)($extra['HTML'] ?? ''),
                ],
                'to_name' => preview_email_const('STORE_OWNER'),
                'to_email' => preview_email_const('CONTACT_US_EMAIL_ADDRESS', preview_email_const('STORE_OWNER_EMAIL_ADDRESS')),
                'from_name' => $customer['name'],
                'from_email' => $customer['email'],
            ];
        },
    ],
    [
        'key' => 'ask_a_question',
        'group' => $previewEmailGroupCore,
        'sort' => 31,
        'label' => preview_email_const('PREVIEW_EMAIL_LABEL_ASK_A_QUESTION', 'Ask a Question About a Product'),
        'describe' => preview_email_const('PREVIEW_EMAIL_DESC_ASK_A_QUESTION', 'What the store receives from the Ask a Question link on a product page. Uses the Contact Us template unless email_template_ask_a_question.html exists.'),
        'module' => 'ask_a_question',
        'page_base' => 'contact_us',
        'available' => static function () {
            return defined('FILENAME_ASK_A_QUESTION') || is_dir(DIR_FS_CATALOG . 'includes/modules/pages/ask_a_question')
                ? true
                : preview_email_const('PREVIEW_EMAIL_NA_ASK_A_QUESTION', 'This Zen Cart release has no Ask a Question page.');
        },
        'build' => static function (array $def): array {
            preview_email_load_catalog_language('ask_a_question');
            preview_email_load_catalog_language('english');
            $customer = preview_email_sample_customer();
            $product = preview_email_sample_product();
            $question = preview_email_const('PREVIEW_EMAIL_SAMPLE_QUESTION', 'Does this come in other colors, and is it in stock right now?');
            $message = preview_email_const('TEXT_PRODUCT_NAME', 'Product:') . ' ' . $product['name'] . "\n\n" . $question;
            $from = preview_email_const('OFFICE_FROM', 'From:') . ' ' . $customer['name'] . '<br>' . preview_email_const('OFFICE_EMAIL', 'E-Mail:') . '(' . $customer['email'] . ')';
            $extra = function_exists('email_collect_extra_info')
                ? email_collect_extra_info($customer['name'], $customer['email'], $customer['name'], $customer['email'], $customer['telephone'])
                : ['HTML' => '', 'TEXT' => ''];
            return [
                'subject' => preview_email_const('EMAIL_SUBJECT', 'Question about a product') . ' ' . $product['name'],
                'text' => $message . (string)($extra['TEXT'] ?? ''),
                'block' => [
                    'CONTACT_US_OFFICE_FROM' => $from,
                    'EMAIL_MESSAGE_HTML' => nl2br($message),
                    'EXTRA_INFO' => (string)($extra['HTML'] ?? ''),
                ],
                'to_name' => preview_email_const('STORE_OWNER'),
                'to_email' => preview_email_const('CONTACT_US_EMAIL_ADDRESS', preview_email_const('STORE_OWNER_EMAIL_ADDRESS')),
                'from_name' => $customer['name'],
                'from_email' => $customer['email'],
            ];
        },
    ],
    [
        'key' => 'reviews_extra',
        'group' => $previewEmailGroupCore,
        'sort' => 32,
        'label' => preview_email_const('PREVIEW_EMAIL_LABEL_REVIEWS_EXTRA', 'Review Awaiting Approval'),
        'describe' => preview_email_const('PREVIEW_EMAIL_DESC_REVIEWS_EXTRA', 'Sent to the store when a customer writes a review and reviews need approval.'),
        'module' => 'reviews_extra',
        'page_base' => 'product_reviews_write',
        'build' => static function (array $def): array {
            preview_email_load_catalog_language('product_reviews_write');
            preview_email_load_catalog_language('english');
            $customer = preview_email_sample_customer();
            $product = preview_email_sample_product();
            $review = preview_email_const('PREVIEW_EMAIL_SAMPLE_REVIEW', 'Arrived quickly and works exactly as described. Would buy again.');
            $intro = sprintf(preview_email_const('EMAIL_PRODUCT_REVIEW_CONTENT_INTRO', 'A new review has been submitted for %s.'), $product['name']);
            $details = sprintf(preview_email_const('EMAIL_PRODUCT_REVIEW_CONTENT_DETAILS', 'Review: %s'), $review);
            $subject = sprintf(preview_email_const('EMAIL_REVIEW_PENDING_SUBJECT', 'Review pending approval for %s'), $product['name']);
            $extra = function_exists('email_collect_extra_info')
                ? email_collect_extra_info('', '', $customer['name'], $customer['email'])
                : ['HTML' => '', 'TEXT' => ''];
            return [
                'subject' => $subject,
                'text' => $intro . "\n\n" . $details . "\n\n" . (string)($extra['TEXT'] ?? ''),
                'block' => [
                    'EMAIL_SUBJECT' => $subject,
                    'EMAIL_MESSAGE_HTML' => str_replace('\n', '', $intro) . '<br>' . str_replace('\n', '', $details),
                    'EXTRA_INFO' => (string)($extra['HTML'] ?? ''),
                ],
                'to_name' => '',
                'to_email' => preview_email_const('SEND_EXTRA_REVIEW_NOTIFICATION_EMAILS_TO', preview_email_const('STORE_OWNER_EMAIL_ADDRESS')),
            ];
        },
    ],
];
