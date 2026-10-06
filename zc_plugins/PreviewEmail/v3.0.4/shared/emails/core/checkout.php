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

if (!function_exists('preview_email_checkout_render_from_order_class')) {
    /**
     * Render the checkout email's product rows, product text and order-totals rows
     * from THIS store's own includes/classes/order.php, so a store that has
     * customized the confirmation email -- a different product-line format, the model
     * ahead of the name, and so on -- sees its real email in the preview instead of
     * the stock layout. Returns null (and the caller falls back to the stock-faithful
     * rendering) when the store's order.php matches stock (the common case: the proven
     * path is used and nothing is evaluated), when it cannot be read, or on any
     * extraction or evaluation problem.
     *
     * eval is used deliberately and narrowly: the only thing evaluated is a
     * string-concatenation expression lifted out of the store's own, already-trusted
     * includes/classes/order.php -- never any request input -- warnings are silenced
     * for its duration, and any Throwable returns null. A store whose order.php matches
     * stock never reaches it. tests/security_scan.php carries the matching exemption.
     *
     * @return array{html:string,text:string,totals_html:string}|null
     */
    function preview_email_checkout_render_from_order_class($order, $currencies): ?array
    {
        // The stock products_ordered_html block, whitespace-stripped, identical in
        // every supported release (Zen Cart 1.5.8-3.0.0). A store matching it is
        // unmodified, so we leave it to the stock-faithful rendering below.
        $stockProductsHtml = 'c46f152c85050e898811184491fc3fc2';

        if (!defined('DIR_FS_CATALOG')) {
            return null;
        }
        $file = DIR_FS_CATALOG . 'includes/classes/order.php';
        if (!is_file($file) || !is_readable($file)) {
            return null;
        }
        $src = file_get_contents($file);
        if ($src === false || $src === '') {
            return null;
        }

        // Anchor each grab on the terminating ; at end of line: an HTML entity such
        // as &nbsp; carries a ; of its own mid-expression.
        $grab = static function (string $pattern) use ($src): ?string {
            return preg_match($pattern, $src, $m) === 1 ? trim($m[1]) : null;
        };
        $rowExpr = $grab('~\$this->products_ordered_html\s*\.=(.*?);[ \t]*\r?\n~s');
        if ($rowExpr === null) {
            return null;
        }
        if (md5(preg_replace('~\s+~', '', $rowExpr)) === $stockProductsHtml) {
            return null; // unmodified -> stock-faithful rendering, no eval
        }

        $textExpr = $grab('~\$this->products_ordered\s*\.=(.*?);[ \t]*\r?\n~s');
        $attrExpr = $grab('~\$this->products_ordered_attributes\s*\.=\s*(.*?products_options_name.*?);[ \t]*\r?\n~s');
        $totHead = $grab('~\$html_ot\s*=\s*(.*?);[ \t]*\r?\n~s');
        $totRow = $grab('~\$html_ot\s*\.=\s*(.*?);[ \t]*\r?\n~s');
        if ($textExpr === null || $totHead === null || $totRow === null) {
            return null;
        }

        // Point the store's object references at the locals supplied below.
        $swap = static function (string $e): string {
            return str_replace(['$this->products[$i]', '$this->products_ordered_attributes'], ['$P', '$ATTR'], $e);
        };
        $rowExpr = $swap($rowExpr);
        $textExpr = $swap($textExpr);
        $totRow = str_replace('$order_totals[$i]', '$T', $totRow);
        if ($attrExpr !== null) {
            $attrExpr = str_replace(
                ["\$attributes_values->fields['products_options_name']", "\$this->products[\$i]['attributes'][\$j]['value']"],
                ['$OPTNAME', '$AVAL'],
                $attrExpr
            );
        }
        // Only a plain concatenation from order.php is evaluated; reject a backtick.
        foreach ([$rowExpr, $textExpr, $totHead, $totRow, (string)$attrExpr] as $e) {
            if (strpos($e, '`') !== false) {
                return null;
            }
        }

        $defaults = ['qty' => 0, 'name' => '', 'model' => '', 'final_price' => 0, 'tax' => 0, 'onetime_charges' => 0, 'attributes' => []];
        set_error_handler(static function (): bool {
            return true; // silence a key this store's stored order does not carry
        });
        try {
            $html = '';
            $text = '';
            foreach ((array)$order->products as $product) {
                $P = array_merge($defaults, (array)$product);
                $ATTR = '';
                if ($attrExpr !== null && !empty($P['attributes']) && is_array($P['attributes'])) {
                    foreach ($P['attributes'] as $a) {
                        $OPTNAME = (string)($a['option'] ?? '');
                        $AVAL = (string)($a['value'] ?? '');
                        $ATTR .= eval('return ' . $attrExpr . ';');
                    }
                }
                $html .= eval('return ' . $rowExpr . ';');
                $text .= eval('return ' . $textExpr . ';');
            }
            $totHtml = eval('return ' . $totHead . ';');
            foreach ((array)$order->totals as $T) {
                $totHtml .= eval('return ' . $totRow . ';');
            }
            $result = ['html' => $html, 'text' => $text, 'totals_html' => $totHtml];
        } catch (\Throwable $e) {
            $result = null;
        } finally {
            restore_error_handler();
        }
        return $result;
    }
}

