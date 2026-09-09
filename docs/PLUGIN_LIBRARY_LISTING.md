# Plugins Library Listing

Not packaged; the text to paste into the Zen Cart Plugins Library submission
form. Committed so the wording is versioned with the code.

**Name:** Preview Email

**Category:** Admin Tools

**Zen Cart Versions:** 1.5.8, 2.0.0, 2.0.1, 2.1.0, 2.2.0, 2.2.1, 2.2.2, 2.3.0, 3.0.0

**Encapsulated:** Yes (zc_plugins)

**Version String:** v3.0.0 (must match `pluginVersion` in manifest.php exactly, `v` included, or Plugin Manager never announces an update)

**GitHub:** https://github.com/dbltoe/Preview_Email

**Short Description (Listing Summary):**

See every email your store can send, as HTML or as plain text, and send yourself a test of any of them. Covers all of Zen Cart's own emails, finds the emails your add-ons send, and flags placeholders in a template that nothing fills.

**Full Description:**

Zen Cart sends a lot of email you never see: the order confirmation goes to the customer, the status update goes to the customer, the low stock notice goes to a copy address. Preview Email puts all of it on one admin page, Tools > Preview Email, built from your own recent customers, orders and products.

For every email: Preview HTML opens it in a new tab exactly as core builds it, with a strip at the top naming the template file used and every placeholder in that template that nothing fills (core sends those as literal $NAME text). Preview text shows the plain-text part, which is what a customer who chose TEXT-Only receives. Send test sends it to an address you type, as HTML or as plain text, overriding that address's stored preference for that one send, through the store's real email transport.

What is listed:

- Every email Zen Cart itself sends: the order confirmation and the store's copy, the order status update and its copy, the low stock notice, the welcome email and its copy, the password reset, Contact Us, Ask a Question, the pending-review notice, the coupon and gift certificate mailings and their copies, the gift certificate release and the one a customer sends, the direct email, the newsletter, the product notification, and the default template.
- Zen Cart's admin notices: admin password reset, the admin sign-in code, the admin-account-changed notice, payment module alerts.
- The emails your add-ons send. Any installed plugin can ship an email_preview/ directory describing its emails and they appear in their own group. Definitions are bundled for Social Contact Footer, Admin Add Customer, Fraud Screen, Back in Stock and Recover Cart Sales.
- Your own custom emails, described in a small file under your admin directory.
- Any email_template_*.html file nothing claims.

A store with no orders, customers, products or coupons yet gets invented sample data instead of an error. Nothing is configured and no table is created; Plugin Manager installs one menu entry and removes it on uninstall.

Preview Email 1.x and 2.x were written by Scott C. Wilson (That Software Guy). Version 3.0.0 is a rewrite as an encapsulated plugin by My Zen Cart Host (dbltoe), keeping the name, the purpose and the sample-data approach. Both are GPL-2.0.

**Installation (Short Form for the Listing):**

Upload zc_plugins/PreviewEmail/ into your store's zc_plugins/ directory, then Modules > Plugin Manager > Preview Email > Install. The page is at Tools > Preview Email. Upgrading from 1.x/2.x: install 3.0.0 (it removes the old menu row), then delete the old files from your admin directory; the readme lists them.

**Note on the Name:** the Library already lists "Preview Email" (vb2220, swguy, v2.1, 1.5.8-2.0.0), the version this continues. If the moderators prefer a distinct listing name, "Preview Email 3" or "Preview and Test Email" are both one-line changes to `pluginName` in the manifest and the readme heading; the plugin key and directory (`PreviewEmail`) need not change.
