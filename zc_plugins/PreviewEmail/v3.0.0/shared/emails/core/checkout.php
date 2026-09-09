<?php
/**
 * Preview Email -- Zen Cart's order emails: the customer's confirmation, the
 * store's copy of it, and the low-stock notice sent alongside.
 *
 * Built from a recent real order through the admin order class, the way
 * order::send_order_email() builds the live one. A store with no orders yet
 * gets an invented order so the layout can still be seen.
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
        'key' => 'checkout',
        'group' => $previewEmailGroupCore,
        'sort' => 10,
        'label' => preview_email_const('PREVIEW_EMAIL_LABEL_CHECKOUT', 'Order Confirmation (Checkout)'),
        'describe' => preview_email_const('PREVIEW_EMAIL_DESC_CHECKOUT', 'Sent to the customer the moment an order is placed. Built from one of your recent orders.'),
        'module' => 'checkout',
        'page_base' => 'checkout_process',
        'build' => static function (array $def): array {
            global $db, $zcDate;

            preview_email_load_catalog_language('checkout_process');
            preview_email_load_catalog_language('email_extras');
            preview_email_load_catalog_language('english');
            $currencies = preview_email_currencies();

            $notes = [];
            $oID = preview_email_sample_order_id();
            $order = null;
            if ($oID > 0) {
                require_once DIR_WS_CLASSES . 'order.php';
                $order = new order($oID);
            }
            if ($order === null || empty($order->customer)) {
                $notes[] = preview_email_const('PREVIEW_EMAIL_NOTE_NO_ORDERS', 'This store has no orders yet, so the order shown is invented.');
                $customer = preview_email_sample_customer();
                $oID = 1001;
                $fake = [
                    'name' => $customer['name'],
                    'email' => $customer['email'],
                    'telephone' => $customer['telephone'],
                    'address' => $customer['name'] . '<br>1 Sample Street<br>Sampletown, TX 78701<br>United States',
                    'shipping' => 'Flat Rate (Best Way)',
                    'payment' => 'Sample Payment',
                    'products' => [['qty' => 2, 'name' => 'Sample Product', 'model' => 'SAMPLE-1', 'price' => '$19.95']],
                    'totals' => [['title' => 'Sub-Total:', 'text' => '$39.90'], ['title' => 'Flat Rate (Best Way):', 'text' => '$5.00'], ['title' => 'Total:', 'text' => '$44.90']],
                    'comments' => '',
                ];
            } else {
                $fake = null;
            }

            $name = $fake ? $fake['name'] : (string)$order->customer['name'];
            $parts = preg_split('/\s+/', trim($name)) ?: [''];
            $lastName = (string)array_pop($parts);
            $firstName = trim(implode(' ', $parts));

            $invoiceUrl = zen_catalog_href_link(defined('FILENAME_ACCOUNT_HISTORY_INFO') ? FILENAME_ACCOUNT_HISTORY_INFO : 'account_history_info', 'order_id=' . $oID, 'SSL', false);
            $dateOrdered = (isset($zcDate) && is_object($zcDate)) ? $zcDate->output(preview_email_const('DATE_FORMAT_LONG', '%A %d %B, %Y')) : date('l d F, Y');

            // Products and totals, in the same markup the live email carries.
            $productsHtml = '';
            $productsText = '';
            $totalsHtml = '<tr><td class="order-totals-text" align="right" width="100%">&nbsp;</td><td class="order-totals-num" align="right" nowrap="nowrap">---------</td></tr>' . "\n";
            $totalsText = '';
            if ($fake) {
                foreach ($fake['products'] as $p) {
                    $productsHtml .= '<tr><td class="product-details" align="right" valign="top" width="30">' . $p['qty'] . '&nbsp;x</td>'
                        . '<td class="product-details" valign="top">' . $p['name'] . ' (' . $p['model'] . ')</td>'
                        . '<td class="product-details-num" valign="top" align="right">' . $p['price'] . '</td></tr>' . "\n";
                    $productsText .= $p['qty'] . ' x ' . $p['name'] . ' (' . $p['model'] . ') = ' . $p['price'] . "\n";
                }
                foreach ($fake['totals'] as $t) {
                    $totalsHtml .= '<tr><td class="order-totals-text" align="right" width="100%">' . $t['title'] . '</td><td class="order-totals-num" align="right" nowrap="nowrap">' . $t['text'] . '</td></tr>' . "\n";
                    $totalsText .= $t['title'] . ' ' . $t['text'] . "\n";
                }
            } else {
                foreach ($order->products as $p) {
                    $attributes = '';
                    if (!empty($p['attributes']) && is_array($p['attributes'])) {
                        foreach ($p['attributes'] as $a) {
                            $attributes .= '<br><nobr><small>&nbsp;<i> - ' . $a['option'] . ': ' . nl2br(zen_output_string_protected((string)$a['value']));
                            if ((float)$a['price'] != 0) {
                                $attributes .= ' (' . $a['prefix'] . $currencies->format((float)$a['price'] * (int)$p['qty'], true, $order->info['currency'], $order->info['currency_value']) . ')';
                            }
                            $attributes .= '</i></small></nobr>';
                            $productsText .= '   - ' . $a['option'] . ': ' . $a['value'] . "\n";
                        }
                    }
                    $price = $currencies->display_price($p['final_price'], $p['tax'], $p['qty']);
                    $productsHtml .= '<tr>' . "\n"
                        . '<td class="product-details" align="right" valign="top" width="30">' . $p['qty'] . '&nbsp;x</td>' . "\n"
                        . '<td class="product-details" valign="top">' . nl2br((string)$p['name']) . ($p['model'] !== '' ? ' (' . nl2br((string)$p['model']) . ') ' : '') . "\n"
                        . '<nobr><small><em> ' . nl2br($attributes) . '</em></small></nobr></td>' . "\n"
                        . '<td class="product-details-num" valign="top" align="right">' . $price . '</td></tr>' . "\n";
                    $productsText = $p['qty'] . ' x ' . $p['name'] . ($p['model'] !== '' ? ' (' . $p['model'] . ')' : '') . ' = ' . strip_tags((string)$price) . "\n" . $productsText;
                }
                foreach ($order->totals as $t) {
                    $totalsHtml .= '<tr><td class="order-totals-text" align="right" width="100%">' . $t['title'] . '</td><td class="order-totals-num" align="right" nowrap="nowrap">' . $t['text'] . '</td></tr>' . "\n";
                    $totalsText .= strip_tags((string)$t['title']) . ' ' . strip_tags((string)$t['text']) . "\n";
                }
            }

            $comments = '';
            if (!$fake && defined('TABLE_ORDERS_STATUS_HISTORY')) {
                $h = $db->Execute("SELECT comments FROM " . TABLE_ORDERS_STATUS_HISTORY . " WHERE orders_id = " . (int)$oID . " ORDER BY date_added ASC LIMIT 1");
                $comments = $h->EOF ? '' : (string)$h->fields['comments'];
            }

            $delivery = $fake ? $fake['address'] : zen_address_format($order->delivery['format_id'], $order->delivery, 1, '', '<br>');
            $billing = $fake ? $fake['address'] : zen_address_format($order->billing['format_id'], $order->billing, 1, '', '<br>');
            $shipping = $fake ? $fake['shipping'] : ((string)($order->info['shipping_method'] ?? '') !== '' ? (string)$order->info['shipping_method'] : 'n/a');
            $payment = $fake ? $fake['payment'] : (string)($order->info['payment_method'] ?? '');
            $phone = $fake ? $fake['telephone'] : (string)($order->customer['telephone'] ?? '');
            $email = $fake ? $fake['email'] : (string)($order->customer['email_address'] ?? '');

            $subject = preview_email_const('EMAIL_TEXT_SUBJECT', 'Order Confirmation') . preview_email_const('EMAIL_ORDER_NUMBER_SUBJECT', ' No: ') . $oID;

            $block = [
                'EMAIL_SALUTATION' => preview_email_const('EMAIL_SALUTATION', 'Dear'),
                'EMAIL_FIRST_NAME' => $firstName,
                'EMAIL_LAST_NAME' => $lastName,
                'INTRO_URL_TEXT' => preview_email_const('EMAIL_TEXT_INVOICE_URL_CLICK', 'Click here to view your order'),
                'INTRO_URL_VALUE' => $invoiceUrl,
                'EMAIL_TEXT_HEADER' => preview_email_const('EMAIL_TEXT_HEADER', 'Order Confirmation'),
                'EMAIL_TEXT_FROM' => preview_email_const('EMAIL_TEXT_FROM', ''),
                'INTRO_STORE_NAME' => preview_email_const('STORE_NAME'),
                'EMAIL_THANKS_FOR_SHOPPING' => preview_email_const('EMAIL_THANKS_FOR_SHOPPING', 'Thanks for shopping with us today!'),
                'EMAIL_DETAILS_FOLLOW' => preview_email_const('EMAIL_DETAILS_FOLLOW', 'The following are the details of your order.'),
                'INTRO_ORDER_NUM_TITLE' => preview_email_const('EMAIL_TEXT_ORDER_NUMBER', 'Order Number:'),
                'INTRO_ORDER_NUMBER' => (string)$oID,
                'INTRO_DATE_TITLE' => preview_email_const('EMAIL_TEXT_DATE_ORDERED', 'Date Ordered:'),
                'INTRO_DATE_ORDERED' => $dateOrdered,
                'PRODUCTS_TITLE' => preview_email_const('EMAIL_TEXT_PRODUCTS', 'Products'),
                'PRODUCTS_DETAIL' => '<table class="product-details" border="0" width="100%" cellspacing="0" cellpadding="2">' . $productsHtml . '</table>',
                'ORDER_TOTALS' => '<table border="0" width="100%" cellspacing="0" cellpadding="2"> ' . $totalsHtml . ' </table>',
                'ORDER_COMMENTS' => nl2br(zen_output_string_protected($comments)),
                'HEADING_ADDRESS_INFORMATION' => preview_email_const('HEADING_ADDRESS_INFORMATION', 'Address Information'),
                'ADDRESS_DELIVERY_TITLE' => preview_email_const('EMAIL_TEXT_DELIVERY_ADDRESS', 'Delivery Address'),
                'ADDRESS_DELIVERY_DETAIL' => $delivery,
                'ADDRESS_BILLING_TITLE' => preview_email_const('EMAIL_TEXT_BILLING_ADDRESS', 'Billing Address'),
                'ADDRESS_BILLING_DETAIL' => $billing,
                'SHIPPING_METHOD_TITLE' => preview_email_const('HEADING_SHIPPING_METHOD', 'Shipping Method'),
                'SHIPPING_METHOD_DETAIL' => $shipping,
                'PAYMENT_METHOD_TITLE' => preview_email_const('EMAIL_TEXT_PAYMENT_METHOD', 'Payment Method'),
                'PAYMENT_METHOD_DETAIL' => $payment,
                'PAYMENT_METHOD_FOOTER' => '',
                'EMAIL_TEXT_TELEPHONE' => preview_email_const('EMAIL_TEXT_TELEPHONE', 'Telephone:'),
                'EMAIL_CUSTOMER_PHONE' => $phone,
                'EMAIL_ORDER_MESSAGE' => preview_email_const('EMAIL_ORDER_MESSAGE', ''),
            ];

            $text =
                $block['EMAIL_TEXT_HEADER'] . "\n\n"
                . $block['EMAIL_SALUTATION'] . ' ' . $name . ",\n\n"
                . $block['EMAIL_THANKS_FOR_SHOPPING'] . "\n" . $block['EMAIL_DETAILS_FOLLOW'] . "\n\n"
                . $block['INTRO_ORDER_NUM_TITLE'] . ' ' . $oID . "\n"
                . $block['INTRO_DATE_TITLE'] . ' ' . $dateOrdered . "\n"
                . $block['INTRO_URL_TEXT'] . ' ' . str_replace('&amp;', '&', $invoiceUrl) . "\n\n"
                . $block['PRODUCTS_TITLE'] . "\n" . preview_email_const('EMAIL_SEPARATOR', '------------------------------------------------------') . "\n"
                . $productsText . "\n" . $totalsText . "\n"
                . ($comments !== '' ? $comments . "\n\n" : '')
                . $block['ADDRESS_DELIVERY_TITLE'] . "\n" . strip_tags(str_replace('<br>', "\n", $delivery)) . "\n"
                . $block['EMAIL_TEXT_TELEPHONE'] . ' ' . $phone . "\n\n"
                . $block['SHIPPING_METHOD_TITLE'] . "\n" . $shipping . "\n\n"
                . $block['ADDRESS_BILLING_TITLE'] . "\n" . strip_tags(str_replace('<br>', "\n", $billing)) . "\n\n"
                . $block['PAYMENT_METHOD_TITLE'] . "\n" . $payment . "\n\n"
                . strip_tags((string)$block['EMAIL_ORDER_MESSAGE']);

            return [
                'subject' => $subject,
                'text' => $text,
                'block' => $block,
                'to_name' => $name,
                'to_email' => $email,
                'notes' => $notes,
            ];
        },
    ],
    [
        'key' => 'checkout_extra',
        'group' => $previewEmailGroupCore,
        'sort' => 11,
        'label' => preview_email_const('PREVIEW_EMAIL_LABEL_CHECKOUT_EXTRA', 'Order Confirmation, Store Copy'),
        'describe' => preview_email_const('PREVIEW_EMAIL_DESC_CHECKOUT_EXTRA', 'The copy sent to the address in Configuration > E-Mail Options > Send Extra Order Emails To.'),
        'module' => 'checkout_extra',
        'page_base' => 'checkout_process',
        'build' => preview_email_extra_builder('checkout'),
    ],
    [
        'key' => 'low_stock',
        'group' => $previewEmailGroupCore,
        'sort' => 12,
        'label' => preview_email_const('PREVIEW_EMAIL_LABEL_LOW_STOCK', 'Low Stock Notice'),
        'describe' => preview_email_const('PREVIEW_EMAIL_DESC_LOW_STOCK', 'Sent to the store after an order drops a product below its reorder level.'),
        'module' => 'low_stock',
        'page_base' => 'checkout_process',
        'build' => static function (array $def): array {
            preview_email_load_catalog_language('checkout_process');
            preview_email_load_catalog_language('english');
            $product = preview_email_sample_product();
            $line = preview_email_const('EMAIL_TEXT_STOCK_LEVEL', 'Product Stock Level') . ': ' . $product['name'] . ' (' . $product['model'] . ') ' . preview_email_const('EMAIL_TEXT_QUANTITY', 'Quantity remaining:') . ' 1';
            $body = preview_email_const('SEND_EXTRA_LOW_STOCK_EMAIL_TITLE', 'Low stock notice') . "\n\n" . $line . "\n";
            return [
                'subject' => preview_email_const('EMAIL_TEXT_SUBJECT_LOWSTOCK', 'Low Stock Notice'),
                'text' => $body,
                'block' => ['EMAIL_MESSAGE_HTML' => nl2br($body)],
                'to_name' => '',
                'to_email' => preview_email_const('SEND_EXTRA_LOW_STOCK_EMAILS_TO', preview_email_const('STORE_OWNER_EMAIL_ADDRESS')),
                'from_name' => preview_email_const('STORE_OWNER'),
            ];
        },
    ],
];
