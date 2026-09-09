<?php
/**
 * Preview Email -- shared function library.
 *
 * Everything the admin page, the observer and the email definitions need,
 * in one file that every entry point loads with a `require_once __DIR__`
 * path. Nothing here relies on Zen Cart's plugin auto-loaders, because the
 * set of directories those loaders read changed between v1.5.8 and v3.0.0
 * (v3.0.0 no longer loads a plugin's admin extra_functions at all).
 *
 * Function prefix: preview_email_
 *
 * @package  PreviewEmail
 * @license  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

if (!defined('PREVIEW_EMAIL_LOOKBACK')) {
    /** How many recent customers/orders/products to pick a sample from. */
    define('PREVIEW_EMAIL_LOOKBACK', 20);
}

/* ------------------------------------------------------------------ *
 * Locating things
 * ------------------------------------------------------------------ */

/**
 * This plugin's version directory, with a trailing slash.
 */
function preview_email_plugin_dir(): string
{
    return str_replace('\\', '/', dirname(__DIR__)) . '/';
}

/**
 * The version directory of an installed, enabled zc_plugin, or '' when it
 * is not installed. Read from the `$installedPlugins` array both admin and
 * storefront bootstraps build, which is keyed by the plugin's unique_key on
 * every release from v1.5.8 to v3.0.0.
 */
function preview_email_installed_plugin_dir(string $uniqueKey): string
{
    $installed = $GLOBALS['installedPlugins'] ?? [];
    if (!is_array($installed) || !isset($installed[$uniqueKey]['version'])) {
        return '';
    }
    $dir = DIR_FS_CATALOG . 'zc_plugins/' . $uniqueKey . '/' . $installed[$uniqueKey]['version'] . '/';
    return is_dir($dir) ? $dir : '';
}

/**
 * The store's email template directory, plus any extra directories a plugin
 * registers on v3.0.0 through NOTIFY_EMAIL_REGISTER_ADDITIONAL_TEMPLATE_DIRS.
 * Earlier releases have no such notifier; firing it there simply finds no
 * listener, so the list is the one core directory.
 *
 * @return string[] directories, each with a trailing slash, in search order
 */
function preview_email_template_roots(string $module = 'default'): array
{
    $extra = [];
    if (isset($GLOBALS['zco_notifier']) && is_object($GLOBALS['zco_notifier']) && method_exists($GLOBALS['zco_notifier'], 'notify')) {
        $GLOBALS['zco_notifier']->notify(
            'NOTIFY_EMAIL_REGISTER_ADDITIONAL_TEMPLATE_DIRS',
            ['module' => $module, 'langfolder' => preview_email_lang_folder(), 'content' => []],
            $extra
        );
    }
    $roots = [];
    foreach ((array)$extra as $dir) {
        if (!is_string($dir) || $dir === '') {
            continue;
        }
        // Core only honors paths inside the catalog root; mirror that.
        if (strpos($dir, DIR_FS_CATALOG) !== 0) {
            continue;
        }
        $roots[] = rtrim($dir, '/') . '/';
    }
    $roots[] = DIR_FS_EMAIL_TEMPLATES;
    return $roots;
}

/**
 * The per-language subfolder core prepends to template names: '' for
 * English, 'xx/' otherwise.
 */
function preview_email_lang_folder(): string
{
    $code = strtolower((string)($_SESSION['languages_code'] ?? 'en'));
    return ($code === 'en' || $code === '') ? '' : $code . '/';
}

/**
 * Which template file core will use for a given module, page base and
 * block -- the same search order as zen_build_html_email_from_template()
 * on every supported release (the `_extra` / `_admin` suffix is stripped,
 * then the page base is tried, then EMAIL_TEMPLATE_FILENAME, then default).
 *
 * @return array{path: string, name: string, fallback: bool}  'name' is the
 *   part after email_template_; 'fallback' is true when the default template
 *   is what will be used because nothing specific exists.
 */
function preview_email_resolve_template(string $module, string $pageBase = '', array $block = []): array
{
    $lang = preview_email_lang_folder();
    $base = str_replace(['_extra', '_admin'], '', $module);
    if ($pageBase === '') {
        $pageBase = $module;
    }
    // EMAIL_TEMPLATE_FILENAME: a full path on 1.5.8-2.3 (the caller supplies
    // it), root-relative on 3.0.0. Both forms are tried.
    $named = !empty($block['EMAIL_TEMPLATE_FILENAME']) ? (string)$block['EMAIL_TEMPLATE_FILENAME'] . '.html' : '';
    $patterns = [
        $lang . 'email_template_' . $base . '.html',
        'email_template_' . $base . '.html',
        $lang . 'email_template_' . $pageBase . '.html',
        'email_template_' . $pageBase . '.html',
        $named,
        $lang . 'email_template_default.html',
        'email_template_default.html',
    ];
    foreach (preview_email_template_roots($module) as $root) {
        foreach ($patterns as $pattern) {
            if ($pattern === '') {
                continue;
            }
            $path = ($pattern === $named && is_file($named)) ? $named : $root . $pattern;
            if (is_file($path)) {
                $name = preg_replace('~^.*email_template_~', '', basename($path));
                $name = (string)preg_replace('~\.html$~', '', (string)$name);
                return [
                    'path' => $path,
                    'name' => $name,
                    'fallback' => ($name === 'default' && $base !== 'default' && $pageBase !== 'default'),
                ];
            }
        }
    }
    return ['path' => '', 'name' => '', 'fallback' => true];
}

