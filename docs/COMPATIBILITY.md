# Compatibility

Preview Email 3.0.0 runs on Zen Cart v1.5.8 through v3.0.0 from one codebase.
Everything below was verified by reading the source of each release
(`v158`, `2.0`, `2.1`, `2.2`, `2.3` and `master` branches of
github.com/zencart/zencart), not taken from documentation.

## PHP

The source is written to PHP 7.4 syntax: no `match`, no nullsafe operator,
no union types, no named arguments, no enums. It is deprecation-clean on PHP
8.5. Which PHP you can actually run is decided by your Zen Cart release, not
by this plugin:

| Zen Cart | PHP |
|---|---|
| 1.5.8 | 7.4 - 8.0 (8.1 fatals in core's date class) |
| 2.0.x, 2.1.x | 8.0 - 8.3 |
| 2.2.x, 2.3.x | 8.2 - 8.5 |
| 3.0.0 | 8.3 - 8.5 |

## What the plugin relies on, and where it exists

| Used for | Mechanism | 1.5.8 | 2.0 | 2.1 | 2.2 | 2.3 | 3.0 |
|---|---|---|---|---|---|---|---|
| Menu entry | `zen_register_admin_page()` / `zen_deregister_admin_pages()` | yes | yes | yes | yes | yes | yes |
| Filename constant | `admin/includes/extra_datafiles/` loaded per plugin | yes | yes | yes | yes | yes | yes |
| Menu label | `admin/includes/languages/<lang>/extra_definitions/lang.*.php` per plugin | yes | yes | yes | yes | yes | yes |
| Page strings | `admin/includes/languages/<lang>/lang.<page>.php` per plugin | yes | yes | yes | yes | yes | yes |
| Page routing | `admin/index.php?cmd=preview_email` | yes | yes | yes | yes | yes | yes |
| Forcing the test format | `NOTIFY_EMAIL_DETERMINING_EMAIL_FORMAT` with `$format` by reference | yes | yes | yes | yes | yes | yes |
| Admin observer | `admin/includes/classes/observers/auto.*.php` auto-loaded | yes | yes | yes | yes | yes | yes |
| Building the HTML | `zen_build_html_email_from_template()` | yes | yes | yes | yes | yes | yes (wrapper for `Email::buildHtmlFromTemplate()`) |
| Plugin template dirs | `NOTIFY_EMAIL_REGISTER_ADDITIONAL_TEMPLATE_DIRS` | no | no | no | no | no | yes |
| Extra-info block | `email_collect_extra_info()` | yes | yes | yes | yes | yes | yes |
| Non-transactional test | `zen_is_non_transactional_email()` | yes | yes | yes | yes | yes | yes |

The template-directory notifier only exists on 3.0.0. The plugin fires it on
every release; on the others no observer answers and the list is the one
core directory, which is also what core uses there.

## Things deliberately avoided

- **`zen_config()`** is 3.0.0-only. Constants are read with
  `defined() ? constant() : fallback`.
- **The admin language loader.** 3.0.0 dropped `AdminFilesLanguageLoader`, so
  legacy `define()` language files are no longer loaded by core, and
  `loadExtraLanguageFiles()` never looks inside a plugin's directory on any
  release. The plugin loads the language files its builders need itself,
  handling both the array and the define() forms.
- **Plugin `extra_functions`.** Loaded on 1.5.8 through 2.3, removed from
  the admin bootstrap on master in June 2026. Every entry point
  `require_once`s `shared/functions.php` by `__DIR__` path and relies on no
  loader.
- **A root-level `filenames.php`.** Only auto-loaded from 2.2.0. The filename
  constant is in `admin/includes/extra_datafiles/`, which every release loads.
- **`zen_draw_form()` / `zen_draw_input_field()`.** Their signatures differ
  between admin and catalog. The page writes its form as plain HTML.
- **Installer convenience helpers** (`addConfigurationKey()` and friends) are
  2.0.1+. The installer needs none of them; it only registers a page.
- **Dynamic properties.** The order class's `products_ordered_html` is not
  declared on 2.2+, and PHP 8.2 deprecates creating it. The checkout builder
  keeps its markup in a local variable.

## The HTML/text decision

On every release, `zen_mail()` sends the HTML part only when
`EMAIL_USE_HTML` is true **and** the recipient's `customers_email_format` is
`HTML` (or the module is an `_extra` copy and `ADMIN_EXTRA_EMAIL_FORMAT` is
not TEXT). An address not in the customers table has an empty format and
gets plain text. That is why the test send forces the format through the
notifier rather than assuming HTML would arrive.

## Template resolution

The plugin resolves the template file the way core does, in this order, in
each template root:

1. `<lang>/email_template_<module minus _extra/_admin>.html`
2. `email_template_<module minus _extra/_admin>.html`
3. `<lang>/email_template_<page base>.html`
4. `email_template_<page base>.html`
5. `<EMAIL_TEMPLATE_FILENAME from the block>.html`
6. `<lang>/email_template_default.html`
7. `email_template_default.html`

This is identical on 1.5.8, 2.2.2 and 3.0.0 (the latter adds the plugin roots
before the core one).

## Unfilled placeholders

On every release core's builder replaces `$KEY` for each key in the block and
leaves any other `$NAME` in the output untouched. The preview scans the
rendered body (not the stylesheet) for surviving `$UPPER_CASE` tokens and
lists them, because that is what would go out.

## Preview Email 1.x/2.x on the same store

The old functions were named `build_*` and `preview_advance()`; everything
here is prefixed `preview_email_`. The two can coexist during an upgrade, but
the old files should be deleted (see INSTALL.md).
