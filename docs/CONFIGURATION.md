# Using Preview Email

There is nothing to configure. Everything is on one page, **Tools > Preview
Email**.

## The status strip

Across the top, four facts about the store that decide whether a test send
can arrive, read from Configuration > E-Mail Options:

- **Sending** -- `Send E-Mails`. When off, `zen_mail()` returns without
  sending anything and the Send Test buttons are disabled.
- **HTML email** -- `E-Mail Format`. When it is not HTML, core sends every
  message as plain text and an HTML test is refused with a message saying so.
- **Transport** -- `E-Mail Transport Method` (PHP, sendmail, SMTP, and so on).
  A test that "sent" but never arrives is a transport problem, not a preview
  one; the transport is named here so it is the first thing you check.
- **Templates in** -- the directory core reads templates from, normally
  `email/`.

## The list

Emails are grouped: Zen Cart's own, Zen Cart's admin notices, one group per
add-on, your store's own definitions, and any template file nothing claims.
For each row:

- **Email** -- what it is and when it is sent.
- **Module** -- the name `zen_mail()` is called with. This is what a template
  file name has to match (`email_template_<module>.html`) and what the
  `EMAIL_MODULES_TO_SKIP` setting refers to.
- **Template file** -- the file core will use, resolved with core's own
  rules: the module name, then the module with `_extra`/`_admin` removed,
  then the page it is sent from, then `email_template_default.html`. When
  the default is what will be used because the email has no template of its
  own, the row says so.

A greyed row is an email this store cannot send right now (for example the
gift certificate emails when the Gift Certificates order-total module is not
installed). The reason is under the label.

## Preview HTML

Opens a new tab with the message exactly as core would build it: the same
template resolution, the same defaults for the logo, footer and disclaimers.
Across the top is a strip naming the email, its subject and recipient, the
template file, and:

- **Placeholders that nothing fills.** Every `$NAME` in the template that no
  code sets. Core does not remove these; they go out in the email as the
  literal text `$NAME`. If you see one here, either the template names
  something the sending code does not provide (a typo, or a placeholder from
  another template pasted in) or a plugin's email is using a template meant
  for a different email.
- Any notes from the builder, such as "this store has no orders yet, so the
  order shown is invented".

## Preview text

Opens a new tab with the plain-text part, derived the way `zen_mail()`
derives it: an explicit text body has its tags stripped except for the few
core keeps; a message with no text body has one generated from the HTML
block. The disclaimers core appends to non-transactional mail (newsletters,
coupon and gift certificate mailings, and so on) are appended here too.

This is what a customer who chose **TEXT-Only** on their account page
receives, and what every address the store does not know receives. It is
worth looking at; a message that reads well as HTML can be a wall of link
text in this form.

## Send test

Type an address (your own admin address is filled in), choose HTML or plain
text, and press **Send test** on any row. The message goes through
`zen_mail()` with the store's real transport, so what arrives is what a
customer would get, with two exceptions:

- **The format is forced.** Core decides HTML or text by looking the
  recipient up in the customers table, and sends plain text to any address it
  does not know. For a test that would make "send me the HTML" impossible
  for most admin addresses, so the format you chose is applied to that one
  send through core's own `NOTIFY_EMAIL_DETERMINING_EMAIL_FORMAT` notifier.
  Nothing about the recipient's stored preference is changed.
- **The sample data is a sample.** The order, customer or product shown is a
  real recent record (or an invented one on an empty store), not something
  tied to the address you send to.

The result is reported at the top of the page. A refusal names the reason:
sending off, HTML off, the module listed in `EMAIL_MODULES_TO_SKIP`, an
invalid address, or the recipient having opted out (`customers_email_format`
of `NONE` or `OUT`). Each test is written to the admin activity log.

Tests are real sends. A test of the store copy of an order goes to the
address you typed, not to the store; nothing here ever sends to a customer.

## direct_test.html

The direct-email preview shows a sample message unless a file named
`direct_test.html` exists in the catalog root, in which case its contents are
used as the message body. This is how 1.x and 2.x worked and it is kept.
