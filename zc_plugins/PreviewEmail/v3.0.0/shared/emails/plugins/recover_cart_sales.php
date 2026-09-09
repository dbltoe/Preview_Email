<?php
/**
 * Preview Email -- the abandoned cart reminders sent by Recover Cart Sales,
 * both the stock single message and the three-message drip variant some
 * stores run. Carried over from Preview Email 2.x.
 *
 * Listed only when the store has the Recover Cart Sales language file, or
 * the drip templates.
 *
 * @package  PreviewEmail
 * @license  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

$previewEmailRcsLang = is_file(DIR_FS_CATALOG . 'includes/languages/' . ($_SESSION['language'] ?? 'english') . '/recover_cart_sales.php')
    || is_file(DIR_FS_CATALOG . 'includes/languages/' . ($_SESSION['language'] ?? 'english') . '/lang.recover_cart_sales.php')
    || is_file(DIR_FS_ADMIN . 'includes/languages/' . ($_SESSION['language'] ?? 'english') . '/recover_cart_sales.php')
    || is_file(DIR_FS_ADMIN . 'includes/languages/' . ($_SESSION['language'] ?? 'english') . '/lang.recover_cart_sales.php');
$previewEmailRcsDrip = !preview_email_resolve_template('abandoned_cart_1', 'recover_cart_sales')['fallback'];
if (!$previewEmailRcsLang && !$previewEmailRcsDrip) {
    return [];
}

/** The products in a recent abandoned basket, or an invented basket. */
$previewEmailRcsBasket = static function (): array {
    global $db;
    $customer = preview_email_sample_customer();
    $items = [];
    if (isset($db) && defined('TABLE_CUSTOMERS_BASKET') && defined('TABLE_PRODUCTS') && defined('TABLE_PRODUCTS_DESCRIPTION')) {
        $r = $db->Execute("SELECT customers_id FROM " . TABLE_CUSTOMERS_BASKET . " ORDER BY customers_basket_date_added DESC LIMIT " . (int)PREVIEW_EMAIL_LOOKBACK);
        if (!$r->EOF) {
            $cid = (int)preview_email_advance($r)->fields['customers_id'];
            $c = $db->Execute("SELECT customers_firstname, customers_lastname, customers_email_address FROM " . TABLE_CUSTOMERS . " WHERE customers_id = " . (int)$cid . " LIMIT 1");
            if (!$c->EOF) {
                $customer = ['id' => $cid, 'firstname' => (string)$c->fields['customers_firstname'], 'lastname' => (string)$c->fields['customers_lastname'], 'name' => trim($c->fields['customers_firstname'] . ' ' . $c->fields['customers_lastname']), 'email' => (string)$c->fields['customers_email_address'], 'telephone' => '', 'real' => true];
            }
            $b = $db->Execute("SELECT cb.products_id, cb.customers_basket_quantity, p.products_model, p.products_image, pd.products_name FROM " . TABLE_CUSTOMERS_BASKET . " cb JOIN " . TABLE_PRODUCTS . " p ON p.products_id = cb.products_id JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON pd.products_id = p.products_id AND pd.language_id = " . (int)($_SESSION['languages_id'] ?? 1) . " WHERE cb.customers_id = " . $cid . " ORDER BY cb.customers_basket_date_added DESC");
            $seen = [];
            while (!$b->EOF) {
                $pid = (int)$b->fields['products_id'];
                if (!isset($seen[$pid])) {
                    $seen[$pid] = true;
                    $items[] = ['id' => $pid, 'qty' => (int)$b->fields['customers_basket_quantity'], 'name' => (string)$b->fields['products_name'], 'model' => (string)$b->fields['products_model'], 'image' => (string)$b->fields['products_image']];
                }
                $b->MoveNext();
            }
        }
    }
    if ($items === []) {
        $p = preview_email_sample_product();
        $items[] = ['id' => $p['id'], 'qty' => 1, 'name' => $p['name'], 'model' => $p['model'], 'image' => $p['image']];
    }
    return ['customer' => $customer, 'items' => $items];
};

$previewEmailRcsLoad = static function (): void {
    preview_email_load_admin_language('recover_cart_sales');
    preview_email_load_catalog_language('recover_cart_sales');
};

$defs = [];

