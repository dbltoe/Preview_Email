<?php
/**
 * Preview Email -- admin page strings (English).
 *
 * Loaded for the preview_email page by the admin language loader on every
 * supported release (array form). The group names and labels here can be
 * overridden by a store's own language file, since a constant already
 * defined is never redefined.
 *
 * @package  PreviewEmail
 * @license  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

$define = [
    'HEADING_TITLE' => 'Preview Email',
    'PREVIEW_EMAIL_INTRO' => 'Every email this store can send, built from your own recent customers, orders and products. Open one as HTML or as the plain-text part, or send yourself a test in either format. Plain text is what a customer who chose "TEXT-Only" receives, so it is worth a look too.',

    /* status strip */
    'PREVIEW_EMAIL_STATUS_SENDING' => 'Sending:',
    'PREVIEW_EMAIL_STATUS_HTML' => 'HTML email:',
    'PREVIEW_EMAIL_STATUS_TRANSPORT' => 'Transport:',
    'PREVIEW_EMAIL_STATUS_TEMPLATES' => 'Templates in',
    'PREVIEW_EMAIL_STATUS_ON' => 'on',
    'PREVIEW_EMAIL_STATUS_OFF' => 'off',
    'PREVIEW_EMAIL_PROBLEMS' => 'Some email definition files could not be used:',

    /* send box */
    'PREVIEW_EMAIL_SEND_HEADING' => 'Send a test',
    'PREVIEW_EMAIL_SEND_TO' => 'to',
    'PREVIEW_EMAIL_SEND_HELP' => 'Press Send Test on any row below. The format chosen here is used for that one send, whatever this address\'s stored preference is. Tests go through the store\'s own email settings, so what arrives is what a customer would get.',
    'PREVIEW_EMAIL_FORMAT_HTML' => 'HTML',
    'PREVIEW_EMAIL_FORMAT_TEXT' => 'Plain text',

    /* table */
    'PREVIEW_EMAIL_COL_EMAIL' => 'Email',
    'PREVIEW_EMAIL_COL_MODULE' => 'Module',
    'PREVIEW_EMAIL_COL_TEMPLATE' => 'Template file',
    'PREVIEW_EMAIL_COL_ACTIONS' => '',
    'PREVIEW_EMAIL_BTN_HTML' => 'Preview HTML',
    'PREVIEW_EMAIL_BTN_TEXT' => 'Preview text',
    'PREVIEW_EMAIL_BTN_SEND' => 'Send test',
    'PREVIEW_EMAIL_TEMPLATE_FALLBACK' => '(no template of its own; the default is used)',

    /* groups and generic entries */
    'PREVIEW_EMAIL_GROUP_CORE' => 'Zen Cart',
    'PREVIEW_EMAIL_GROUP_ADMIN' => 'Zen Cart admin notices',
    'PREVIEW_EMAIL_GROUP_OTHER_TEMPLATES' => 'Other templates found',
    'PREVIEW_EMAIL_GENERIC_LABEL' => 'Template: %s',
    'PREVIEW_EMAIL_GENERIC_DESCRIBE' => 'A template file with no matching definition. Previewed with generic sample content; placeholders it uses that nothing fills are listed at the top of the preview.',
    'PREVIEW_EMAIL_GENERIC_MESSAGE' => 'This is sample message content, standing in for whatever this email normally carries.',
    'PREVIEW_EMAIL_DEFAULT_SUBJECT' => 'Sample subject line',
    'PREVIEW_EMAIL_NOT_AVAILABLE' => 'Not available on this store.',

    /* preview banner */
    'PREVIEW_EMAIL_BANNER_EMAIL' => 'Email:',
    'PREVIEW_EMAIL_BANNER_SUBJECT' => 'Subject:',
    'PREVIEW_EMAIL_BANNER_TO' => 'To:',
    'PREVIEW_EMAIL_BANNER_TEMPLATE' => 'Template:',
    'PREVIEW_EMAIL_BANNER_NO_TEMPLATE' => 'none found',
    'PREVIEW_EMAIL_BANNER_FALLBACK' => '(the default template, because this email has none of its own)',
    'PREVIEW_EMAIL_BANNER_TEXT_PART' => 'plain-text part, as a TEXT-Only recipient receives it',
    'PREVIEW_EMAIL_BANNER_UNFILLED' => 'Placeholders in this template that nothing fills (they would appear in the sent email exactly like this):',
    'PREVIEW_EMAIL_BANNER_SKIPPED_LANG' => 'Language file %s was skipped, so some wording may be a fallback.',

    /* results */
    'PREVIEW_EMAIL_SENT' => 'Test of "%1$s" sent to %2$s as %3$s.',
    'PREVIEW_EMAIL_NOT_SENT' => 'The test of "%s" was not sent.',
    'PREVIEW_EMAIL_ERR_NO_EMAIL' => 'Choose an email first.',
    'PREVIEW_EMAIL_ERR_BAD_ADDRESS' => 'Enter a valid address to send the test to.',
    'PREVIEW_EMAIL_ERR_EMPTY_HTML' => 'No HTML could be built for this email. Its template file is missing, or the template directory could not be read.',
    'PREVIEW_EMAIL_ERR_SENDING_OFF' => 'Sending is turned off: Configuration > E-Mail Options > Send E-Mails is false.',
    'PREVIEW_EMAIL_ERR_HTML_OFF' => 'HTML email is turned off: Configuration > E-Mail Options > E-Mail Format is not HTML, so only a plain-text test can be sent.',
    'PREVIEW_EMAIL_ERR_MODULE_SKIPPED' => 'The %s email is listed in EMAIL_MODULES_TO_SKIP, so zen_mail() refuses to send it.',
    'PREVIEW_EMAIL_ERR_DECLINED' => 'zen_mail() declined to send. Check that the address is valid and that the recipient has not opted out (customers_email_format NONE or OUT).',

    /* notes the builders add */
    'PREVIEW_EMAIL_NOTE_NO_ORDERS' => 'This store has no orders yet, so the order shown is invented.',
    'PREVIEW_EMAIL_NOTE_EXTRA_COPY' => 'This is the copy the store receives, with the extra-info block core adds to admin copies.',
    'PREVIEW_EMAIL_NOTE_DIRECT_FILE' => 'Message body read from %s.',

    /* labels and descriptions of the built-in emails */
    'PREVIEW_EMAIL_LABEL_CHECKOUT' => 'Order confirmation (checkout)',
    'PREVIEW_EMAIL_DESC_CHECKOUT' => 'Sent to the customer the moment an order is placed. Built from one of your recent orders.',
    'PREVIEW_EMAIL_LABEL_CHECKOUT_EXTRA' => 'Order confirmation, store copy',
    'PREVIEW_EMAIL_DESC_CHECKOUT_EXTRA' => 'The copy sent to the address in Configuration > E-Mail Options > Send Extra Order Emails To.',
    'PREVIEW_EMAIL_LABEL_LOW_STOCK' => 'Low stock notice',
    'PREVIEW_EMAIL_DESC_LOW_STOCK' => 'Sent to the store after an order drops a product below its reorder level.',
    'PREVIEW_EMAIL_LABEL_ORDER_STATUS' => 'Order status update',
    'PREVIEW_EMAIL_DESC_ORDER_STATUS' => 'Sent to the customer when you change an order\'s status with "notify customer" ticked.',
    'PREVIEW_EMAIL_LABEL_ORDER_STATUS_EXTRA' => 'Order status update, store copy',
    'PREVIEW_EMAIL_DESC_ORDER_STATUS_EXTRA' => 'The copy sent to Send Extra Order Status Update Emails To.',
    'PREVIEW_EMAIL_LABEL_WELCOME' => 'Welcome (new account)',
    'PREVIEW_EMAIL_DESC_WELCOME' => 'Sent when a customer creates an account, including any new-account coupon or gift certificate you have configured.',
    'PREVIEW_EMAIL_LABEL_WELCOME_EXTRA' => 'Welcome, store copy',
    'PREVIEW_EMAIL_DESC_WELCOME_EXTRA' => 'The copy sent to Send Extra Create Account Emails To.',
    'PREVIEW_EMAIL_LABEL_PASSWORD_FORGOTTEN' => 'Password reset (customer)',
    'PREVIEW_EMAIL_DESC_PASSWORD_FORGOTTEN' => 'Sent from the storefront Password Forgotten page. Zen Cart 2.x sends a reset link; 1.5.8 sends a new password.',
    'PREVIEW_EMAIL_LABEL_CONTACT_US' => 'Contact Us',
    'PREVIEW_EMAIL_DESC_CONTACT_US' => 'What the store receives when a visitor uses the Contact Us page.',
    'PREVIEW_EMAIL_LABEL_ASK_A_QUESTION' => 'Ask a Question about a product',
    'PREVIEW_EMAIL_DESC_ASK_A_QUESTION' => 'What the store receives from the Ask a Question link on a product page. Uses the Contact Us template unless email_template_ask_a_question.html exists.',
    'PREVIEW_EMAIL_NA_ASK_A_QUESTION' => 'This Zen Cart release has no Ask a Question page.',
    'PREVIEW_EMAIL_LABEL_REVIEWS_EXTRA' => 'Review awaiting approval',
    'PREVIEW_EMAIL_DESC_REVIEWS_EXTRA' => 'Sent to the store when a customer writes a review and reviews need approval.',
    'PREVIEW_EMAIL_LABEL_COUPON' => 'Coupon mailing',
    'PREVIEW_EMAIL_DESC_COUPON' => 'Sent from Marketing > Coupon Admin > Email Coupon. Built from one of your coupons, or an invented one if there are none.',
    'PREVIEW_EMAIL_LABEL_COUPON_EXTRA' => 'Coupon mailing, store copy',
    'PREVIEW_EMAIL_DESC_COUPON_EXTRA' => 'The copy sent to Send Extra Discount Coupon Admin Emails To.',
    'PREVIEW_EMAIL_LABEL_GV_MAIL' => 'Gift certificate mailing',
    'PREVIEW_EMAIL_DESC_GV_MAIL' => 'Sent from Marketing > Gift Certificate Admin > Email Gift Certificate.',
    'PREVIEW_EMAIL_LABEL_GV_MAIL_EXTRA' => 'Gift certificate mailing, store copy',
    'PREVIEW_EMAIL_DESC_GV_MAIL_EXTRA' => 'The copy sent to Send Extra Gift Certificate Admin Emails To.',
    'PREVIEW_EMAIL_LABEL_GV_QUEUE' => 'Gift certificate released from the queue',
    'PREVIEW_EMAIL_DESC_GV_QUEUE' => 'Sent to the buyer when you release a purchased gift certificate in Marketing > Gift Certificate Queue.',
    'PREVIEW_EMAIL_LABEL_GV_SEND' => 'Gift certificate sent by a customer',
    'PREVIEW_EMAIL_DESC_GV_SEND' => 'What the recipient gets when a customer sends a gift certificate from their account.',
    'PREVIEW_EMAIL_LABEL_GV_SEND_EXTRA' => 'Gift certificate sent by a customer, store copy',
    'PREVIEW_EMAIL_DESC_GV_SEND_EXTRA' => 'The copy sent to Send Extra Gift Certificate Customer Emails To.',
    'PREVIEW_EMAIL_NA_GV' => 'The Gift Certificates order-total module is not installed (Modules > Order Total).',
    'PREVIEW_EMAIL_LABEL_DIRECT_EMAIL' => 'Direct email to a customer',
    'PREVIEW_EMAIL_DESC_DIRECT_EMAIL' => 'Sent from Tools > Send Email. To preview your own wording, put it in a file named direct_test.html in the catalog root.',
    'PREVIEW_EMAIL_LABEL_NEWSLETTERS' => 'Newsletter',
    'PREVIEW_EMAIL_DESC_NEWSLETTERS' => 'Sent from Tools > Newsletter and Product Notifications Manager, newsletter type.',
    'PREVIEW_EMAIL_LABEL_PRODUCT_NOTIFICATION' => 'Product notification',
    'PREVIEW_EMAIL_DESC_PRODUCT_NOTIFICATION' => 'Sent from the Newsletter Manager, product notification type, to customers watching a product.',
    'PREVIEW_EMAIL_LABEL_DEFAULT' => 'Default template (any other message)',
    'PREVIEW_EMAIL_DESC_DEFAULT' => 'The template every message without one of its own falls back to: download failures, payment module alerts, and most add-on emails.',
    'PREVIEW_EMAIL_LABEL_PASSWORD_FORGOTTEN_ADMIN' => 'Admin password reset',
    'PREVIEW_EMAIL_DESC_PASSWORD_FORGOTTEN_ADMIN' => 'Sent to an admin user who asks for a new password on the admin login page.',
    'PREVIEW_EMAIL_LABEL_ADMIN_MFA' => 'Admin sign-in verification code',
    'PREVIEW_EMAIL_DESC_ADMIN_MFA' => 'The multi-factor code emailed at admin sign-in (Zen Cart 2.0 and later). Sent with the no_archive module so it is never written to the email archive.',
    'PREVIEW_EMAIL_NA_MFA' => 'This Zen Cart release has no multi-factor sign-in.',
    'PREVIEW_EMAIL_LABEL_ADMIN_SETTINGS_CHANGED' => 'Admin account added, changed or deleted',
    'PREVIEW_EMAIL_DESC_ADMIN_SETTINGS_CHANGED' => 'Sent to the store owner whenever an admin user is created, edited or removed.',
    'PREVIEW_EMAIL_LABEL_PAYMENTALERT' => 'Payment module alert',
    'PREVIEW_EMAIL_DESC_PAYMENTALERT' => 'The alert a payment module sends the store owner when a transaction needs attention. The wording is the module\'s own; this shows the layout.',

    /* sample content used where the real wording is typed in by hand */
    'PREVIEW_EMAIL_SAMPLE_CONTACT_MESSAGE' => 'Hello, I have a question about my recent order. Could you let me know when it will ship? Thank you.',
    'PREVIEW_EMAIL_SAMPLE_QUESTION' => 'Does this come in other colors, and is it in stock right now?',
    'PREVIEW_EMAIL_SAMPLE_REVIEW' => 'Arrived quickly and works exactly as described. Would buy again.',
    'PREVIEW_EMAIL_SAMPLE_STATUS_COMMENT' => 'Your order has shipped. Tracking number: 1Z999AA10123456784.',
    'PREVIEW_EMAIL_SAMPLE_COUPON_SUBJECT' => 'A coupon for you',
    'PREVIEW_EMAIL_SAMPLE_COUPON_MESSAGE' => 'As a thank-you for being a customer, here is a coupon for your next order.',
    'PREVIEW_EMAIL_SAMPLE_GV_SUBJECT' => 'A gift certificate for you',
    'PREVIEW_EMAIL_SAMPLE_GV_MESSAGE' => 'Thank you for being a customer. Please enjoy this gift certificate on your next order.',
    'PREVIEW_EMAIL_SAMPLE_GV_RECIPIENT' => 'Jordan Reyes',
    'PREVIEW_EMAIL_SAMPLE_GV_SEND_MESSAGE' => 'Happy birthday! Pick out something you like.',
    'PREVIEW_EMAIL_SAMPLE_DIRECT_SUBJECT' => 'A note from us',
    'PREVIEW_EMAIL_SAMPLE_DIRECT_MESSAGE' => 'This is a sample direct email, the kind sent from Tools &gt; Send Email.',
    'PREVIEW_EMAIL_SAMPLE_NEWSLETTER_SUBJECT' => 'Our newsletter',
    'PREVIEW_EMAIL_SAMPLE_NEWSLETTER_MESSAGE' => '<p>This is a sample newsletter. Your own newsletter content appears here, wrapped in the newsletter template with its unsubscribe link at the bottom.</p>',
    'PREVIEW_EMAIL_SAMPLE_NOTIFICATION_SUBJECT' => 'News about a product you are watching',
    'PREVIEW_EMAIL_SAMPLE_NOTIFICATION_MESSAGE' => '<p>This is a sample product notification. The products you chose in the Newsletter Manager are described here.</p>',
    'PREVIEW_EMAIL_SAMPLE_DEFAULT_MESSAGE' => 'This is a sample message using the default email template.',
    'PREVIEW_EMAIL_SAMPLE_PAYMENTALERT_SUBJECT' => 'Payment alert',
    'PREVIEW_EMAIL_SAMPLE_PAYMENTALERT' => "Payment module alert\n\nA transaction for order 1001 needs attention.\n\nResponse: DECLINED (sample)\nAmount: 44.90\nTransaction ID: SAMPLE-TXN-0001",
];

return $define;