/**
 * Which stylesheet core will pour into $EMAIL_COMMON_CSS for a module: the
 * first email_common.css found, language subfolder first, in the same
 * template roots as the template itself.
 *
 * @return array{path: string, relative: string}  both '' when none exists
 */
function preview_email_resolve_stylesheet(string $module = 'default'): array
{
    $lang = preview_email_lang_folder();
    foreach (preview_email_template_roots($module) as $root) {
        foreach ([$lang . 'email_common.css', 'email_common.css'] as $pattern) {
            if ($pattern === '' || !is_file($root . $pattern)) {
                continue;
            }
            $path = $root . $pattern;
            return ['path' => $path, 'relative' => preview_email_relative_path($path)];
        }
    }
    return ['path' => '', 'relative' => ''];
}

/**
 * A path relative to the catalog root, for display.
 */
function preview_email_relative_path(string $path): string
{
    $path = str_replace('\\', '/', $path);
    $root = str_replace('\\', '/', DIR_FS_CATALOG);
    return (strpos($path, $root) === 0) ? substr($path, strlen($root)) : $path;
}

/**
 * Where else styling comes from, beyond the stylesheet: rules the template
 * carries in its own <style> blocks (apart from the $EMAIL_COMMON_CSS
 * placeholder), and inline style attributes in the template and in the
 * message content the sending code supplies.
 *
 * @return array{template_rules: bool, template_inline: int, content_inline: int}
 */
function preview_email_css_facts(string $templatePath, array $block): array
{
    $template = ($templatePath !== '' && is_file($templatePath)) ? (string)file_get_contents($templatePath) : '';
    $rules = false;
    if (preg_match_all('~<style\b[^>]*>(.*?)</style>~is', $template, $m)) {
        foreach ($m[1] as $css) {
            $css = trim(str_replace('$EMAIL_COMMON_CSS', '', $css));
            if ($css !== '') {
                $rules = true;
            }
        }
    }
    $content = '';
    foreach ($block as $value) {
        if (is_string($value)) {
            $content .= $value . "\n";
        }
    }
    return [
        'template_rules' => $rules,
        'template_inline' => (int)preg_match_all('~\sstyle\s*=\s*["\']~i', $template),
        'content_inline' => (int)preg_match_all('~\sstyle\s*=\s*["\']~i', $content),
    ];
}

/**
 * Every email_template_*.html in the template directories, by short name.
 *
 * @return array<string, string>  name => path
 */
function preview_email_template_files(): array
{
    $found = [];
    foreach (preview_email_template_roots() as $root) {
        foreach ((array)glob($root . 'email_template_*.html') as $path) {
            $name = (string)preg_replace('~\.html$~', '', substr(basename($path), strlen('email_template_')));
            if ($name !== '' && !isset($found[$name])) {
                $found[$name] = $path;
            }
        }
    }
    ksort($found);
    return $found;
}

/**
 * The $PLACEHOLDER names a template file refers to.
 *
 * @return string[]
 */
function preview_email_template_placeholders(string $path): array
{
    if ($path === '' || !is_file($path)) {
        return [];
    }
    $html = (string)file_get_contents($path);
    if (preg_match_all('~\$([A-Z][A-Z0-9_]*)~', $html, $m) === false) {
        return [];
    }
    $names = array_values(array_unique($m[1]));
    sort($names);
    return $names;
}

/* ------------------------------------------------------------------ *
 * Language files
 * ------------------------------------------------------------------ */

/**
 * Load a language file into constants without going through Zen Cart's
 * language loader.
 *
 * Tries the array form first (`lang.<name>.php`, which returns or sets
 * `$define`), then the legacy define() form (`<name>.php`), in the given
 * directory and, for storefront files, in the template override folder
 * beneath it. Constants already defined are left alone, so a store's own
 * override always wins over a value loaded later.
 *
 * `$dir` is the language directory itself, e.g.
 *   DIR_FS_CATALOG . 'includes/languages/english/'
 *
 * @return bool  true when at least one file was found
 */
function preview_email_load_language_file(string $dir, string $name, string $subdir = ''): bool
{
    $dir = rtrim($dir, '/') . '/';
    $candidates = [];
    $template = (string)($GLOBALS['template_dir'] ?? '');
    $sub = ($subdir !== '') ? rtrim($subdir, '/') . '/' : '';
    // A constant cannot be redefined, so whichever file is read first wins.
    // The template override is therefore read FIRST and the base file then
    // fills in whatever the override did not set -- the same net result as
    // core's loader, reached from the other direction.
    if ($template !== '') {
        $candidates[] = $dir . $sub . $template . '/lang.' . $name . '.php';
        $candidates[] = $dir . $sub . $template . '/' . $name . '.php';
    }
    $candidates[] = $dir . $sub . 'lang.' . $name . '.php';
    $candidates[] = $dir . $sub . $name . '.php';

    $found = false;
    foreach ($candidates as $file) {
        if (!is_file($file)) {
            continue;
        }
        $found = true;
        if (strpos(basename($file), 'lang.') === 0) {
            preview_email_define_from_array_file($file);
        } else {
            preview_email_include_define_file($file);
        }
    }
    return $found;
}

/**
 * Include a `lang.*.php` file and turn its array into constants.
 *
 * Core's own loader accepts both shapes those files take: a `return [...]`
 * and a `$define = [...]` with no return. A key that is already a constant
 * is skipped, never redefined.
 */
