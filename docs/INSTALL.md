# Installing Preview Email

## Requirements

- Zen Cart v1.5.8, v2.0.x, v2.1.x, v2.2.x, v2.3.x or v3.0.0
- PHP 7.4 through 8.5 (whatever your Zen Cart release itself supports)
- An admin account with access to Modules > Plugin Manager

## Install

1. Unzip the package. Inside is a `zc_plugins/PreviewEmail/v3.0.0/` directory.
2. Upload `zc_plugins/PreviewEmail/` into your store's `zc_plugins/` directory,
   so that `zc_plugins/PreviewEmail/v3.0.0/manifest.php` exists on the server.
3. In admin, open **Modules > Plugin Manager**. Preview Email appears in the
   list as Not Installed. Select it and press **Install**.
4. The page is now at **Tools > Preview Email**.

Nothing else is created: no configuration group, no database table. The
installer adds one row to `admin_pages` for the menu entry.

## Upgrading from Preview Email 1.x or 2.x

Those versions were installed by copying files into the admin directory and
running an SQL patch. To move to 3.0.0:

1. Install 3.0.0 as above. Its installer removes the old `previewEmail` menu
   row, so you do not end up with two entries.
2. Delete the old files from your admin directory:
   - `<admin>/preview_email.php`
   - `<admin>/includes/functions/extra_functions/preview_email.php`
   - `<admin>/includes/languages/english/preview_email.php`
   - `<admin>/includes/languages/english/extra_definitions/preview_email.php`

   Leave `<admin>/includes/functions/extra_functions/custom_preview_email.php`
   if you customized it; 3.0.0 still calls `preview_email_custom()`.

If you skip step 2 on Zen Cart 2.x or earlier, the old `preview_email.php`
functions file would define functions with the same names as the plugin's
builders. It does not: the plugin's functions are all prefixed
`preview_email_` and the old ones were `build_*`, so both can coexist. Still,
delete the old files; they are dead weight.

## Upgrading 3.0.0 to a later 3.x

Upload the new version directory alongside the old one
(`zc_plugins/PreviewEmail/v3.0.1/` next to `v3.0.0/`), then in Plugin Manager
select Preview Email and press **Upgrade**. After it reports success, delete
the old version directory. Re-running the installer is safe; every step is
idempotent.

## Uninstall

In Plugin Manager select Preview Email and press **Uninstall**. The menu row
is removed. Then delete the `zc_plugins/PreviewEmail/` directory if you want
the files gone too. Any `email_preview/` definitions you wrote under your
admin directory are yours and are left alone.
