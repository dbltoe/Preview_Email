# Customizing Preview Email

Preview Email knows about an email because a **definition** describes it: a
small PHP file that says what the email is called, which `zen_mail()` module
it uses, and how to build a sample of it. The plugin ships definitions for
Zen Cart's own emails and for several add-ons. You can add your own, and a
plugin can ship its own.

## Where Definitions Are Read From

In this order. A later definition with the same `key` replaces an earlier
one, so a store can override anything a plugin ships.

1. `zc_plugins/PreviewEmail/v3.0.0/shared/emails/core/` -- Zen Cart's emails
2. `zc_plugins/PreviewEmail/v3.0.0/shared/emails/plugins/` -- bundled add-on
   definitions, each self-guarding (it returns nothing when its add-on is
   not installed)
3. `zc_plugins/<AnyPlugin>/<version>/email_preview/` -- for every installed,
   enabled plugin. This is how a plugin ships definitions for its own emails.
4. `<admin>/includes/email_preview/` -- the store's own definitions

Then every `email_template_*.html` file no definition claims gets a generic
entry under "Other templates found".

Every `.php` file in those directories is included and must **return** either
one definition or a list of them. A file that returns anything else is
reported at the top of the page and skipped.

## A Definition

```php
<?php
// <admin>/includes/email_preview/renewal_reminder.php
if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

return [
    'key' => 'renewal_reminder',            // unique; a-z, 0-9 and _ only
    'group' => 'My Store',                   // heading on the page (optional;
                                             // defaults to the plugin name or
                                             // the store name)
    'label' => 'Support renewal reminder',   // what the row says
    'describe' => 'Sent eleven months after a purchase.',   // optional
    'module' => 'renewal_reminder',          // the zen_mail() module name
    'page_base' => 'index',                  // optional: $current_page_base
                                             // when the real one is sent,
                                             // which core also tries as a
                                             // template name
    'sort' => 10,                            // optional order within the group
    'available' => static function () {     // optional
        // true: usable. false or a string: listed grayed, with the reason.
        return defined('MY_RENEWAL_ENABLED') ? true : 'Renewals are turned off.';
    },
    'build' => static function (array $def): array {
        $customer = preview_email_sample_customer();
        $html = '<p>Hello ' . zen_output_string_protected($customer['firstname']) . ', your year is almost up.</p>';
        return [
            'subject' => 'Your support renewal',
            'text' => "Hello " . $customer['firstname'] . ", your year is almost up.",
            'block' => ['EMAIL_MESSAGE_HTML' => $html],
            'to_name' => $customer['name'],
            'to_email' => $customer['email'],
            // optional: 'from_name', 'from_email', 'module', 'page_base',
            // 'notes' => ['shown in the preview strip']
        ];
    },
];
```

`build` returns what the real sender passes to `zen_mail()`: the subject, the
text body, and the `$block` array of template placeholders. Give it exactly
what your sending code gives, and the preview is exactly your email. The
usual way is to call the same function your sender calls.

## Helpers You Can Use in a Builder

All in `shared/functions.php`, loaded before any definition runs.

- `preview_email_sample_customer()` -- a recent customer (`id`, `firstname`,
  `lastname`, `name`, `email`, `telephone`, `real`). Invented when the store
  has none.
- `preview_email_sample_order_id($withHistory = false)` -- a recent order id,
  or 0.
- `preview_email_sample_product()` -- a recent active product (`id`, `name`,
  `model`, `image`, `real`).
- `preview_email_sample_coupon()` -- a coupon, preferring active ones.
- `preview_email_const('NAME', 'fallback')` -- a language constant or a
  fallback, so a builder never fatals on a missing string.
- `preview_email_load_catalog_language('checkout_process')`,
  `preview_email_load_admin_language('orders')`,
  `preview_email_load_plugin_language('MyPlugin', 'admin/includes/languages/', 'my_page')`
  -- load a language file into constants, both `lang.*.php` arrays and legacy
  `define()` files, honoring the template override folder. Constants already
  defined are left alone.
