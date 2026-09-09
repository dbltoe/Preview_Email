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

/**
 * The header-image block the plugin adds to its HTML messages, if any.
 *
 * SCF uses only a header image the store owner has uploaded on its
 * Subscribers page. Until then it points the template's image tag at a
 * transparent 1x1 spacer, so its emails go out with a blank header rather
 * than the store logo. A blank in a preview is easy to mistake for the
 * finished email, so the preview (and a test send) shows a placeholder banner
 * that says what is going on and where to fix it. The real email still
 * carries the spacer, and the strip says so.
 *
 * @param string[] $notes  a note is appended when the placeholder is used
 */
$previewEmailScfHeader = static function (array &$notes): array {
    $block = function_exists('scf_header_image_block') ? (array)scf_header_image_block() : [];
    $logo = (string)($block['EMAIL_LOGO_FILE'] ?? '');
    if ($logo === '' || substr($logo, -10) !== 'spacer.png') {
        return $block; // an uploaded image, or nothing (core then uses the store logo)
    }
    $notes[] = 'Social Contact Footer sends this email with a blank header: it uses only a header image uploaded on Tools > Footer Newsletter Subscribers (its own page, not its Configuration settings), and none is. The banner shown here is a placeholder from Preview Email, not part of the real email.';
    return [
        'EMAIL_LOGO_FILE' => HTTP_CATALOG_SERVER . DIR_WS_CATALOG . 'zc_plugins/PreviewEmail/v3.0.0/images/scf_header_placeholder.png',
        'EMAIL_LOGO_ALT_TEXT' => 'Placeholder Header: no image is set for Social Contact Footer emails',
        'EMAIL_LOGO_ALT_TITLE_TEXT' => 'Placeholder Header: no image is set for Social Contact Footer emails',
        'EMAIL_LOGO_WIDTH' => '550',
        'EMAIL_LOGO_HEIGHT' => '110',
    ];
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
        $notes = [];
        $block = array_merge($block, $previewEmailScfHeader($notes));
        return [
            'subject' => sprintf(preview_email_const($subjectKey, '%s'), $store),
            'text' => sprintf(preview_email_const($textKey, "%1\$s %2\$s %3\$s %4\$s"), $store, $primary, $unsubscribe, $from),
            'block' => $block,
            'module' => $module,
            'to_name' => $customer['email'],
            'to_email' => $customer['email'],
            'notes' => $notes,
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
        $notes = [];
        $block = $previewEmailScfHeader($notes);
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
            'notes' => $notes,
        ];
    };
};

return [
    [
        'key' => 'scf_confirm',
        'group' => $previewEmailScfGroup,
        'sort' => 10,
        'label' => 'Please Confirm Your Subscription',
        'describe' => 'Sent to a visitor who signs up in the footer, or is imported, asking them to confirm.',
        'module' => 'social_contact_footer',
        'page_base' => 'index',
        'build' => $previewEmailScfSubscriber('SCF_EMAIL_CONFIRM_SUBJECT', 'SCF_EMAIL_CONFIRM_TEXT', 'SCF_EMAIL_CONFIRM_HTML', 'social_contact_footer', 'index', 'scf_confirm='),
    ],
    [
        'key' => 'scf_welcome',
        'group' => $previewEmailScfGroup,
        'sort' => 11,
        'label' => 'You Are Subscribed (Welcome)',
        'describe' => 'Sent once the subscriber has clicked the confirmation link.',
        'module' => 'social_contact_footer',
        'page_base' => 'index',
        'build' => $previewEmailScfSubscriber('SCF_EMAIL_WELCOME_SUBJECT', 'SCF_EMAIL_WELCOME_TEXT', 'SCF_EMAIL_WELCOME_HTML', 'social_contact_footer', 'index', 'scf_unsubscribe='),
    ],
    [
        'key' => 'scf_invite',
        'group' => $previewEmailScfGroup,
        'sort' => 12,
        'label' => 'Invitation to Open a Customer Account',
        'describe' => 'Sent from Tools > Newsletter Subscribers when you invite a subscriber to register.',
        'module' => 'social_contact_footer_invite',
        'page_base' => 'social_contact_footer_subscribers',
        'build' => $previewEmailScfInvite(false),
    ],
    [
        'key' => 'scf_reinvite',
        'group' => $previewEmailScfGroup,
        'sort' => 13,
        'label' => 'Invitation Re-Sent',
        'describe' => 'The second invitation, which carries a new password and says so.',
        'module' => 'social_contact_footer_invite',
        'page_base' => 'social_contact_footer_subscribers',
        'build' => $previewEmailScfInvite(true),
    ],
    [
        'key' => 'scf_owner_notice',
        'group' => $previewEmailScfGroup,
        'sort' => 14,
        'label' => 'New Subscriber Notice to the Store Owner',
        'describe' => 'Plain text to the store owner when a subscriber confirms.',
        'module' => 'social_contact_footer_admin',
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