if (!function_exists('preview_email_checkout_capture')) {
    /**
     * Opt-in "exact send-path" render: run this store's own
     * order::send_order_email() for a stored order and capture exactly what it
     * would send, with every send suppressed by the capture observer. Returns
     * ['text' => string, 'html' => array (the HTML block array)], or null if
     * nothing was captured (e.g. the order could not be driven).
     *
     * No eval and no scraping. The product rows are built from the store's own
     * order method when core provides it (buildProductOrderedEmailStrings, the
     * read-only method proposed in zencart/zencart#8015), else from the stock
     * layout; everything else -- intro, totals, payment, disclaimer, and any
     * customization inside send_order_email() itself -- comes straight from core.
     *
     * The delivery/billing address and the payment line are overwritten from the
     * stored order afterwards, because send_order_email() sources them from the
     * live address book via the checkout session, which a stored order lacks.
     */
    function preview_email_checkout_capture($order, int $oID, $currencies): ?array
    {
        if (!is_object($order) || empty($order->products) || !method_exists($order, 'send_order_email')) {
            return null;
        }

        // send_order_email() uses raw base-catalog language constants (e.g.
        // HEADING_ADDRESS_INFORMATION, PAYMENT_METHOD_GV) that the admin preview
        // context does not load on its own. Pull in the main catalog language so
        // those constants exist; the per-page checkout_process/email_extras files
        // the builder already loaded fill in the rest.
        if (function_exists('preview_email_load_language_file') && defined('DIR_FS_CATALOG')) {
            preview_email_load_language_file(DIR_FS_CATALOG . 'includes/languages/', 'english');
        }

        // 1) Build products_ordered_html / products_ordered with no side effects.
        $order->products_ordered = '';
        $order->products_ordered_html = '';
        $useMethod = method_exists($order, 'buildProductOrderedEmailStrings');
        foreach ((array)$order->products as $product) {
            $p = array_merge(['qty' => 0, 'name' => '', 'model' => '', 'final_price' => 0, 'tax' => 0, 'onetime_charges' => 0, 'attributes' => []], (array)$product);
            $attributes = '';
            if (!empty($p['attributes']) && is_array($p['attributes'])) {
                foreach ($p['attributes'] as $a) {
                    $attributes .= "\n\t" . ($a['option'] ?? '') . ' ' . zen_decode_specialchars((string)($a['value'] ?? ''));
                }
            }
            if ($useMethod) {
                $line = $order->buildProductOrderedEmailStrings($p, $attributes, $currencies);
                $order->products_ordered .= $line['text'];
                $order->products_ordered_html .= $line['html'];
                continue;
            }
            $model = (string)$p['model'];
            $onetime = (float)$p['onetime_charges'];
            $order->products_ordered_html .=
                '<tr>' . "\n" .
                '<td class="product-details" align="right" valign="top" width="30">' . $p['qty'] . '&nbsp;x</td>' . "\n" .
                '<td class="product-details" valign="top">' . nl2br((string)$p['name']) . ($model != '' ? ' (' . nl2br($model) . ') ' : '') .
                (!empty($attributes) ? "\n" . '<nobr>' . '<small><em>' . nl2br($attributes) . '</em></small>' . '</nobr>' : '') .
                '</td>' . "\n" .
                '<td class="product-details-num" valign="top" align="right">' .
                $currencies->display_price($p['final_price'], $p['tax'], $p['qty']) . '</td>' . "\n" . '</tr>' . "\n" .
                ($onetime != 0 ?
                    '<tr>' . "\n" . '<td class="product-details" colspan="2">' . nl2br(TEXT_ONETIME_CHARGES_EMAIL) . '</td>' . "\n" .
                    '<td valign="top" align="right">' . $currencies->display_price($onetime, $p['tax'], 1) . '</td>' . "\n" . '</tr>' . "\n" : '');
            $order->products_ordered .=
                $p['qty'] . ' x ' . (string)$p['name'] . ($model != '' ? ' (' . $model . ') ' : '') . ' = ' .
                $currencies->display_price($p['final_price'], $p['tax'], $p['qty']) .
                ($onetime != 0 ? "\n" . TEXT_ONETIME_CHARGES_EMAIL . $currencies->display_price($onetime, $p['tax'], 1) : '') .
                $attributes . "\n";
        }

        // 2) Fabricate the global + session values send_order_email() reads,
        //    saving the real ones so the admin request is left untouched.
        global $order_totals;
        $savedTotals = $order_totals ?? null;
        $order_totals = [];
        foreach ((array)$order->totals as $t) {
            $order_totals[] = ['title' => $t['title'] ?? '', 'text' => $t['text'] ?? ''];
        }
        $savedSession = [];
        foreach (['customer_id', 'sendto', 'billto', 'payment'] as $k) {
            $savedSession[$k] = $_SESSION[$k] ?? null;
        }
        $_SESSION['customer_id'] = (int)($order->customer['id'] ?? 0);
        $_SESSION['sendto'] = null;   // addresses are overwritten from the stored order below
        $_SESSION['billto'] = null;
        $_SESSION['payment'] = '';    // falls through to the plain payment title

        $restore = static function () use (&$order_totals, $savedTotals, $savedSession) {
            $order_totals = $savedTotals;
            foreach ($savedSession as $k => $v) {
                if ($v === null) { unset($_SESSION[$k]); } else { $_SESSION[$k] = $v; }
            }
        };

        // A loaded order's customer/info arrays do not carry every key that
        // checkout-time send_order_email() reads; backfill the ones it needs so
        // running it against a stored order neither warns nor fails.
        if (!isset($order->customer['firstname']) || !isset($order->customer['lastname'])) {
            $nm = trim((string)($order->customer['name'] ?? ''));
            $sp = strrpos($nm, ' ');
            $order->customer['firstname'] = ($sp === false) ? $nm : substr($nm, 0, $sp);
            $order->customer['lastname'] = ($sp === false) ? '' : substr($nm, $sp + 1);
        }
        if (!isset($order->customer['telephone'])) { $order->customer['telephone'] = ''; }
        if (!isset($order->customer['email_address'])) { $order->customer['email_address'] = ''; }
        if (!isset($order->info['comments'])) { $order->info['comments'] = ''; }
        if (!isset($order->info['shipping_module_code'])) { $order->info['shipping_module_code'] = ''; }
        if (!isset($order->info['shipping_method'])) { $order->info['shipping_method'] = ''; }
        if (!isset($order->content_type)) { $order->content_type = 'physical'; }

        // 3) Run the real send path with sends suppressed, then read the capture.
        //    Running core against a loaded order raises benign undefined-key
        //    notices (a stored order's arrays differ from the checkout-time
        //    shape, and the addresses it computes are discarded below in favor
        //    of the stored order's), so silence notices for the duration rather
        //    than fill the store's debug log on every preview. A real failure
        //    still throws and is caught, falling back to the standard preview.
        preview_email_capture_arm();
        set_error_handler(static function (): bool {
            return true;
        });
        try {
            $order->send_order_email($oID);
        } catch (\Throwable $e) {
            restore_error_handler();
            preview_email_capture_disarm();
            $restore();
            return null;
        }
        restore_error_handler();
        $captured = preview_email_capture_result();
        preview_email_capture_disarm();
        $restore();

        if ($captured === null) {
            return null;
        }

        // 4) Correct the address + payment blocks from the stored order.
        $html = $captured['html'];
        $html['ADDRESS_DELIVERY_DETAIL'] = (empty($order->delivery) || !is_array($order->delivery))
            ? 'n/a'
            : zen_address_format($order->delivery['format_id'], $order->delivery, 1, '', '<br>');
        if (!empty($order->billing) && is_array($order->billing)) {
            $html['ADDRESS_BILLING_DETAIL'] = zen_address_format($order->billing['format_id'], $order->billing, 1, '', '<br>');
        }
        if (!empty($order->info['payment_method'])) {
            $html['PAYMENT_METHOD_DETAIL'] = (string)$order->info['payment_method'];
        }
        return ['text' => $captured['text'], 'html' => $html];
    }
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

            // Opt-in exact view: render precisely what order::send_order_email()
            // would send for this stored order by running it and capturing the
            // result (no mail is sent). Falls through to the standard build if
            // capture is not requested or could not be produced.
            if (!$fake && function_exists('preview_email_exact_capture_requested') && preview_email_exact_capture_requested() && function_exists('preview_email_checkout_capture')) {
                $captured = preview_email_checkout_capture($order, (int)$oID, $currencies);
                if ($captured !== null) {
                    $notes[] = preview_email_const('PREVIEW_EMAIL_NOTE_EXACT_CAPTURE', 'This preview was produced by running this store\'s own order-email code and capturing exactly what it would send; no email was sent.');
                    return [
                        'subject' => preview_email_const('EMAIL_TEXT_SUBJECT', 'Order Confirmation') . preview_email_const('EMAIL_ORDER_NUMBER_SUBJECT', ' No: ') . $oID,
                        'text' => $captured['text'],
                        'block' => $captured['html'],
                        'to_name' => (string)($order->customer['name'] ?? ''),
                        'to_email' => (string)($order->customer['email_address'] ?? ''),
                        'notes' => $notes,
                    ];
                }
                $notes[] = preview_email_const('PREVIEW_EMAIL_NOTE_EXACT_CAPTURE_FAILED', 'The exact send-path preview could not be produced for this order, so the standard preview is shown instead.');
            }

            $name = $fake ? $fake['name'] : (string)$order->customer['name'];
            $parts = preg_split('/\s+/', trim($name)) ?: [''];
            $lastName = (string)array_pop($parts);
            $firstName = trim(implode(' ', $parts));

            $invoiceUrl = zen_catalog_href_link(defined('FILENAME_ACCOUNT_HISTORY_INFO') ? FILENAME_ACCOUNT_HISTORY_INFO : 'account_history_info', 'order_id=' . $oID, 'SSL', false);
            $dateOrdered = (isset($zcDate) && is_object($zcDate)) ? $zcDate->output(preview_email_const('DATE_FORMAT_LONG', '%A %d %B, %Y')) : date('l d F, Y');

            // Products and totals, byte-for-byte the markup order::send_order_email()
            // builds (its products_ordered_html / products_ordered / $html_ot blocks,
            // and the products_ordered_attributes format from create_add_products()).
            // These are identical in every supported release; tests/core_parity_check.php
            // pins them to the live order class so a core change is caught, not shown
            // to the store owner as something the customer never actually receives.
            $productsHtml = '';
            $productsText = '';
            $totalsHtml = '<tr><td class="order-totals-text" align="right" width="100%">' . '&nbsp;' . '</td> ' . "\n" . '<td class="order-totals-num" align="right" nowrap="nowrap">' . '---------' . '</td> </tr>' . "\n";
            $totalsText = '';
            if ($fake) {
                foreach ($fake['products'] as $p) {
                    $productsHtml .= '<tr><td class="product-details" align="right" valign="top" width="30">' . $p['qty'] . '&nbsp;x</td>'
                        . '<td class="product-details" valign="top">' . $p['name'] . ' (' . $p['model'] . ')</td>'
                        . '<td class="product-details-num" valign="top" align="right">' . $p['price'] . '</td></tr>' . "\n";
                    $productsText .= $p['qty'] . ' x ' . $p['name'] . ' (' . $p['model'] . ') = ' . $p['price'] . "\n";
                }
                foreach ($fake['totals'] as $t) {
                    $totalsHtml .= '<tr><td class="order-totals-text" align="right" width="100%">' . $t['title'] . '</td> ' . "\n" . '<td class="order-totals-num" align="right" nowrap="nowrap">' . $t['text'] . '</td> </tr>' . "\n";
                    $totalsText .= $t['title'] . ' ' . $t['text'] . "\n";
                }
            } else {
                $live = function_exists('preview_email_checkout_render_from_order_class')
                    ? preview_email_checkout_render_from_order_class($order, $currencies)
                    : null;
                if ($live !== null) {
                    // This store's order.php builds the email differently from stock;
                    // show its real output rather than the stock layout.
                    $productsHtml = $live['html'];
                    $productsText = $live['text'];
                    $totalsHtml = $live['totals_html'];
                    foreach ($order->totals as $t) {
                        $totalsText .= strip_tags((string)$t['title']) . ' ' . strip_tags((string)$t['text']) . "\n";
                    }
                    $notes[] = preview_email_const('PREVIEW_EMAIL_NOTE_FROM_ORDER_CLASS', 'This store\'s includes/classes/order.php builds the order email differently from stock Zen Cart, so the product and totals rows shown here are rendered from your order.php, not the stock layout.');
                } else {
                    foreach ($order->products as $p) {
                        // Attributes exactly as order::create_add_products() accumulates
                        // products_ordered_attributes: "\n\t" . option name . ' ' . value.
                        $attributes = '';
                        if (!empty($p['attributes']) && is_array($p['attributes'])) {
                            foreach ($p['attributes'] as $a) {
                                $attributes .= "\n\t" . $a['option'] . ' ' . zen_decode_specialchars((string)$a['value']);
                            }
                        }
                        $model = (string)$p['model'];
                        $onetime = (float)($p['onetime_charges'] ?? 0);

                        $productsHtml .=
                            '<tr>' . "\n" .
                            '<td class="product-details" align="right" valign="top" width="30">' . $p['qty'] . '&nbsp;x</td>' . "\n" .
                            '<td class="product-details" valign="top">' . nl2br((string)$p['name']) . ($model != '' ? ' (' . nl2br($model) . ') ' : '') .
                            (!empty($attributes) ? "\n" . '<nobr>' . '<small><em>' . nl2br($attributes) . '</em></small>' . '</nobr>' : '') .
                            '</td>' . "\n" .
                            '<td class="product-details-num" valign="top" align="right">' .
                            $currencies->display_price($p['final_price'], $p['tax'], $p['qty']) . '</td>' . "\n" . '</tr>' . "\n" .
                            ($onetime != 0 ?
                                '<tr>' . "\n" . '<td class="product-details" colspan="2">' . nl2br(TEXT_ONETIME_CHARGES_EMAIL) . '</td>' . "\n" .
                                '<td valign="top" align="right">' . $currencies->display_price($onetime, $p['tax'], 1) . '</td>' . "\n" . '</tr>' . "\n" : '');

                        $productsText .=
                            $p['qty'] . ' x ' . (string)$p['name'] . ($model != '' ? ' (' . $model . ') ' : '') . ' = ' .
                            $currencies->display_price($p['final_price'], $p['tax'], $p['qty']) .
                            ($onetime != 0 ? "\n" . TEXT_ONETIME_CHARGES_EMAIL . $currencies->display_price($onetime, $p['tax'], 1) : '') .
                            $attributes . "\n";
                    }
                    foreach ($order->totals as $t) {
                        $totalsHtml .= '<tr><td class="order-totals-text" align="right" width="100%">' . $t['title'] . '</td> ' . "\n" . '<td class="order-totals-num" align="right" nowrap="nowrap">' . ($t['text']) . '</td> </tr>' . "\n";
                        $totalsText .= strip_tags((string)$t['title']) . ' ' . strip_tags((string)$t['text']) . "\n";
                    }
                }
            }

            $comments = '';
            if (!$fake && defined('TABLE_ORDERS_STATUS_HISTORY')) {
                $h = $db->Execute("SELECT comments FROM " . TABLE_ORDERS_STATUS_HISTORY . " WHERE orders_id = " . (int)$oID . " ORDER BY date_added ASC LIMIT 1");
                $comments = $h->EOF ? '' : (string)$h->fields['comments'];
            }

            if ($fake) {
                $delivery = $fake['address'];
            } elseif (empty($order->delivery) || !is_array($order->delivery)) {
                // Pickup, virtual and other no-ship orders: the order class sets
                // ->delivery to false (order::__construct), and send_order_email()
                // shows 'n/a' for exactly these. Match that, rather than
                // dereferencing a missing address and blanking the page.
                $delivery = 'n/a';
            } else {
                $delivery = zen_address_format($order->delivery['format_id'], $order->delivery, 1, '', '<br>');
            }
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