if ($previewEmailRcsLang && !$previewEmailRcsDrip) {
    $defs[] = [
        'key' => 'abandoned_cart',
        'group' => 'Recover Cart Sales',
        'sort' => 10,
        'label' => 'Abandoned cart reminder',
        'describe' => 'Sent from Reports > Recover Cart Sales to a customer who left items in the cart.',
        'module' => 'recover_cart_sales',
        'page_base' => 'recover_cart_sales',
        'build' => static function (array $def) use ($previewEmailRcsBasket, $previewEmailRcsLoad): array {
            $previewEmailRcsLoad();
            $basket = $previewEmailRcsBasket();
            $customer = $basket['customer'];
            $lines = '';
            foreach ($basket['items'] as $item) {
                $url = zen_catalog_href_link('product_info', 'products_id=' . $item['id']);
                $lines .= $item['qty'] . ' x ' . $item['name'] . "\n" . '   <blockquote><a href="' . $url . '">' . $url . "</a></blockquote>\n\n";
            }
            $storeUrl = zen_catalog_href_link('index');
            $email = (preview_email_const('RCS_EMAIL_FRIENDLY') === 'true')
                ? preview_email_const('EMAIL_TEXT_SALUTATION', 'Dear ') . $customer['name'] . ','
                : preview_email_const('STORE_NAME') . "\n" . preview_email_const('EMAIL_SEPARATOR', '------------------------------------------------------') . "\n";
            $email .= sprintf(preview_email_const('EMAIL_TEXT_CURCUST_INTRO', "\n\nWe noticed you left these items in your cart:\n\n%s"), $lines);
            $email .= preview_email_const('EMAIL_TEXT_BODY_HEADER', '') . $lines . preview_email_const('EMAIL_TEXT_BODY_FOOTER', '');
            $email .= '<a href="' . $storeUrl . '">' . preview_email_const('STORE_OWNER') . "\n" . $storeUrl . '</a>' . "\n\n\n" . preview_email_const('EMAIL_SEPARATOR', '') . "\n\n";
            $email .= preview_email_const('EMAIL_TEXT_LOGIN', 'Log in here:') . '  <a href="' . zen_catalog_href_link('login', '', 'SSL') . '">' . zen_catalog_href_link('login', '', 'SSL') . '</a>';
            $message = 'This is a sample personal note added on the Recover Cart Sales page.';
            return [
                'subject' => preview_email_const('EMAIL_TEXT_SUBJECT', 'Your shopping cart at ' . preview_email_const('STORE_NAME')),
                'text' => strip_tags($email . "\n\n" . $message),
                'block' => ['EMAIL_MESSAGE_HTML' => nl2br($email) . '<p>' . $message . '</p>'],
                'to_name' => $customer['name'],
                'to_email' => $customer['email'],
            ];
        },
    ];
}

if ($previewEmailRcsDrip) {
    foreach ([1, 2, 3] as $n) {
        if ($n > 1 && preview_email_resolve_template('abandoned_cart_' . $n, 'recover_cart_sales')['fallback']) {
            continue;
        }
        $defs[] = [
            'key' => 'abandoned_cart_' . $n,
            'group' => 'Recover Cart Sales',
            'sort' => 10 + $n,
            'label' => 'Abandoned cart reminder ' . $n,
            'describe' => 'Message ' . $n . ' of the drip sequence.' . ($n === 3 ? ' The third message carries a contact block instead of the cart.' : ''),
            'module' => 'abandoned_cart_' . $n,
            'page_base' => 'recover_cart_sales',
            'build' => static function (array $def) use ($n, $previewEmailRcsBasket, $previewEmailRcsLoad): array {
                $previewEmailRcsLoad();
                $basket = $previewEmailRcsBasket();
                $customer = $basket['customer'];
                $rows = '';
                $text = '';
                foreach ($basket['items'] as $item) {
                    $url = zen_catalog_href_link('product_info', 'products_id=' . $item['id']);
                    $image = ($item['image'] !== '') ? '<img style="max-width:100%;" src="' . HTTP_CATALOG_SERVER . DIR_WS_CATALOG . 'images/' . $item['image'] . '" alt="' . zen_output_string_protected($item['name']) . '">' : '';
                    $rows .= '<tr><td width="50%"><a href="' . $url . '">' . $image . '</a></td><td><a href="' . $url . '">' . $item['name'] . '</a></td></tr>';
                    $text .= $item['name'] . ': ' . str_replace('&amp;', '&', $url) . "\n\n";
                }
                $header = $customer['firstname'] . preview_email_const('EMAIL_MESSAGE_' . $n, ', we saved your cart for you.');
                $html = nl2br($header) . '<table border="0" width="100%" cellspacing="0" cellpadding="2"> ' . $rows . ' </table>';
                if ($n === 3) {
                    $html = nl2br($header) . preview_email_const('CONTACT_BLOCK_3', '');
                    $text = strip_tags(preview_email_const('CONTACT_BLOCK_3', ''));
                }
                return [
                    'subject' => preview_email_const('EMAIL_TEXT_SUBJECT', 'Your shopping cart at ' . preview_email_const('STORE_NAME')),
                    'text' => $header . "\n\n" . $text,
                    'block' => ['EMAIL_MESSAGE_HTML' => $html, 'EMAIL_ATTENTION' => preview_email_const('ATTENTION_' . $n, '')],
                    'to_name' => $customer['name'],
                    'to_email' => $customer['email'],
                ];
            },
        ];
    }
}

return $defs;
