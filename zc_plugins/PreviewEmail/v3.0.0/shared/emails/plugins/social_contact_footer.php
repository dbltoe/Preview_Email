<?php
/**
 * Preview Email -- the emails the Social Contact Footer plugin sends: the
 * subscription confirmation request, the welcome once confirmed, the
 * invitation to open a customer account (and its re-send), and the notice
 * the store owner gets about a new subscriber.
 *
 * Listed only while Social Contact Footer is installed and enabled. Each
 * message is built with the plugin's own language strings and the same
 * sprintf() arguments its senders use, so the preview is the real wording.
 *
 * @package  PreviewEmail
 * @license  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

$previewEmailScfDir = preview_email_installed_plugin_dir('SocialContactFooter');
if ($previewEmailScfDir === '') {
    return [];
}

$previewEmailScfGroup = 'Social Contact Footer';

/** Load the plugin's language strings and its header-image helper. */
$previewEmailScfPrepare = static function () use ($previewEmailScfDir): void {
    preview_email_load_plugin_language('SocialContactFooter', 'catalog/includes/languages/', 'social_contact_footer', 'extra_definitions');
    preview_email_load_plugin_language('SocialContactFooter', 'admin/includes/languages/', 'social_contact_footer_subscribers');
    if (!function_exists('scf_header_image_block') && is_file($previewEmailScfDir . 'shared/email_header.php')) {
        require_once $previewEmailScfDir . 'shared/email_header.php';
    }
};

/** The header-image block the plugin adds to its HTML messages, if any. */
$previewEmailScfHeader = static function (): array {
    return function_exists('scf_header_image_block') ? (array)scf_header_image_block() : [];
};

$previewEmailScfSubscriber = static function (string $subjectKey, string $textKey, string $htmlKey, string $module, string $primaryPage, string $primaryParams) use ($previewEmailScfPrepare, $previewEmailScfHeader): callable {
    return static function (array $def) use ($subjectKey, $textKey, $htmlKey, $module, $primaryPage, $primaryParams, $previewEmailScfPrepare, $previewEmailScfHeader): array {
        $previewEmailScfPrepare();
        $customer = preview_email_sample_customer();
        $store = preview_email_const('STORE_NAME');
        $from = preview_email_const('EMAIL_FROM');
        $token = 'sampletoken0123456789abcdef';
        $primary = str_replace('&amp;', '&', zen_catalog_href_link($primaryPage, $primaryParams . $token, 'SSL', false));
        $unsubscribe = str_replace('&amp;', '&', zen_catalog_href_link('index', 'scf_unsubscribe=' . $token, 'SSL', false));
        $block = ['EMAIL_MESSAGE_HTML' => sprintf(preview_email_const($htmlKey, '<p>%1$s %2$s %3$s %4$s</p>'), zen_output_string_protected($store), zen_output_string_protected($primary), zen_output_string_protected($unsubscribe), zen_output_string_protected($from))];
        $block = array_merge($block, $previewEmailScfHeader());
        return [
            'subject' => sprintf(preview_email_const($subjectKey, '%s'), $store),
            'text' => sprintf(preview_email_const($textKey, "%1\$s %2\$s %3\$s %4\$s"), $store, $primary, $unsubscribe, $from),
            'block' => $block,
            'module' => $module,
            'to_name' => $customer['email'],
            'to_email' => $customer['email'],
        ];
    };
};