function preview_email_define_from_array_file(string $file): void
{
    $define = [];
    $returned = include $file;
    if (is_array($returned)) {
        $define = $returned;
    }
    if (!is_array($define)) {
        return;
    }
    foreach ($define as $key => $value) {
        if (is_string($key) && $key !== '' && !defined($key) && (is_scalar($value) || $value === null)) {
            define($key, $value);
        }
    }
}

/**
 * Include a legacy define() language file -- the form older add-ons still
 * ship. Such files define() without checking, and a redefinition is a fatal
 * error, so the file is scanned first: if any constant it defines already
 * exists the whole file is skipped and noted, since there is no way to
 * include half of it. Core's own files have been arrays since v1.5.8, so
 * this path only ever sees third-party language files.
 */
function preview_email_include_define_file(string $file): void
{
    $src = (string)file_get_contents($file);
    if (preg_match_all('~define\s*\(\s*([\'"])([A-Za-z_][A-Za-z0-9_]*)\1\s*,~', $src, $m) < 1) {
        return;
    }
    foreach ($m[2] as $name) {
        if (defined($name)) {
            $GLOBALS['preview_email_skipped_language_files'][] = basename($file) . ' (' . $name . ' already defined)';
            return;
        }
    }
    include $file;
}

/**
 * Load a storefront language file (`includes/languages/<lang>/`), for the
 * current session language, honoring the template override folder.
 */
function preview_email_load_catalog_language(string $name, string $subdir = ''): bool
{
    $language = (string)($_SESSION['language'] ?? 'english');
    $dir = DIR_FS_CATALOG . 'includes/languages/' . $language . '/';
    $ok = preview_email_load_language_file($dir, $name, $subdir);
    if (!$ok && $language !== 'english') {
        $ok = preview_email_load_language_file(DIR_FS_CATALOG . 'includes/languages/english/', $name, $subdir);
    }
    return $ok;
}

/**
 * Load an admin language file (`<admin>/includes/languages/<lang>/`).
 */
function preview_email_load_admin_language(string $name, string $subdir = ''): bool
{
    $language = (string)($_SESSION['language'] ?? 'english');
    $dir = DIR_FS_ADMIN . 'includes/languages/' . $language . '/';
    $ok = preview_email_load_language_file($dir, $name, $subdir);
    if (!$ok && $language !== 'english') {
        $ok = preview_email_load_language_file(DIR_FS_ADMIN . 'includes/languages/english/', $name, $subdir);
    }
    return $ok;
}

/**
 * Load a language file that ships inside another plugin's version directory.
 *
 * `$relative` is the path below the version directory up to and including the
 * language directory, e.g. 'admin/includes/languages/', and `$name` the file
 * name without `lang.`/`.php`.
 */
function preview_email_load_plugin_language(string $uniqueKey, string $relative, string $name, string $subdir = ''): bool
{
    $plugin = preview_email_installed_plugin_dir($uniqueKey);
    if ($plugin === '') {
        return false;
    }
    $language = (string)($_SESSION['language'] ?? 'english');
    $dir = $plugin . trim($relative, '/') . '/' . $language . '/';
    $ok = preview_email_load_language_file($dir, $name, $subdir);
    if (!$ok && $language !== 'english') {
        $ok = preview_email_load_language_file($plugin . trim($relative, '/') . '/english/', $name, $subdir);
    }
    return $ok;
}

/** constant() with a fallback, so a definition never fatals on a missing string. */
function preview_email_const(string $name, string $fallback = ''): string
{
    return defined($name) ? (string)constant($name) : $fallback;
}

/**
 * Core's extra-info block for the store copies of an email, built in admin
 * context.
 *
 * email_collect_extra_info() reads two storefront session keys, the
 * customer's IP and host address, that never exist in an admin session, and
 * PHP 8 warns on each read. They are supplied for the duration of the call
 * (the admin's own address stands in for the customer's) and removed again,
 * so nothing is left in the admin session.
 *
 * @return array{HTML: string, TEXT: string}
 */
function preview_email_extra_info(string $from, string $emailFrom, string $login, string $loginEmail, string $phone = ''): array
{
    if (!function_exists('email_collect_extra_info')) {
        return ['HTML' => '', 'TEXT' => ''];
    }
    $added = [];
    foreach (['customers_ip_address' => (string)($_SERVER['REMOTE_ADDR'] ?? ''), 'customers_host_address' => ''] as $key => $value) {
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = $value;
            $added[] = $key;
        }
    }
    $extra = email_collect_extra_info($from, $emailFrom, $login, $loginEmail, $phone);
    foreach ($added as $key) {
        unset($_SESSION[$key]);
    }
    return ['HTML' => (string)($extra['HTML'] ?? ''), 'TEXT' => (string)($extra['TEXT'] ?? '')];
}

/**
 * Is a module one core treats as non-transactional (and so appends the
 * disclaimers to the text part)? The same list on 1.5.8, 2.x and 3.0.0;
 * on 3.0.0 it is a private method of the Email class, so it is mirrored
 * here rather than called.
 */
function preview_email_is_non_transactional(string $module): bool
{
    if (function_exists('zen_is_non_transactional_email')) {
        return (bool)zen_is_non_transactional_email($module);
    }
    return in_array($module, ['newsletters', 'product_notification', 'direct_email', 'coupon', 'gv_mail', 'welcome'], true);
}

/**
 * The currencies object: the admin page's own when it exists, a new one
 * from the admin classes directory when that exists, and otherwise a
 * stand-in that formats numbers plainly so a preview never fatals.
 *
 * @return object with format() and display_price()
 */
