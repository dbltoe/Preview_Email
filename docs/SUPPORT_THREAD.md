# Forum Support Thread, 3.0.0 Announcement

Not packaged. The post announcing 3.0.0, as a reply in the existing Preview
Email thread, https://www.zen-cart.com/threads/201011 (John, 2026-09-09).
The manifest, readme and Library listing already carry the permalink to that
thread's opening post, so nothing needs changing after the reply is made.

---

**Preview Email 3.0.0** — see every email your store can send, as HTML or plain text, and send yourself a test.

This continues the Preview Email plugin that Scott C. Wilson (That Software Guy) wrote; 1.x and 2.x let you preview the twelve core emails that have template files. 3.0.0 is a rewrite as an encapsulated zc_plugin, installed and removed through Plugin Manager, and it changes the scope: every email the store can send, not just the ones with templates.

**What It Does.** Tools > Preview Email lists, in groups, every email Zen Cart sends (including the store copies, the low stock notice, Ask a Question, the review notice, the newsletter and the admin notices), the emails your add-ons send, your own custom emails, and any template file nothing claims. For each one you can open the HTML in a new tab exactly as core builds it, open the plain-text part (what a TEXT-Only customer gets), or send a test to any address as HTML or as plain text. The HTML preview flags every placeholder in the template that nothing fills, which is how it would go out.

**Add-On Authors:** put an `email_preview/` directory in your plugin's version directory with a small definition file per email, and Preview Email lists your emails under your plugin's name whenever both are installed. Nothing in your plugin needs to reference Preview Email. The format is in `docs/CUSTOMIZING.md`. Definitions are bundled for Social Contact Footer, Admin Add Customer, Fraud Screen, Back in Stock and Recover Cart Sales.

**Compatibility:** Zen Cart v1.5.8 through v3.0.0 from one codebase, on whatever PHP your release supports. What that took, and what was verified against each release, is in `docs/COMPATIBILITY.md`.

**Upgrading From 1.x/2.x:** install 3.0.0 (it removes the old menu row), then delete the old files from your admin directory. The readme lists them. `direct_test.html` and `custom_preview_email.php` still work.

Download: Zen Cart Plugins Library, or https://github.com/dbltoe/Preview_Email

Please report problems in this thread with your Zen Cart version, PHP version, and which email and button you pressed.
