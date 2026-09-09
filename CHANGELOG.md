# Changelog

All notable changes to this project are recorded here. This project follows
[Semantic Versioning](https://semver.org/).

## [3.0.0] — 2026-09-09

A rewrite. Versions 1.x and 2.x, by Scott C. Wilson (That Software Guy), were
files copied into the admin directory plus an SQL patch, and previewed the
twelve Zen Cart emails that have template files. 3.0.0 is an encapsulated
zc_plugin with a different scope: every email the store can send, not just
the ones with templates.

### Added

- **Every core email.** The store copies of the order, welcome, coupon, gift
  certificate and status emails (the `_extra` modules, with the extra-info
  block core adds), the low stock notice, Ask a Question, the pending-review
  notice, the newsletter, the admin password reset, the admin sign-in code,
  the admin-account-changed notice and payment module alerts. Every module
  name `zen_mail()` is called with in Zen Cart 2.2.2 has an entry.
- **Add-on emails, found automatically.** Any installed plugin can ship an
  `email_preview/` directory of definition files and they appear in their own
  group. A store describes its own custom emails the same way, in
  `<admin>/includes/email_preview/`. Definitions are bundled for Social
  Contact Footer, Admin Add Customer, Fraud Screen, Back in Stock (both the
  Ceon and the simple add-on) and Recover Cart Sales (single and drip).
- **Unclaimed templates.** Any `email_template_*.html` no definition uses is
  listed under "Other templates found" and previewed with generic content.
- **Plain-text preview.** The text part, derived the way `zen_mail()` derives
  it on every supported release, including the disclaimers it appends to
  non-transactional mail. It is what a TEXT-Only customer receives.
- **Test sends.** Any email to any address, as HTML or plain text. The
  format is forced for that one send through core's own
  `NOTIFY_EMAIL_DETERMINING_EMAIL_FORMAT` notifier, so an address the store
  does not know (which core would send plain text) can still get the HTML.
  Tests go through the store's real transport and settings.
- **Hooks for other plugins.** Six notifiers fired through the admin
  notifier: definitions loaded, message built, an unknown page action, extra
  row buttons, a page-top box, and extra preview-strip lines. Documented in
  docs/CUSTOMIZING.md; Preview Email Pro builds on them.
- **The stylesheet named.** The preview strip names the `email_common.css`
  core pours into the template, and says when the template carries rules of
  its own or the template or message content uses inline styles, since those
  win over the stylesheet.
- **Unfilled placeholders flagged.** The preview strip lists every
  `$PLACEHOLDER` the template names that nothing filled. Core leaves them in
  the sent email as literal text.
- **Status strip** showing whether sending and HTML are on, the transport,
  and the template directory, so a test that does not arrive is explained
  before it is sent.
- **Invented sample data** when the store has no customers, orders, products
  or coupons yet, instead of the old "create some orders and try again".
- **Language files loaded directly**, both `lang.*.php` arrays and legacy
  `define()` files, honoring the template override folder, without relying
  on the admin language loader (Zen Cart 3.0.0 dropped define-file loading).

### Changed

- Installed through Plugin Manager; the 1.x/2.x SQL patch is no longer
  needed, and its `previewEmail` menu row is removed on install so a store
  upgrading from the old copy does not get two entries.
- The page moved from Customers to **Tools > Preview Email**.
- Sample records are chosen from the last twenty at random, as before, but
  from a query that never walks off the end of a short result set.

### Kept

- `direct_test.html` in the catalog root still supplies the body of the
  direct-email preview.
- A `custom_preview_email.php` defining `preview_email_custom()` still runs
  after every build, so an existing site customization keeps working.
- The Back in Stock and Recover Cart Sales previews, now self-guarding so
  they appear only when those add-ons are present.

### Versions 1.x and 2.x

See <https://github.com/scottcwilson/zencart_preview_email>.
