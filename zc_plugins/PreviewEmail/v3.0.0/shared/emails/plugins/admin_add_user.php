<?php
/**
 * Preview Email -- the welcome email the Admin Add Customer plugin sends
 * when an account is created from admin: the standard welcome, plus the
 * activation link the customer must click before the account works.
 *
 * Listed only while Admin Add Customer (AdminAddUser) is installed.
 *
 * @package  PreviewEmail
 * @license  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

if (preview_email_installed_plugin_dir('AdminAddUser') === '') {
    return [];
}

return [
    [
        'key' => 'admin_add_user_welcome',
        'group' => 'Admin Add Customer',
        'sort' => 10,
        'label' => 'Welcome with activation link',
        'describe' => 'Sent when you create a customer from admin. The account stays inactive until the link is used.',
        'module' => 'welcome',
        'page_base' => 'add_customers',
        'build' => static function (array $def): array {
            preview_email_load_catalog_language('create_account');
            preview_email_load_catalog_language('email_extras');
            preview_email_load_catalog_language('english');
            preview_email_load_plugin_language('AdminAddUser', 'admin/includes/languages/', 'add_customers');
            preview_email_load_plugin_language('AdminAddUser', 'admin/includes/languages/', 'add_customers', 'extra_definitions');

            $customer = preview_email_sample_customer();
            $password = 'Rk4v#Nq8Ty';
            $token = 'sampleactivation0123456789';
            $activation = zen_catalog_href_link(defined('FILENAME_ADMIN_ADD_USER_ACTIVATE') ? FILENAME_ADMIN_ADD_USER_ACTIVATE : 'admin_add_user_activate', 'token=' . $token, 'SSL');
            $greeting = sprintf(preview_email_const('EMAIL_GREET_NONE', 'Dear %s,'), $customer['firstname']);
            $body = preview_email_const('EMAIL_TEXT_1', preview_email_const('EMAIL_TEXT', 'Welcome to our store.'))
                . sprintf(preview_email_const('EMAIL_TEXT_2', ' Your password is %s.'), $password)
                . preview_email_const('EMAIL_TEXT_3', '');
            $contact = preview_email_const('EMAIL_CONTACT', '');
            $closure = preview_email_const('EMAIL_GV_CLOSURE', '');
            $header = preview_email_const('EMAIL_ACTIVATION_HEADER', 'Your account is not yet active. To activate it, please click the link below:');
            $separator = preview_email_const('EMAIL_SEPARATOR', '------------------------------------------------------');
            $owner = preview_email_const('STORE_OWNER_EMAIL_ADDRESS');

            $text = $greeting . "\n\n" . preview_email_const('EMAIL_WELCOME', 'Welcome') . "\n\n"
                . $header . "\n" . str_replace('&amp;', '&', $activation) . "\n" . $separator . "\n\n"
                . $body . $contact . $closure . "\n\n"
                . sprintf(preview_email_const('EMAIL_DISCLAIMER_NEW_CUSTOMER', 'This email was sent because an account was created for you. Contact %s if this was not expected.'), $owner) . "\n\n";

            return [
                'subject' => preview_email_const('EMAIL_SUBJECT', 'Welcome to ' . preview_email_const('STORE_NAME')),
                'text' => $text,
                'block' => [
                    'EMAIL_GREETING' => $greeting,
                    'EMAIL_FIRST_NAME' => $customer['firstname'],
                    'EMAIL_LAST_NAME' => $customer['lastname'],
                    'EMAIL_WELCOME' => str_replace('\n', '', preview_email_const('EMAIL_WELCOME', 'Welcome')),
                    'EMAIL_ACTIVATION_HEADER' => $header,
                    'EMAIL_ACTIVATION_LINK' => '<a href="' . $activation . '">' . $activation . '</a>',
                    'EMAIL_MESSAGE_HTML' => str_replace('\n', '', $body),
                    'EMAIL_CONTACT_OWNER' => str_replace('\n', '', $contact),
                    'EMAIL_CLOSURE' => nl2br($closure),
                    'EMAIL_DISCLAIMER' => sprintf(preview_email_const('EMAIL_DISCLAIMER_NEW_CUSTOMER', 'Contact %s if this was not expected.'), '<a href="mailto:' . $owner . '">' . $owner . ' </a>'),
                ],
                'to_name' => $customer['name'],
                'to_email' => $customer['email'],
                'notes' => ['The activation link and password are samples. If your welcome template does not show the activation line, add $EMAIL_ACTIVATION_HEADER and $EMAIL_ACTIVATION_LINK to email/email_template_welcome.html as the plugin\'s readme describes.'],
            ];
        },
    ],
];
