# Preview Email for Zen Cart

See every email your store can send, built from your own recent customers,
orders and products, as HTML or as the plain-text part, and send yourself a
test in either format. Runs on **Zen Cart v1.5.8 through v3.0.0** from a single
encapsulated plugin.

Version 3.0.0 continues the Preview Email line begun by Scott C. Wilson (That
Software Guy). Versions 1.x and 2.x let you preview the twelve core emails that
have template files; 3.0.0 is a rewrite as a zc_plugin that covers every email
Zen Cart sends, finds the emails your add-ons send, previews the plain-text
part too, and sends tests.

## What You Get

**Tools > Preview Email** lists, in groups:

- **Zen Cart** -- order confirmation and the store's copy, order status update
  and its copy, low stock notice, welcome and its copy, password reset,
  Contact Us, Ask a Question, review awaiting approval, coupon mailing and its
  copy, gift certificate mailing and its copy, gift certificate release, gift
  certificate sent by a customer and its copy, direct email, newsletter,
  product notification, and the default template.
- **Zen Cart admin notices** -- admin password reset, admin sign-in code,
  admin-account-changed notice, payment module alert.
- **One group per add-on** that either ships its own definitions or is
  covered by the ones bundled here: Social Contact Footer, Admin Add Customer,
  Fraud Screen, Back in Stock, Recover Cart Sales.
- **Your store** -- anything you describe in an `email_preview/` directory
  under your admin folder.
- **Other templates found** -- any `email_template_*.html` file nothing above
  claims, previewed with generic content.

For each email: **Preview HTML** opens the message in a new tab exactly as core
would build it, with a strip at the top naming the template file used and any
placeholder the template names that nothing fills. **Preview text** shows the
plain-text part, which is what a customer who chose TEXT-Only receives.
**Send test** sends it to the address you typed, as HTML or as plain text,
overriding that address's stored preference for that one send.

Sample data comes from the store: a customer from the last twenty, an order
from the last twenty, and so on, chosen at random so repeated previews show
different records. A store with none of those gets invented data rather than
an error.

## Installing

Upload the `zc_plugins/PreviewEmail/` directory to your store's `zc_plugins/`
directory, then open **Modules > Plugin Manager**, select Preview Email and
press **Install**. Details, including upgrading from 1.x/2.x, are in
[docs/INSTALL.md](docs/INSTALL.md).

## Documentation

- [docs/INSTALL.md](docs/INSTALL.md) -- installing, upgrading from the
  copy-files versions, uninstalling
- [docs/CONFIGURATION.md](docs/CONFIGURATION.md) -- the page, the status strip,
  and what the store's email settings do to a test send
- [docs/CUSTOMIZING.md](docs/CUSTOMIZING.md) -- describing your own emails and
  shipping definitions inside a plugin
- [docs/COMPATIBILITY.md](docs/COMPATIBILITY.md) -- how one codebase covers
  v1.5.8 through v3.0.0, and what was verified against each release
- `zc_plugins/PreviewEmail/v3.0.0/readme.html` -- the same material as one
  page, also linked from the Plugin Manager panel

## License

GPL-2.0. Copyright (c) 2026 My Zen Cart Host (dbltoe). Preview Email 1.x and
2.x copyright (c) Scott C. Wilson. See [LICENSE](LICENSE).