function preview_email_currencies(): object
{
    global $currencies;
    if (isset($currencies) && is_object($currencies)) {
        return $currencies;
    }
    if (!class_exists('currencies') && defined('DIR_WS_CLASSES') && is_file(DIR_WS_CLASSES . 'currencies.php')) {
        require_once DIR_WS_CLASSES . 'currencies.php';
    }
    if (class_exists('currencies')) {
        $currencies = new currencies();
        return $currencies;
    }
    return new class {
        public function format($number, $calculate = true, $currency = '', $value = ''): string
        {
            return '$' . number_format((float)$number, 2);
        }

        public function display_price($price, $tax, $qty = 1): string
        {
            return '$' . number_format((float)$price * (float)$qty, 2);
        }
    };
}

/* ------------------------------------------------------------------ *
 * Sample data
 * ------------------------------------------------------------------ */

/**
 * Walk a result set forward a random number of rows (at most the lookback
 * count minus one), so repeated previews show different real records.
 */
function preview_email_advance(object $result): object
{
    $max = min(PREVIEW_EMAIL_LOOKBACK, (int)$result->RecordCount()) - 1;
    if ($max < 1) {
        return $result;
    }
    $steps = random_int(0, $max);
    while ($steps > 0 && !$result->EOF) {
        $result->MoveNext();
        $steps--;
    }
    if ($result->EOF) {
        $result->MoveFirst();
    }
    return $result;
}

/**
 * A recent customer, or an invented one when the store has none.
 *
 * @return array{id: int, firstname: string, lastname: string, name: string, email: string, telephone: string, real: bool}
 */
function preview_email_sample_customer(): array
{
    global $db;
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $sample = [
        'id' => 0,
        'firstname' => 'Sample',
        'lastname' => 'Customer',
        'name' => 'Sample Customer',
        'email' => 'sample.customer@example.com',
        'telephone' => '555-0100',
        'real' => false,
    ];
    if (isset($db) && defined('TABLE_CUSTOMERS')) {
        $r = $db->Execute(
            "SELECT customers_id, customers_firstname, customers_lastname, customers_email_address, customers_telephone
               FROM " . TABLE_CUSTOMERS . "
              ORDER BY customers_id DESC
              LIMIT " . (int)PREVIEW_EMAIL_LOOKBACK
        );
        if (!$r->EOF) {
            $r = preview_email_advance($r);
            $sample = [
                'id' => (int)$r->fields['customers_id'],
                'firstname' => (string)$r->fields['customers_firstname'],
                'lastname' => (string)$r->fields['customers_lastname'],
                'name' => trim($r->fields['customers_firstname'] . ' ' . $r->fields['customers_lastname']),
                'email' => (string)$r->fields['customers_email_address'],
                'telephone' => (string)$r->fields['customers_telephone'],
                'real' => true,
            ];
        }
    }
    return $cache = $sample;
}

/**
 * A recent order id, or 0 when the store has none. Prefers orders with a
 * status history, which the order-status email needs.
 */
function preview_email_sample_order_id(bool $withHistory = false): int
{
    global $db;
    if (!isset($db) || !defined('TABLE_ORDERS')) {
        return 0;
    }
    if ($withHistory && defined('TABLE_ORDERS_STATUS_HISTORY')) {
        $r = $db->Execute(
            "SELECT o.orders_id, COUNT(osh.orders_status_history_id) AS n
               FROM " . TABLE_ORDERS . " o
                    LEFT JOIN " . TABLE_ORDERS_STATUS_HISTORY . " osh ON osh.orders_id = o.orders_id
              GROUP BY o.orders_id
             HAVING n > 1
              ORDER BY o.orders_id DESC
              LIMIT " . (int)PREVIEW_EMAIL_LOOKBACK
        );
        if (!$r->EOF) {
            return (int)preview_email_advance($r)->fields['orders_id'];
        }
    }
    $r = $db->Execute("SELECT orders_id FROM " . TABLE_ORDERS . " ORDER BY orders_id DESC LIMIT " . (int)PREVIEW_EMAIL_LOOKBACK);
    if ($r->EOF) {
        return 0;
    }
    return (int)preview_email_advance($r)->fields['orders_id'];
}

/**
 * A recent active product.
 *
 * @return array{id: int, name: string, model: string, image: string, real: bool}
 */
function preview_email_sample_product(): array
{
    global $db;
    $sample = ['id' => 0, 'name' => 'Sample Product', 'model' => 'SAMPLE-1', 'image' => '', 'real' => false];
    if (!isset($db) || !defined('TABLE_PRODUCTS') || !defined('TABLE_PRODUCTS_DESCRIPTION')) {
        return $sample;
    }
    $r = $db->Execute(
        "SELECT p.products_id, p.products_model, p.products_image, pd.products_name
           FROM " . TABLE_PRODUCTS . " p
                JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON pd.products_id = p.products_id
                     AND pd.language_id = " . (int)($_SESSION['languages_id'] ?? 1) . "
          WHERE p.products_status = 1
          ORDER BY p.products_last_modified DESC, p.products_id DESC
          LIMIT " . (int)PREVIEW_EMAIL_LOOKBACK
    );
    if ($r->EOF) {
        return $sample;
    }
    $r = preview_email_advance($r);
    return [
        'id' => (int)$r->fields['products_id'],
        'name' => (string)$r->fields['products_name'],
        'model' => (string)$r->fields['products_model'],
        'image' => (string)$r->fields['products_image'],
        'real' => true,
    ];
}

