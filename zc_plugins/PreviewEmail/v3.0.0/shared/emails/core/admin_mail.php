<?php
/**
 * Preview Email -- the mailings sent from admin: a direct email to a
 * customer, the newsletter, the product notification, and the plain
 * "default" template that every message without its own template uses.
 *
 * @package  PreviewEmail
 * @license  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

$previewEmailGroupCore = preview_email_const('PREVIEW_EMAIL_GROUP_CORE', 'Zen Cart');

/**
 * The three admin mailings share one shape: salutation, name, message.
 */
$previewEmailMailingBuilder = static function (string $subjectKey, string $subjectDefault, string $messageKey, string $messageDefault): callable {
    return static function (array $def) use ($subjectKey, $subjectDefault, $messageKey, $messageDefault): array {
        $customer = preview_email_sample_customer();
        $message = preview_email_const($messageKey, $messageDefault);
        return [
            'subject' => preview_email_const($subjectKey, $subjectDefault),
            'text' => preview_email_const('EMAIL_SALUTATION', 'Dear') . ' ' . $customer['name'] . ",\n\n" . strip_tags($message),
            'block' => [
                'EMAIL_SALUTATION' => preview_email_const('EMAIL_SALUTATION', 'Dear'),
                'EMAIL_FIRST_NAME' => $customer['firstname'],
                'EMAIL_LAST_NAME' => $customer['lastname'],
                'EMAIL_MESSAGE_HTML' => $message,
            ],
            'to_name' => $customer['name'],
            'to_email' => $customer['email'],
        ];
    };
};

return [
    [
        'key' => 'direct_email',
        'group' => $previewEmailGroupCore,
        'sort' => 50,
        'label' => preview_email_const('PREVIEW_EMAIL_LABEL_DIRECT_EMAIL', 'Direct email to a customer'),
        'describe' => preview_email_const('PREVIEW_EMAIL_DESC_DIRECT_EMAIL', 'Sent from Tools > Send Email. To preview your own wording, put it in a file named direct_test.html in the catalog root.'),
        'module' => 'direct_email',
        'page_base' => 'mail',
        'build' => static function (array $def) use ($previewEmailMailingBuilder): array {
            $built = $previewEmailMailingBuilder('PREVIEW_EMAIL_SAMPLE_DIRECT_SUBJECT', 'A note from ' . preview_email_const('STORE_NAME'), 'PREVIEW_EMAIL_SAMPLE_DIRECT_MESSAGE', 'This is a sample direct email, the kind sent from Tools &gt; Send Email.')($def);
            // Preview Email 1.x/2.x read a direct_test.html from the catalog
            // root; kept, because stores use it.
            $file = DIR_FS_CATALOG . 'direct_test.html';
            if (is_file($file)) {
                $built['block']['EMAIL_MESSAGE_HTML'] = (string)file_get_contents($file);
                $built['text'] = strip_tags($built['block']['EMAIL_MESSAGE_HTML']);
                $built['notes'][] = sprintf(preview_email_const('PREVIEW_EMAIL_NOTE_DIRECT_FILE', 'Message body read from %s.'), 'direct_test.html');
            }
            return $built;
        },
    ],
    [
        'key' => 'newsletters',
        'group' => $previewEmailGroupCore,
        'sort' => 51,
        'label' => preview_email_const('PREVIEW_EMAIL_LABEL_NEWSLETTERS', 'Newsletter'),
        'describe' => preview_email_const('PREVIEW_EMAIL_DESC_NEWSLETTERS', 'Sent from Tools > Newsletter and Product Notifications Manager, newsletter type.'),
        'module' => 'newsletters',
        'page_base' => 'newsletters',
        'build' => $previewEmailMailingBuilder('PREVIEW_EMAIL_SAMPLE_NEWSLETTER_SUBJECT', preview_email_const('STORE_NAME') . ' newsletter', 'PREVIEW_EMAIL_SAMPLE_NEWSLETTER_MESSAGE', '<p>This is a sample newsletter. Your own newsletter content appears here, wrapped in the newsletter template with its unsubscribe link at the bottom.</p>'),
    ],
    [
        'key' => 'product_notification',
        'group' => $previewEmailGroupCore,
        'sort' => 52,
        'label' => preview_email_const('PREVIEW_EMAIL_LABEL_PRODUCT_NOTIFICATION', 'Product notification'),
        'describe' => preview_email_const('PREVIEW_EMAIL_DESC_PRODUCT_NOTIFICATION', 'Sent from the Newsletter Manager, product notification type, to customers watching a product.'),
        'module' => 'product_notification',
        'page_base' => 'product_notification',
        'build' => $previewEmailMailingBuilder('PREVIEW_EMAIL_SAMPLE_NOTIFICATION_SUBJECT', 'News about a product you are watching', 'PREVIEW_EMAIL_SAMPLE_NOTIFICATION_MESSAGE', '<p>This is a sample product notification. The products you chose in the Newsletter Manager are described here.</p>'),
    ],
    [
        'key' => 'default',
        'group' => $previewEmailGroupCore,
        'sort' => 60,
        'label' => preview_email_const('PREVIEW_EMAIL_LABEL_DEFAULT', 'Default template (any other message)'),
        'describe' => preview_email_const('PREVIEW_EMAIL_DESC_DEFAULT', 'The template every message without one of its own falls back to: download failures, payment module alerts, and most add-on emails.'),
        'module' => 'default',
        'page_base' => 'default',
        'build' => static function (array $def): array {
            $customer = preview_email_sample_customer();
            $message = preview_email_const('PREVIEW_EMAIL_SAMPLE_DEFAULT_MESSAGE', 'This is a sample message using the default email template.');
            return [
                'subject' => preview_email_const('PREVIEW_EMAIL_DEFAULT_SUBJECT', 'Sample subject line'),
                'text' => $message,
                'block' => ['EMAIL_MESSAGE_HTML' => $message],
                'to_name' => $customer['name'],
                'to_email' => $customer['email'],
            ];
        },
    ],
];
