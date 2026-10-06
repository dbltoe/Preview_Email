<?php
/**
 * Preview Email -- the notices Zen Cart sends to admin users and the store
 * owner: an admin password reset, the multi-factor sign-in code, the alert
 * when an admin account is added, changed or deleted, and a payment module
 * alert. All of them use the default template.
 *
 * @package  PreviewEmail
 * @license  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

$previewEmailGroupAdmin = preview_email_const('PREVIEW_EMAIL_GROUP_ADMIN', 'Zen Cart Admin Notices');

$previewEmailAdminName = static function (): array {
    $name = (string)($_SESSION['admin_name'] ?? '');
    $email = '';
    global $db;
    if (isset($db) && defined('TABLE_ADMIN') && !empty($_SESSION['admin_id'])) {
        $a = $db->Execute("SELECT admin_name, admin_email FROM " . TABLE_ADMIN . " WHERE admin_id = " . (int)$_SESSION['admin_id'] . " LIMIT 1");
        if (!$a->EOF) {
            $name = (string)$a->fields['admin_name'];
            $email = (string)$a->fields['admin_email'];
        }
    }
    if ($name === '') {
        $name = 'admin';
    }
    if ($email === '') {
        $email = preview_email_const('STORE_OWNER_EMAIL_ADDRESS');
    }
    return ['name' => $name, 'email' => $email];
};

return [
    [
        'key' => 'password_forgotten_admin',
        'group' => $previewEmailGroupAdmin,
        'sort' => 10,
        'label' => preview_email_const('PREVIEW_EMAIL_LABEL_PASSWORD_FORGOTTEN_ADMIN', 'Admin Password Reset'),
        'describe' => preview_email_const('PREVIEW_EMAIL_DESC_PASSWORD_FORGOTTEN_ADMIN', 'Sent to an admin user who asks for a new password on the admin login page.'),
        'module' => 'password_forgotten_admin',
        'page_base' => 'password_forgotten',
        'build' => static function (array $def) use ($previewEmailAdminName): array {
            preview_email_load_admin_language('password_forgotten');
            $admin = $previewEmailAdminName();
            $body = sprintf(preview_email_const('TEXT_EMAIL_MESSAGE_PWD_RESET', 'A password reset was requested from %s. Your new temporary password is %s.'), '203.0.113.10', 'Tq8m#Vx2Lp');
            return [
                'subject' => preview_email_const('TEXT_EMAIL_SUBJECT_PWD_RESET', 'Admin password reset'),
                'text' => $body,
                'block' => ['EMAIL_CUSTOMERS_NAME' => $admin['name'], 'EMAIL_MESSAGE_HTML' => $body],
                'to_name' => $admin['name'],
                'to_email' => $admin['email'],
            ];
        },
    ],
    [
        'key' => 'admin_mfa',
        'group' => $previewEmailGroupAdmin,
        'sort' => 11,
        'label' => preview_email_const('PREVIEW_EMAIL_LABEL_ADMIN_MFA', 'Admin Sign-In Verification Code'),
        'describe' => preview_email_const('PREVIEW_EMAIL_DESC_ADMIN_MFA', 'The multi-factor code emailed at admin sign-in (Zen Cart 2.0 and later). Sent with the no_archive module so it is never written to the email archive.'),
        'module' => 'no_archive',
        'page_base' => 'login',
        'available' => static function () {
            return (is_file(DIR_FS_ADMIN . 'includes/functions/functions_mfa.php') || defined('TEXT_MFA_EMAIL_SUBJECT'))
                ? true
                : preview_email_const('PREVIEW_EMAIL_NA_MFA', 'This Zen Cart release has no multi-factor sign-in.');
        },
        'build' => static function (array $def) use ($previewEmailAdminName): array {
            preview_email_load_admin_language('login');
            $admin = $previewEmailAdminName();
            $body = sprintf(preview_email_const('TEXT_MFA_EMAIL_BODY', 'Your verification code is %s. Request came from %s.'), '482913', '203.0.113.10');
            return [
                'subject' => preview_email_const('TEXT_MFA_EMAIL_SUBJECT', 'Your sign-in code'),
                'text' => $body,
                'block' => ['EMAIL_CUSTOMERS_NAME' => $admin['email'], 'EMAIL_MESSAGE_HTML' => $body],
                'to_name' => $admin['name'],
                'to_email' => $admin['email'],
            ];
        },
    ],
    [
        'key' => 'admin_settings_changed',
        'group' => $previewEmailGroupAdmin,
        'sort' => 12,
        'label' => preview_email_const('PREVIEW_EMAIL_LABEL_ADMIN_SETTINGS_CHANGED', 'Admin Account Added, Changed or Deleted'),
        'describe' => preview_email_const('PREVIEW_EMAIL_DESC_ADMIN_SETTINGS_CHANGED', 'Sent to the store owner whenever an admin user is created, edited or removed.'),
        'module' => 'admin_settings_changed',
        'page_base' => 'admin',
        'build' => static function (array $def) use ($previewEmailAdminName): array {
            preview_email_load_admin_language('admin');
            $admin = $previewEmailAdminName();
            $who = '{' . $admin['name'] . ' [id: ' . (int)($_SESSION['admin_id'] ?? 1) . ']}';
            $body = sprintf(preview_email_const('TEXT_EMAIL_MESSAGE_ADMIN_USER_ADDED', 'Admin user %s was added by %s.'), 'newadmin [id: 9]', $who);
            return [
                'subject' => preview_email_const('TEXT_EMAIL_SUBJECT_ADMIN_USER_ADDED', 'Admin user added'),
                'text' => $body,
                'block' => ['EMAIL_MESSAGE_HTML' => $body],
                'to_name' => preview_email_const('STORE_NAME'),
                'to_email' => preview_email_const('STORE_OWNER_EMAIL_ADDRESS'),
            ];
        },
    ],
    [
        'key' => 'paymentalert',
        'group' => $previewEmailGroupAdmin,
        'sort' => 13,
        'label' => preview_email_const('PREVIEW_EMAIL_LABEL_PAYMENTALERT', 'Payment Module Alert'),
        'describe' => preview_email_const('PREVIEW_EMAIL_DESC_PAYMENTALERT', 'The alert a payment module sends the store owner when a transaction needs attention. The wording is the module\'s own; this shows the layout.'),
        'module' => 'paymentalert',
        'page_base' => 'checkout_process',
        'build' => static function (array $def): array {
            $body = preview_email_const('PREVIEW_EMAIL_SAMPLE_PAYMENTALERT', "Payment module alert\n\nA transaction for order 1001 needs attention.\n\nResponse: DECLINED (sample)\nAmount: 44.90\nTransaction ID: SAMPLE-TXN-0001");
            return [
                'subject' => preview_email_const('PREVIEW_EMAIL_SAMPLE_PAYMENTALERT_SUBJECT', 'Payment alert'),
                'text' => $body,
                'block' => ['EMAIL_MESSAGE_HTML' => nl2br($body)],
                'to_name' => preview_email_const('STORE_NAME'),
                'to_email' => preview_email_const('STORE_OWNER_EMAIL_ADDRESS'),
            ];
        },
    ],
];