/**
 * A coupon, preferring active ones, or an invented one.
 *
 * @return array{code: string, name: string, description: string, start: string, expire: string, real: bool}
 */
function preview_email_sample_coupon(): array
{
    global $db;
    $sample = [
        'code' => 'SAMPLE10',
        'name' => 'Sample coupon',
        'description' => '10% off your next order',
        'start' => date('Y-m-d'),
        'expire' => date('Y-m-d', strtotime('+30 days')),
        'real' => false,
    ];
    if (!isset($db) || !defined('TABLE_COUPONS') || !defined('TABLE_COUPONS_DESCRIPTION')) {
        return $sample;
    }
    $sql = "SELECT c.coupon_code, c.coupon_start_date, c.coupon_expire_date, cd.coupon_name, cd.coupon_description
              FROM " . TABLE_COUPONS . " c
                   JOIN " . TABLE_COUPONS_DESCRIPTION . " cd ON cd.coupon_id = c.coupon_id
                        AND cd.language_id = " . (int)($_SESSION['languages_id'] ?? 1) . "
             WHERE c.coupon_type <> 'G'";
    $order = " ORDER BY c.coupon_id DESC LIMIT " . (int)PREVIEW_EMAIL_LOOKBACK;
    $r = $db->Execute($sql . " AND c.coupon_active = 'Y'" . $order);
    if ($r->EOF) {
        $r = $db->Execute($sql . $order);
    }
    if ($r->EOF) {
        return $sample;
    }
    $r = preview_email_advance($r);
    return [
        'code' => (string)$r->fields['coupon_code'],
        'name' => (string)$r->fields['coupon_name'],
        'description' => (string)$r->fields['coupon_description'],
        'start' => (string)$r->fields['coupon_start_date'],
        'expire' => (string)$r->fields['coupon_expire_date'],
        'real' => true,
    ];
}

/* ------------------------------------------------------------------ *
 * Definitions
 * ------------------------------------------------------------------ */

/**
 * The definition sources, in the order they are read. A later definition
 * with the same key replaces an earlier one, so a store's own file under the
 * admin directory can override anything a plugin ships.
 *
 * @return array<int, array{dir: string, source: string, group: string}>
 */
function preview_email_definition_sources(): array
{
    $sources = [
        ['dir' => preview_email_plugin_dir() . 'shared/emails/core/', 'source' => 'core', 'group' => ''],
        ['dir' => preview_email_plugin_dir() . 'shared/emails/plugins/', 'source' => 'bundled', 'group' => ''],
    ];
    $installed = $GLOBALS['installedPlugins'] ?? [];
    if (is_array($installed)) {
        ksort($installed);
        foreach ($installed as $key => $info) {
            if (!is_string($key) || $key === 'PreviewEmail' || !isset($info['version'])) {
                continue;
            }
            $dir = DIR_FS_CATALOG . 'zc_plugins/' . $key . '/' . $info['version'] . '/email_preview/';
            if (is_dir($dir)) {
                $sources[] = ['dir' => $dir, 'source' => 'plugin:' . $key, 'group' => (string)($info['name'] ?? $key)];
            }
        }
    }
    $site = DIR_FS_ADMIN . 'includes/email_preview/';
    if (is_dir($site)) {
        $sources[] = ['dir' => $site, 'source' => 'site', 'group' => preview_email_const('STORE_NAME', 'This store')];
    }
    return $sources;
}

/**
 * Read one definition file. It must `return` either a single definition or
 * a list of them. Anything else is reported and skipped rather than fatal.
 *
 * @return array<int, array<string, mixed>>
 */
function preview_email_read_definition_file(string $file, string $source, string $defaultGroup, array &$problems): array
{
    $result = include $file;
    if (!is_array($result)) {
        $problems[] = basename($file) . ' (' . $source . ') did not return an array';
        return [];
    }
    // A single definition rather than a list of them.
    if (isset($result['key'])) {
        $result = [$result];
    }
    $out = [];
    foreach ($result as $i => $def) {
        if (!is_array($def)) {
            $problems[] = basename($file) . ' (' . $source . ') entry ' . $i . ' is not an array';
            continue;
        }
        $key = (string)($def['key'] ?? '');
        if ($key === '' || preg_match('~^[a-z0-9_]+$~', $key) !== 1) {
            $problems[] = basename($file) . ' (' . $source . ') entry ' . $i . ' has no valid key (a-z, 0-9, _)';
            continue;
        }
        if (!isset($def['build']) || !is_callable($def['build'])) {
            $problems[] = basename($file) . ' (' . $source . ') "' . $key . '" has no build callable';
            continue;
        }
        $def['label'] = (string)($def['label'] ?? $key);
        $def['module'] = (string)($def['module'] ?? $key);
        $def['page_base'] = (string)($def['page_base'] ?? '');
        $def['group'] = (string)($def['group'] ?? $defaultGroup);
        if ($def['group'] === '') {
            $def['group'] = ucfirst($source);
        }
        $def['describe'] = (string)($def['describe'] ?? '');
        $def['sort'] = (int)($def['sort'] ?? 500);
        $def['source'] = $source;
        $def['file'] = $file;
        $out[] = $def;
    }
    return $out;
}

/**
 * Every email definition the store has, keyed by definition key, with the
 * template each will use resolved, plus a generic entry for every template
 * file nothing else claims.
 *
 * @param string[] $problems  filled with human-readable notes about bad files
 * @return array<string, array<string, mixed>>
 */