- `preview_email_installed_plugin_dir('MyPlugin')` -- the version directory
  of an installed plugin, or `''`.
- `preview_email_extra_builder('checkout')` -- a builder for the store copy
  of another definition (same message, `EXTRA_INFO` block added, sent to the
  store).

## Shipping Definitions in Your Own Plugin

Put a directory named `email_preview/` in your plugin's version directory,
next to `manifest.php`, with one or more definition files in it. Preview
Email reads it whenever your plugin is installed and enabled, and lists the
emails under your plugin's name. Nothing in your plugin needs to reference
Preview Email; if it is not installed the directory is simply never read.

Group them under your plugin's name (`'group' => 'My Plugin'`) and prefix
the keys (`'key' => 'myplugin_welcome'`) so they cannot collide with
anyone else's.

## Overriding a Bundled Definition

Copy the definition to `<admin>/includes/email_preview/` and keep its `key`.
The store's copy is read last and wins.

## Hooks for Other Plugins

Beyond definitions, the page and the library fire six notifiers through the
admin's `$zco_notifier`, so an ordinary admin observer
(`admin/includes/classes/observers/auto.*.php` in your plugin) can extend
Preview Email without touching it. Each is fired with `$p1` by value and
`$p2` by reference; what you put in `$p2` is what Preview Email uses. The
names and parameters are a contract and do not change within 3.x.

| Notifier | `$p1` | `$p2` (by reference) | When |
|---|---|---|---|
| `NOTIFY_PREVIEW_EMAIL_DEFINITIONS_LOADED` | `[]` | the definitions array, `key => definition` | after every definition file is read and unclaimed templates added; add, alter or remove entries |
| `NOTIFY_PREVIEW_EMAIL_MESSAGE_BUILT` | `['definition' => $def]` | the built message | after a builder runs, before rendering or sending; change the block, subject or text, or set `EMAIL_TEMPLATE_FILENAME` in the block to render another file |
| `NOTIFY_PREVIEW_EMAIL_ACTION` | `['action' => string, 'definitions' => array]` | `$handled` (bool) | a POSTed `action` the page does not know; produce your own response and set `$handled = true`, or leave it and the page redirects to itself |
| `NOTIFY_PREVIEW_EMAIL_ROW_ACTIONS` | `['key', 'definition', 'available']` | HTML | appended to the row's action cell after the built-in buttons; the page's form already carries the `securityToken` |
| `NOTIFY_PREVIEW_EMAIL_PAGE_TOP` | `[]` | HTML | inserted after the status strip, before the send box |
| `NOTIFY_PREVIEW_EMAIL_PREVIEW_STRIP` | `['definition', 'message', 'part']` | HTML | appended to the strip above an HTML or text preview |

A button returned from `NOTIFY_PREVIEW_EMAIL_ROW_ACTIONS` that submits the
page's form with its own `action` value reaches `NOTIFY_PREVIEW_EMAIL_ACTION`
with the token already checked by Zen Cart. Escape everything you return;
it is echoed as is.

## The Legacy Hook

Preview Email 1.x and 2.x called `preview_email_custom($action, &$content)`
from `custom_preview_email.php` in the admin `extra_functions` directory, to
let a store add placeholders to a built email. 3.0.0 still calls it, after
every build, with the definition key as `$action` and the block array as
`$content`. On Zen Cart 3.0.0 the admin no longer auto-loads plugin
`extra_functions`, but a store's own admin `extra_functions` directory is
still loaded, which is where that file lives.

## Language

Every label, description, group name and sample string on the page is a
constant in `admin/includes/languages/english/lang.preview_email.php`. To
change one, define it in your own admin language file; a constant already
defined is never redefined. To translate the page, copy that file to
`admin/includes/languages/<language>/` in the plugin directory.