$previewEmailScfInvite = static function (bool $resend) use ($previewEmailScfPrepare, $previewEmailScfHeader): callable {
    return static function (array $def) use ($resend, $previewEmailScfPrepare, $previewEmailScfHeader): array {
        $previewEmailScfPrepare();
        $customer = preview_email_sample_customer();
        $store = preview_email_const('STORE_NAME');
        $from = preview_email_const('EMAIL_FROM');
        $token = 'sampleinvite0123456789abcdef';
        $password = 'Wq7#pLm2Xz';
        $activation = str_replace('&amp;', '&', zen_catalog_href_link('login', 'scf_invite=' . $token, 'SSL', false));
        $unsubscribe = str_replace('&amp;', '&', zen_catalog_href_link('index', 'scf_unsubscribe=' . $token, 'SSL', false));
        $subject = $resend ? 'SCF_EMAIL_REINVITE_SUBJECT' : 'SCF_EMAIL_INVITE_SUBJECT';
        $textKey = $resend ? 'SCF_EMAIL_REINVITE_TEXT' : 'SCF_EMAIL_INVITE_TEXT';
        $htmlKey = $resend ? 'SCF_EMAIL_REINVITE_HTML' : 'SCF_EMAIL_INVITE_HTML';
        $block = $previewEmailScfHeader();
        $block['EMAIL_MESSAGE_HTML'] = sprintf(
            preview_email_const($htmlKey, '<p>%1$s %2$s %3$s %4$s %5$s %6$s</p>'),
            zen_output_string_protected($store),
            zen_output_string_protected($activation),
            zen_output_string_protected($customer['email']),
            zen_output_string_protected($password),
            zen_output_string_protected($unsubscribe),
            zen_output_string_protected($from)
        );
        return [
            'subject' => sprintf(preview_email_const($subject, '%s'), $store),
            'text' => sprintf(preview_email_const($textKey, "%1\$s %2\$s %3\$s %4\$s %5\$s %6\$s"), $store, $activation, $customer['email'], $password, $unsubscribe, $from),
            'block' => $block,
            'to_name' => $customer['name'],
            'to_email' => $customer['email'],
        ];
    };
};

return [
    [
        'key' => 'scf_confirm',
        'group' => $previewEmailScfGroup,
        'sort' => 10,
        'label' => 'Please confirm your subscription',
        'describe' => 'Sent to a visitor who signs up in the footer, or is imported, asking them to confirm.',
        'module' => 'social_contact_footer',
        'page_base' => 'index',
        'build' => $previewEmailScfSubscriber('SCF_EMAIL_CONFIRM_SUBJECT', 'SCF_EMAIL_CONFIRM_TEXT', 'SCF_EMAIL_CONFIRM_HTML', 'social_contact_footer', 'index', 'scf_confirm='),
    ],
    [
        'key' => 'scf_welcome',
        'group' => $previewEmailScfGroup,
        'sort' => 11,
        'label' => 'You are subscribed (welcome)',
        'describe' => 'Sent once the subscriber has clicked the confirmation link.',
        'module' => 'social_contact_footer',
        'page_base' => 'index',
        'build' => $previewEmailScfSubscriber('SCF_EMAIL_WELCOME_SUBJECT', 'SCF_EMAIL_WELCOME_TEXT', 'SCF_EMAIL_WELCOME_HTML', 'social_contact_footer', 'index', 'scf_unsubscribe='),
    ],
    [
        'key' => 'scf_invite',
        'group' => $previewEmailScfGroup,
        'sort' => 12,
        'label' => 'Invitation to open a customer account',
        'describe' => 'Sent from Tools > Newsletter Subscribers when you invite a subscriber to register.',
        'module' => 'social_contact_footer_invite',
        'page_base' => 'social_contact_footer_subscribers',
        'build' => $previewEmailScfInvite(false),
    ],
    [
        'key' => 'scf_reinvite',
        'group' => $previewEmailScfGroup,
        'sort' => 13,
        'label' => 'Invitation re-sent',
        'describe' => 'The second invitation, which carries a new password and says so.',
        'module' => 'social_contact_footer_invite',
        'page_base' => 'social_contact_footer_subscribers',
        'build' => $previewEmailScfInvite(true),
    ],
    [
        'key' => 'scf_owner_notice',
        'group' => $previewEmailScfGroup,
        'sort' => 14,
        'label' => 'New subscriber notice to the store owner',
        'describe' => 'Plain text to the store owner when a subscriber confirms.',
        'module' => 'default',
        'page_base' => 'index',
        'build' => static function (array $def) use ($previewEmailScfPrepare): array {
            $previewEmailScfPrepare();
            $customer = preview_email_sample_customer();
            $store = preview_email_const('STORE_NAME');
            $body = sprintf(preview_email_const('SCF_EMAIL_ADMIN_TEXT', "A new subscriber has confirmed:\n\nEmail: %1\$s\nPreferred format: %2\$s\n"), $customer['email'], 'HTML');
            return [
                'subject' => sprintf(preview_email_const('SCF_EMAIL_ADMIN_SUBJECT', 'New footer subscriber at %s'), $store),
                'text' => $body,
                'block' => ['EMAIL_MESSAGE_HTML' => nl2br($body)],
                'to_name' => preview_email_const('STORE_OWNER'),
                'to_email' => preview_email_const('STORE_OWNER_EMAIL_ADDRESS'),
            ];
        },
    ],
];