function preview_email_definitions(array &$problems = []): array
{
    static $cache = null;
    static $cacheProblems = [];
    if ($cache !== null) {
        $problems = $cacheProblems;
        return $cache;
    }
    $defs = [];
    foreach (preview_email_definition_sources() as $src) {
        foreach ((array)glob($src['dir'] . '*.php') as $file) {
            foreach (preview_email_read_definition_file($file, $src['source'], $src['group'], $problems) as $def) {
                $defs[$def['key']] = $def;
            }
        }
    }

    // Which template names are spoken for.
    $claimed = [];
    foreach ($defs as $key => $def) {
        $t = preview_email_resolve_template($def['module'], $def['page_base']);
        $defs[$key]['template'] = $t;
        if (!$t['fallback'] && $t['name'] !== '') {
            $claimed[$t['name']] = true;
        }
    }
    $claimed['default'] = true;

    // Everything else in email/ gets a generic entry so it can still be seen.
    foreach (preview_email_template_files() as $name => $path) {
        if (isset($claimed[$name])) {
            continue;
        }
        $key = 'template_' . preg_replace('~[^a-z0-9_]~', '_', strtolower($name));
        if (isset($defs[$key])) {
            continue;
        }
        $defs[$key] = [
            'key' => $key,
            'label' => sprintf(preview_email_const('PREVIEW_EMAIL_GENERIC_LABEL', 'Template: %s'), $name),
            'module' => $name,
            'page_base' => '',
            'group' => preview_email_const('PREVIEW_EMAIL_GROUP_OTHER_TEMPLATES', 'Other templates found'),
            'describe' => preview_email_const('PREVIEW_EMAIL_GENERIC_DESCRIBE', 'A template file with no matching definition. Previewed with generic sample content; placeholders it uses that nothing fills are listed at the top of the preview.'),
            'sort' => 900,
            'source' => 'template',
            'file' => $path,
            'build' => 'preview_email_build_generic',
            'template' => ['path' => $path, 'name' => $name, 'fallback' => false],
        ];
    }

    uasort($defs, static function (array $a, array $b): int {
        if ($a['group'] !== $b['group']) {
            return strcmp($a['group'], $b['group']);
        }
        if ($a['sort'] !== $b['sort']) {
            return $a['sort'] <=> $b['sort'];
        }
        return strcmp($a['label'], $b['label']);
    });

    $cacheProblems = $problems;
    return $cache = $defs;
}

/**
 * Group order for the page: Zen Cart first, this store's own last, the rest
 * alphabetically in between.
 *
 * @param array<string, array<string, mixed>> $defs
 * @return array<string, array<string, array<string, mixed>>>  group => key => def
 */
function preview_email_grouped(array $defs): array
{
    $groups = [];
    foreach ($defs as $key => $def) {
        $groups[$def['group']][$key] = $def;
    }
    $core = preview_email_const('PREVIEW_EMAIL_GROUP_CORE', 'Zen Cart');
    $admin = preview_email_const('PREVIEW_EMAIL_GROUP_ADMIN', 'Zen Cart admin notices');
    $other = preview_email_const('PREVIEW_EMAIL_GROUP_OTHER_TEMPLATES', 'Other templates found');
    $site = preview_email_const('STORE_NAME', '');
    uksort($groups, static function (string $a, string $b) use ($core, $admin, $other, $site): int {
        $rank = static function (string $g) use ($core, $admin, $other, $site): int {
            if ($g === $core) {
                return 0;
            }
            if ($g === $admin) {
                return 1;
            }
            if ($g === $other) {
                return 8;
            }
            if ($g !== '' && $g === $site) {
                return 9;
            }
            return 5;
        };
        $ra = $rank($a);
        $rb = $rank($b);
        return ($ra === $rb) ? strcmp($a, $b) : $ra <=> $rb;
    });
    return $groups;
}

/**
 * Is a definition usable right now? Returns true, or a string saying why not
 * (shown next to a grayed-out entry). A definition's 'available' entry may be
 * a bool, a string, or a callable returning either.
 *
 * @return bool|string
 */
function preview_email_availability(array $def)
{
    if (!isset($def['available'])) {
        return true;
    }
    $a = $def['available'];
    if (is_callable($a)) {
        $a = $a();
    }
    if ($a === true || $a === null) {
        return true;
    }
    if ($a === false) {
        return preview_email_const('PREVIEW_EMAIL_NOT_AVAILABLE', 'Not available on this store.');
    }
    return (string)$a;
}

/* ------------------------------------------------------------------ *
 * Building a message
 * ------------------------------------------------------------------ */

/**
 * Run a definition's builder and normalize what it returns.
 *
 * @return array{subject: string, text: string, block: array, to_name: string, to_email: string,
 *               from_name: string, from_email: string, notes: string[], module: string, page_base: string}
 */
function preview_email_build(array $def): array
{
    $customer = preview_email_sample_customer();
    $built = call_user_func($def['build'], $def);
    if (!is_array($built)) {
        $built = [];
    }
    $block = isset($built['block']) && is_array($built['block']) ? $built['block'] : [];
    $message = [
        'subject' => (string)($built['subject'] ?? preview_email_const('PREVIEW_EMAIL_DEFAULT_SUBJECT', 'Sample subject line')),
        'text' => (string)($built['text'] ?? ''),
        'block' => $block,
        'to_name' => (string)($built['to_name'] ?? $customer['name']),
        'to_email' => (string)($built['to_email'] ?? $customer['email']),
        'from_name' => (string)($built['from_name'] ?? preview_email_const('STORE_NAME')),
        'from_email' => (string)($built['from_email'] ?? preview_email_const('EMAIL_FROM')),
        'notes' => array_values(array_filter(array_map('strval', (array)($built['notes'] ?? [])))),
        'module' => (string)($built['module'] ?? $def['module']),
        'page_base' => (string)($built['page_base'] ?? $def['page_base']),
    ];
    // The legacy hook from Preview Email 1.x/2.x, kept so a custom_preview_email.php
    // a store already has keeps working.
    if (function_exists('preview_email_custom')) {
        $content = $message['block'];
        preview_email_custom($def['key'], $content);
        if (is_array($content)) {
            $message['block'] = $content;
        }
    }
    return $message;
}

/**
 * The full HTML email, exactly as core would build it -- same function,
 * same template resolution, same defaults for the logo, footer and
 * disclaimer. The recipient fields core adds inside zen_mail() are added
 * here too, since the template can use them.
 */
function preview_email_render_html(array $message): string
{
    global $current_page_base;
    if (empty($message['block'])) {
        return '';
    }
    $block = $message['block'];
    if (empty($block['EMAIL_TO_NAME'])) {
        $block['EMAIL_TO_NAME'] = $message['to_name'];
    }
    if (empty($block['EMAIL_TO_ADDRESS'])) {
        $block['EMAIL_TO_ADDRESS'] = $message['to_email'];
    }
    if (empty($block['EMAIL_SUBJECT'])) {
        $block['EMAIL_SUBJECT'] = $message['subject'];
    }
    if (empty($block['EMAIL_FROM_NAME'])) {
        $block['EMAIL_FROM_NAME'] = $message['from_name'];
    }
    if (empty($block['EMAIL_FROM_ADDRESS'])) {
        $block['EMAIL_FROM_ADDRESS'] = $message['from_email'];
    }
    if (empty($block['EMAIL_MESSAGE_HTML'])) {
        $block['EMAIL_MESSAGE_HTML'] = $message['text'];
    }
    $saved = $current_page_base;
    $current_page_base = ($message['page_base'] !== '') ? $message['page_base'] : $message['module'];
    $html = (string)zen_build_html_email_from_template($message['module'], $block);
    $current_page_base = $saved;
    return $html;
}

/**
 * Placeholders that survived rendering: the template names them and nothing
 * filled them. Core leaves them in the sent email as literal `$NAME` text,
 * which is exactly the sort of thing a preview exists to catch.
 *
 * @return string[]
 */
function preview_email_unfilled_placeholders(string $html): array
{
    // Strip the stylesheet: CSS can legitimately contain nothing that looks
    // like a placeholder, but a store's own rules might, so keep the check to
    // the document body.
    $body = (string)preg_replace('~<style\b.*?</style>~is', '', $html);
    if (preg_match_all('~\$([A-Z][A-Z0-9_]{2,})~', $body, $m) < 1) {
        return [];
    }
    $names = array_values(array_unique($m[1]));
    sort($names);
    return $names;
}

/**
 * The plain-text part, derived the way zen_mail() derives it on every
 * supported release: an explicit text body has its markup stripped except
 * for a handful of tags core keeps; an empty one is generated from the HTML
 * message block. Then the non-transactional disclaimers and the ampersand
 * clean-up. What comes out is what a TEXT-preference recipient reads.
 */
function preview_email_render_text(array $message): string
{
    $text = (string)$message['text'];
    $module = (string)$message['module'];
    $html = (string)($message['block']['EMAIL_MESSAGE_HTML'] ?? '');

    if ($text === '') {
        $text = str_replace(['<br>', '<br />', '</p>'], ["<br>\n", "<br>\n", "</p>\n"], $html);
        $text = ($module !== 'xml_record') ? zen_output_string_protected(stripslashes(strip_tags($text))) : $text;
    } elseif ($module !== 'xml_record') {
        $text = (string)preg_replace('~</?([^(strong>|br ?\/?>|a href=|p |span|script|li|ol|ul|em|b>|i>|u>)])~', '@lt@\\1', $text);
        $text = strip_tags($text);
        $text = str_replace('@lt@', '<', $text);
    }

    $owner = preview_email_const('STORE_OWNER_EMAIL_ADDRESS');
    if (preview_email_is_non_transactional($module) && $message['to_email'] !== $owner) {
        $disclaimer = preview_email_const('EMAIL_DISCLAIMER');
        if ($disclaimer !== '' && strpos($text, sprintf($disclaimer, $owner)) === false && !defined('EMAIL_DISCLAIMER_NEW_CUSTOMER')) {
            $text .= "\n" . sprintf($disclaimer, $owner);
        }
        $spam = preview_email_const('EMAIL_SPAM_DISCLAIMER');
        if ($spam !== '' && strpos($text, $spam) === false) {
            $text .= "\n\n" . $spam;
        }
    }

    $text = (string)preg_replace('/((&amp;)|&)+/', '&', $text);
    $text = str_replace('&#8209;', '-', $text);
    return $text;
}

/**
 * The generic builder for a template file nothing defines: the common
 * salutation, name and message placeholders, so the layout can be seen.
 */
function preview_email_build_generic(array $def): array
{
    $customer = preview_email_sample_customer();
    $message = preview_email_const('PREVIEW_EMAIL_GENERIC_MESSAGE', 'This is sample message content, standing in for whatever this email normally carries.');
    return [
        'subject' => preview_email_const('PREVIEW_EMAIL_DEFAULT_SUBJECT', 'Sample subject line'),
        'text' => $message,
        'block' => [
            'EMAIL_SALUTATION' => preview_email_const('EMAIL_SALUTATION', 'Dear'),
            'EMAIL_FIRST_NAME' => $customer['firstname'],
            'EMAIL_LAST_NAME' => $customer['lastname'],
            'EMAIL_CUSTOMERS_NAME' => $customer['name'],
            'EMAIL_GREETING' => preview_email_const('EMAIL_SALUTATION', 'Dear') . ' ' . $customer['name'] . ',',
            'EMAIL_MESSAGE_HTML' => '<p>' . $message . '</p>',
        ],
        'to_name' => $customer['name'],
        'to_email' => $customer['email'],
    ];
}

/**
 * The admin "extra" copy of another definition: the same message, sent to
 * the store's copy address with the EXTRA_INFO block core adds.
 *
 * Used by a definition as   'build' => preview_email_extra_builder('checkout')
 */
function preview_email_extra_builder(string $baseKey): callable
{
    return static function (array $def) use ($baseKey): array {
        $defs = preview_email_definitions();
        if (!isset($defs[$baseKey])) {
            return preview_email_build_generic($def);
        }
        $base = preview_email_build($defs[$baseKey]);
        $customer = preview_email_sample_customer();
        $extra = preview_email_extra_info($customer['name'], $customer['email'], $customer['name'], $customer['email'], $customer['telephone']);
        $base['block']['EXTRA_INFO'] = (string)($extra['HTML'] ?? '');
        $base['text'] .= (string)($extra['TEXT'] ?? '');
        $base['module'] = $def['module'];
        $base['to_name'] = '';
        $base['to_email'] = preview_email_const('STORE_OWNER_EMAIL_ADDRESS');
        $base['notes'][] = preview_email_const('PREVIEW_EMAIL_NOTE_EXTRA_COPY', 'This is the copy the store receives, with the extra-info block core adds to admin copies.');
        return $base;
    };
}

/* ------------------------------------------------------------------ *
 * Sending a test
 * ------------------------------------------------------------------ */

/**
 * Send a built message to one address through zen_mail(), forcing the HTML
 * or TEXT format for this one send regardless of what the customers table
 * says about that address. The observer in admin/includes/classes/observers
 * reads the flag on NOTIFY_EMAIL_DETERMINING_EMAIL_FORMAT.
 *
 * @param string $format  'HTML' or 'TEXT'
 * @return array{ok: bool, reason: string}
 */
function preview_email_send_test(array $message, string $to, string $format): array
{
    $format = ($format === 'TEXT') ? 'TEXT' : 'HTML';
    if (preview_email_const('SEND_EMAILS') !== 'true') {
        return ['ok' => false, 'reason' => preview_email_const('PREVIEW_EMAIL_ERR_SENDING_OFF', 'Sending is turned off: Configuration > E-Mail Options > Send E-Mails is false.')];
    }
    if ($format === 'HTML' && preview_email_const('EMAIL_USE_HTML') !== 'true') {
        return ['ok' => false, 'reason' => preview_email_const('PREVIEW_EMAIL_ERR_HTML_OFF', 'HTML email is turned off: Configuration > E-Mail Options > E-Mail Format is not HTML, so only a plain-text test can be sent.')];
    }
    if (defined('EMAIL_MODULES_TO_SKIP') && in_array($message['module'], explode(',', (string)constant('EMAIL_MODULES_TO_SKIP')), true)) {
        return ['ok' => false, 'reason' => sprintf(preview_email_const('PREVIEW_EMAIL_ERR_MODULE_SKIPPED', 'The %s email is listed in EMAIL_MODULES_TO_SKIP, so zen_mail() refuses to send it.'), $message['module'])];
    }

    global $current_page_base;
    $saved = $current_page_base;
    $current_page_base = ($message['page_base'] !== '') ? $message['page_base'] : $message['module'];

    $GLOBALS['preview_email_force_format'] = $format;
    $block = $message['block'];
    if ($format === 'TEXT') {
        // Nothing for core to render: zen_mail() treats '' as "no HTML part".
        $block = '';
    }
    $result = zen_mail(
        $message['to_name'] !== '' ? $message['to_name'] : $to,
        $to,
        $message['subject'],
        $message['text'] !== '' ? $message['text'] : (string)($message['block']['EMAIL_MESSAGE_HTML'] ?? ''),
        $message['from_name'],
        $message['from_email'],
        $block,
        $message['module']
    );
    unset($GLOBALS['preview_email_force_format']);
    $current_page_base = $saved;

    // zen_mail() returns '' on success, false when it declined, or an error string.
    if ($result === '' || $result === true) {
        return ['ok' => true, 'reason' => ''];
    }
    if ($result === false) {
        return ['ok' => false, 'reason' => preview_email_const('PREVIEW_EMAIL_ERR_DECLINED', 'zen_mail() declined to send. Check that the address is valid and that the recipient has not opted out (customers_email_format NONE or OUT).')];
    }
    return ['ok' => false, 'reason' => (string)$result];
}

/**
 * The format the test-send observer should force, if a send is in progress.
 */
function preview_email_forced_format(): string
{
    $f = $GLOBALS['preview_email_force_format'] ?? '';
    return ($f === 'HTML' || $f === 'TEXT') ? $f : '';
}
