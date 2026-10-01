<?php
/**
 * Preview Email -- the admin page.
 *
 * Reached as `admin/index.php?cmd=preview_email`, the routing Zen Cart has
 * used for plugin admin pages since v1.5.7. Access is governed by the
 * `toolsPreviewEmail` record the installer writes into `admin_pages`.
 *
 * Three things happen here, all POSTed with an `action` parameter so Zen
 * Cart's global admin CSRF check in init_sessions.php applies (the token is
 * re-checked below as well):
 *
 *   preview   render one email as HTML, or its plain-text part, in a new tab
 *   send      send one email to an address the admin types, forced to HTML
 *             or plain text for that one send
 *   (none)    the list of every email this store can produce
 *
 * @package  PreviewEmail
 * @license  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

require 'includes/application_top.php';
require_once __DIR__ . '/../shared/functions.php';

$previewEmailAction = isset($_POST['action']) ? (string)$_POST['action'] : '';
$previewEmailProblems = [];
$previewEmailDefs = preview_email_definitions($previewEmailProblems);

/**
 * One line of the preview's warning strip, escaped.
 */
function preview_email_banner_line(string $label, string $value): string
{
    return '<div><strong>' . zen_output_string_protected($label) . '</strong> ' . zen_output_string_protected($value) . '</div>';
}

/**
 * The strip shown above a preview: who it goes to, which template built it,
 * any notes from the builder and, most usefully, any placeholders the
 * template names that nothing filled.
 */
function preview_email_banner(array $def, array $message, string $html, string $part): string
{
    $template = preview_email_resolve_template($message['module'], $message['page_base'], $message['block']);
    $lines = preview_email_banner_line(PREVIEW_EMAIL_BANNER_EMAIL, $def['label'] . ' (' . $message['module'] . ')');
    $lines .= preview_email_banner_line(PREVIEW_EMAIL_BANNER_SUBJECT, $message['subject']);
    $lines .= preview_email_banner_line(PREVIEW_EMAIL_BANNER_TO, trim($message['to_name'] . ' <' . $message['to_email'] . '>'));
    if ($part === 'html') {
        $lines .= preview_email_banner_line(PREVIEW_EMAIL_BANNER_TEMPLATE, ($template['path'] !== '' ? preview_email_relative_path($template['path']) : PREVIEW_EMAIL_BANNER_NO_TEMPLATE) . ($template['fallback'] ? ' ' . PREVIEW_EMAIL_BANNER_FALLBACK : ''));
        // Where the styling comes from: the stylesheet core pours into
        // $EMAIL_COMMON_CSS, any rules the template carries itself, and inline
        // styles in the template or in the message content.
        $stylesheet = preview_email_resolve_stylesheet($message['module']);
        $lines .= preview_email_banner_line(PREVIEW_EMAIL_BANNER_STYLESHEET, ($stylesheet['path'] !== '' ? $stylesheet['relative'] . ' ' . PREVIEW_EMAIL_BANNER_STYLESHEET_HOW : PREVIEW_EMAIL_BANNER_NO_STYLESHEET));
        $css = preview_email_css_facts($template['path'], $message['block']);
        if ($css['template_rules']) {
            $lines .= preview_email_banner_line(PREVIEW_EMAIL_BANNER_CSS_ALSO, PREVIEW_EMAIL_BANNER_TEMPLATE_RULES);
        }
        if ($css['template_inline'] > 0 || $css['content_inline'] > 0) {
            $lines .= preview_email_banner_line(PREVIEW_EMAIL_BANNER_CSS_ALSO, sprintf(PREVIEW_EMAIL_BANNER_INLINE_CSS, $css['template_inline'], $css['content_inline']));
        }
        $unfilled = preview_email_unfilled_placeholders($html);
        if ($unfilled !== []) {
            $lines .= '<div class="pe-warn"><strong>' . PREVIEW_EMAIL_BANNER_UNFILLED . '</strong> $' . implode(', $', array_map('zen_output_string_protected', $unfilled)) . '</div>';
        }
    } else {
        $lines .= preview_email_banner_line(PREVIEW_EMAIL_BANNER_TEMPLATE, PREVIEW_EMAIL_BANNER_TEXT_PART);
    }
    foreach ($message['notes'] as $note) {
        $lines .= '<div class="pe-note">' . zen_output_string_protected($note) . '</div>';
    }
    foreach ((array)($GLOBALS['preview_email_skipped_language_files'] ?? []) as $skipped) {
        $lines .= '<div class="pe-note">' . zen_output_string_protected(sprintf(PREVIEW_EMAIL_BANNER_SKIPPED_LANG, $skipped)) . '</div>';
    }
    // Another plugin's lines for this strip, if any.
    $extra = '';
    preview_email_notify('NOTIFY_PREVIEW_EMAIL_PREVIEW_STRIP', ['definition' => $def, 'message' => $message, 'part' => $part], $extra);
    $lines .= (string)$extra;
    return '<div class="pe-banner" style="font:13px/1.5 Arial,Helvetica,sans-serif;background:#fffbe6;border-bottom:2px solid #e0c060;padding:8px 14px;color:#333">'
        . '<style>.pe-banner .pe-warn{color:#a40000;margin-top:4px}.pe-banner .pe-note{color:#555;margin-top:2px}</style>'
        . $lines . '</div>';
}

if ($previewEmailAction === 'preview' || $previewEmailAction === 'send') {
    // Belt and braces: init_sessions.php already rejected a bad token.
    $postedToken = isset($_POST['securityToken']) ? (string)$_POST['securityToken'] : '';
    if (empty($_SESSION['securityToken']) || !hash_equals((string)$_SESSION['securityToken'], $postedToken)) {
        zen_redirect(zen_href_link(FILENAME_PREVIEW_EMAIL, '', 'SSL'));
    }

    $key = isset($_POST['email']) ? preg_replace('~[^a-z0-9_]~', '', (string)$_POST['email']) : '';
    if ($key === '' || !isset($previewEmailDefs[$key])) {
        $messageStack->add_session(PREVIEW_EMAIL_ERR_NO_EMAIL, 'error');
        zen_redirect(zen_href_link(FILENAME_PREVIEW_EMAIL, '', 'SSL'));
    }
    $def = $previewEmailDefs[$key];
    $availability = preview_email_availability($def);
    if ($availability !== true) {
        $messageStack->add_session(zen_output_string_protected($availability), 'warning');
        zen_redirect(zen_href_link(FILENAME_PREVIEW_EMAIL, '', 'SSL'));
    }
    $message = preview_email_build($def);

    if ($previewEmailAction === 'preview') {
        $part = (isset($_POST['part']) && $_POST['part'] === 'text') ? 'text' : 'html';
        if ($part === 'html') {
            $html = preview_email_render_html($message);
            if (trim($html) === '') {
                $html = '<html><body><p>' . PREVIEW_EMAIL_ERR_EMPTY_HTML . '</p></body></html>';
            }
            $banner = preview_email_banner($def, $message, $html, 'html');
            // Put the strip inside the document, right after <body>, so the
            // email's own <head> and stylesheet still apply to what follows.
            if (preg_match('~<body[^>]*>~i', $html, $m, PREG_OFFSET_CAPTURE) === 1) {
                $at = $m[0][1] + strlen($m[0][0]);
                $html = substr($html, 0, $at) . $banner . substr($html, $at);
            } else {
                $html = $banner . $html;
            }
            header('Content-Type: text/html; charset=' . CHARSET);
            echo $html;
        } else {
            $text = preview_email_render_text($message);
            header('Content-Type: text/html; charset=' . CHARSET);
            echo '<!DOCTYPE html><html><head><meta charset="' . CHARSET . '"><title>' . zen_output_string_protected($def['label']) . '</title></head><body style="margin:0">';
            echo preview_email_banner($def, $message, '', 'text');
            echo '<pre style="font:14px/1.5 Menlo,Consolas,monospace;white-space:pre-wrap;padding:14px;margin:0">' . zen_output_string_protected($text) . '</pre>';
            echo '</body></html>';
        }
        require DIR_WS_INCLUDES . 'application_bottom.php';
        exit;
    }

    // send
    $to = isset($_POST['to']) ? trim((string)$_POST['to']) : '';
    $format = (isset($_POST['format']) && $_POST['format'] === 'TEXT') ? 'TEXT' : 'HTML';
    if ($to === '' || !zen_validate_email($to)) {
        $messageStack->add_session(PREVIEW_EMAIL_ERR_BAD_ADDRESS, 'error');
        zen_redirect(zen_href_link(FILENAME_PREVIEW_EMAIL, '', 'SSL'));
    }
    $result = preview_email_send_test($message, $to, $format);
    if ($result['ok']) {
        $messageStack->add_session(sprintf(PREVIEW_EMAIL_SENT, zen_output_string_protected($def['label']), zen_output_string_protected($to), $format === 'TEXT' ? PREVIEW_EMAIL_FORMAT_TEXT : PREVIEW_EMAIL_FORMAT_HTML), 'success');
        if (function_exists('zen_record_admin_activity')) {
            zen_record_admin_activity('Preview Email: test "' . $def['label'] . '" (' . $message['module'] . ') sent to ' . $to . ' as ' . $format, 'info');
        }
    } else {
        $messageStack->add_session(sprintf(PREVIEW_EMAIL_NOT_SENT, zen_output_string_protected($def['label'])) . ' ' . zen_output_string_protected($result['reason']), 'error');
    }
    zen_redirect(zen_href_link(FILENAME_PREVIEW_EMAIL, '', 'SSL'));
}

if ($previewEmailAction !== '') {
    // An action this page does not know: another plugin's, or nobody's.
    // The token was checked by init_sessions.php because the request had an
    // action; a listener that produces its own response sets $handled.
    $previewEmailHandled = false;
    preview_email_notify('NOTIFY_PREVIEW_EMAIL_ACTION', ['action' => $previewEmailAction, 'definitions' => $previewEmailDefs], $previewEmailHandled);
    if ($previewEmailHandled === true) {
        require DIR_WS_INCLUDES . 'application_bottom.php';
        exit;
    }
    zen_redirect(zen_href_link(FILENAME_PREVIEW_EMAIL, '', 'SSL'));
}

$previewEmailGroups = preview_email_grouped($previewEmailDefs);
$previewEmailHtmlOn = (preview_email_const('EMAIL_USE_HTML') === 'true');
$previewEmailSendOn = (preview_email_const('SEND_EMAILS') === 'true');
$previewEmailDefaultTo = '';
if (isset($db) && defined('TABLE_ADMIN') && !empty($_SESSION['admin_id'])) {
    $previewEmailAdmin = $db->Execute("SELECT admin_email FROM " . TABLE_ADMIN . " WHERE admin_id = " . (int)$_SESSION['admin_id'] . " LIMIT 1");
    if (!$previewEmailAdmin->EOF) {
        $previewEmailDefaultTo = (string)$previewEmailAdmin->fields['admin_email'];
    }
}
if ($previewEmailDefaultTo === '') {
    $previewEmailDefaultTo = preview_email_const('STORE_OWNER_EMAIL_ADDRESS');
}
$previewEmailFormAction = zen_href_link(FILENAME_PREVIEW_EMAIL, '', 'SSL');
?>
<!DOCTYPE html>
<html <?= HTML_PARAMS; ?>>
<head>
<?php require DIR_WS_INCLUDES . 'admin_html_head.php'; ?>
<style>
.pe-intro{margin:0 0 14px;color:#444;font-size:1.25rem}
.pe-status{margin:0 0 16px;padding:8px 12px;border-radius:4px;background:#f4f6f8;border:1px solid #d8dee4;font-size:13px}
.pe-status .pe-bad{color:#a40000;font-weight:bold}
.pe-group{margin:0 0 22px}
.pe-group h2{font-size:1.1em;margin:0 0 6px;padding-bottom:4px;border-bottom:1px solid #d8dee4}
/* One table per group, so every table gets the same fixed column widths;
   otherwise each sizes its own columns and the headers wander from group
   to group. The first column takes whatever is left. */
.pe-table{width:100%;table-layout:fixed;border-collapse:collapse;font-size:13px}
.pe-table col.pe-c-module{width:14%}
.pe-table col.pe-c-template{width:24%}
.pe-table col.pe-c-actions{width:300px}
.pe-table th{text-align:left;padding:6px 8px;background:#f4f6f8;border-bottom:1px solid #d8dee4;font-weight:bold}
.pe-table td{padding:6px 8px;border-bottom:1px solid #eceff2;vertical-align:top}
.pe-table tr.pe-off td{color:#888}
.pe-table .pe-label{font-weight:bold}
.pe-table .pe-desc{color:#555;font-weight:normal;display:block;margin-top:2px}
.pe-table .pe-tpl{overflow-wrap:anywhere;font-family:Menlo,Consolas,monospace;font-size:12px}
.pe-table .pe-fallback{color:#8a6d00}
.pe-table .pe-css{display:block;color:#555;margin-top:2px}
.pe-table .pe-na{color:#a40000;font-size:12px;display:block}
.pe-actions{white-space:nowrap}
.pe-actions button{margin:0 4px 4px 0}
.pe-send{margin:0 0 18px;padding:10px 12px;border:1px solid #d8dee4;border-radius:4px;background:#fafbfc}
.pe-send label{margin-right:12px}
.pe-send input[type=email]{min-width:260px}
.pe-problems{margin:0 0 14px;padding:8px 12px;background:#fff3f3;border:1px solid #e0b4b4;border-radius:4px;font-size:13px}
</style>
</head>
<body>
<!-- header //-->
<?php require DIR_WS_INCLUDES . 'header.php'; ?>
<!-- header_eof //-->

<!-- body //-->
<div class="container-fluid pe-wrap">
    <h1><?= HEADING_TITLE; ?></h1>
    <?php
    // The admin messageStack has a size PROPERTY and a no-argument output();
    // size() and output('header') are the storefront class's API.
    if (isset($messageStack) && $messageStack->size > 0) {
        echo $messageStack->output();
    } ?>
    <p class="pe-intro"><?= PREVIEW_EMAIL_INTRO; ?></p>

    <div class="pe-status">
        <?= PREVIEW_EMAIL_STATUS_SENDING; ?>
        <?= $previewEmailSendOn ? PREVIEW_EMAIL_STATUS_ON : '<span class="pe-bad">' . PREVIEW_EMAIL_STATUS_OFF . '</span>'; ?>
        &nbsp;&middot;&nbsp;
        <?= PREVIEW_EMAIL_STATUS_HTML; ?>
        <?= $previewEmailHtmlOn ? PREVIEW_EMAIL_STATUS_ON : '<span class="pe-bad">' . PREVIEW_EMAIL_STATUS_OFF . '</span>'; ?>
        &nbsp;&middot;&nbsp;
        <?= PREVIEW_EMAIL_STATUS_TRANSPORT; ?> <?= zen_output_string_protected(preview_email_const('EMAIL_TRANSPORT', '?')); ?>
        &nbsp;&middot;&nbsp;
        <?= PREVIEW_EMAIL_STATUS_TEMPLATES; ?> <?= zen_output_string_protected(str_replace(DIR_FS_CATALOG, '', DIR_FS_EMAIL_TEMPLATES)); ?>
        &nbsp;&middot;&nbsp;
        <?php $previewEmailStylesheet = preview_email_resolve_stylesheet(); ?>
        <?= PREVIEW_EMAIL_STATUS_STYLESHEET; ?> <?= $previewEmailStylesheet['path'] !== '' ? zen_output_string_protected($previewEmailStylesheet['relative']) : '<span class="pe-bad">' . PREVIEW_EMAIL_BANNER_NO_STYLESHEET . '</span>'; ?>
    </div>

    <?php
    // Another plugin's own box, if any, between the status strip and the send box.
    $previewEmailPageTop = '';
preview_email_notify('NOTIFY_PREVIEW_EMAIL_PAGE_TOP', [], $previewEmailPageTop);
echo (string)$previewEmailPageTop;
?>

    <?php if ($previewEmailProblems !== []) { ?>
    <div class="pe-problems">
        <strong><?= PREVIEW_EMAIL_PROBLEMS; ?></strong>
        <ul>
            <?php foreach ($previewEmailProblems as $problem) { ?>
            <li><?= zen_output_string_protected($problem); ?></li>
            <?php } ?>
        </ul>
    </div>
    <?php } ?>

    <form name="preview_email" id="preview_email" action="<?= $previewEmailFormAction; ?>" method="post" target="_blank">
        <input type="hidden" name="securityToken" value="<?= zen_output_string_protected((string)($_SESSION['securityToken'] ?? '')); ?>">
        <input type="hidden" name="action" id="pe-action" value="preview">
        <input type="hidden" name="email" id="pe-email" value="">
        <input type="hidden" name="part" id="pe-part" value="html">

        <div class="pe-send">
            <strong><?= PREVIEW_EMAIL_SEND_HEADING; ?></strong>
            <label for="pe-to"><?= PREVIEW_EMAIL_SEND_TO; ?></label>
            <input type="email" name="to" id="pe-to" value="<?= zen_output_string_protected($previewEmailDefaultTo); ?>">
            <label><input type="radio" name="format" value="HTML" checked> <?= PREVIEW_EMAIL_FORMAT_HTML; ?></label>
            <label><input type="radio" name="format" value="TEXT"> <?= PREVIEW_EMAIL_FORMAT_TEXT; ?></label>
            <span class="pe-desc"><?= PREVIEW_EMAIL_SEND_HELP; ?></span>
        </div>

        <?php foreach ($previewEmailGroups as $groupName => $entries) { ?>
        <div class="pe-group">
            <h2><?= zen_output_string_protected($groupName); ?></h2>
            <table class="pe-table">
                <colgroup>
                    <col class="pe-c-email">
                    <col class="pe-c-module">
                    <col class="pe-c-template">
                    <col class="pe-c-actions">
                </colgroup>
                <thead>
                    <tr>
                        <th><?= PREVIEW_EMAIL_COL_EMAIL; ?></th>
                        <th><?= PREVIEW_EMAIL_COL_MODULE; ?></th>
                        <th><?= PREVIEW_EMAIL_COL_TEMPLATE; ?></th>
                        <th><?= PREVIEW_EMAIL_COL_ACTIONS; ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($entries as $key => $def) {
                    $availability = preview_email_availability($def);
                    $on = ($availability === true);
                    $template = $def['template'];
                    ?>
                    <tr class="<?= $on ? '' : 'pe-off'; ?>">
                        <td>
                            <span class="pe-label"><?= zen_output_string_protected($def['label']); ?></span>
                            <?php if ($def['describe'] !== '') { ?><span class="pe-desc"><?= zen_output_string_protected($def['describe']); ?></span><?php } ?>
                            <?php if (!$on) { ?><span class="pe-na"><?= zen_output_string_protected((string)$availability); ?></span><?php } ?>
                        </td>
                        <td class="pe-tpl"><?= zen_output_string_protected($def['module']); ?></td>
                        <td class="pe-tpl">
                            <?php if ($template['path'] === '') { ?>
                                <span class="pe-fallback"><?= PREVIEW_EMAIL_BANNER_NO_TEMPLATE; ?></span>
                            <?php } else { ?>
                                <?= zen_output_string_protected(preview_email_relative_path($template['path'])); ?>
                                <?php if ($template['fallback']) { ?><span class="pe-fallback"><?= PREVIEW_EMAIL_TEMPLATE_FALLBACK; ?></span><?php } ?>
                            <?php } ?>
                            <?php $stylesheet = preview_email_resolve_stylesheet($def['module']); ?>
                            <span class="pe-css"><?= PREVIEW_EMAIL_COL_CSS; ?> <?= $stylesheet['path'] !== '' ? zen_output_string_protected($stylesheet['relative']) : '<span class="pe-fallback">' . PREVIEW_EMAIL_BANNER_NO_TEMPLATE . '</span>'; ?></span>
                        </td>
                        <td class="pe-actions">
                            <?php if ($on) { ?>
                            <button type="submit" class="btn btn-default btn-xs" data-pe-email="<?= zen_output_string_protected($key); ?>" data-pe-action="preview" data-pe-part="html"><?= PREVIEW_EMAIL_BTN_HTML; ?></button>
                            <button type="submit" class="btn btn-default btn-xs" data-pe-email="<?= zen_output_string_protected($key); ?>" data-pe-action="preview" data-pe-part="text"><?= PREVIEW_EMAIL_BTN_TEXT; ?></button>
                            <button type="submit" class="btn btn-primary btn-xs" data-pe-email="<?= zen_output_string_protected($key); ?>" data-pe-action="send" data-pe-part="html"<?= $previewEmailSendOn ? '' : ' disabled'; ?>><?= PREVIEW_EMAIL_BTN_SEND; ?></button>
                            <?php } else { ?>
                            &mdash;
                            <?php } ?>
                            <?php
                            // Another plugin's buttons for this row, if any.
                            $previewEmailRowExtra = '';
                    preview_email_notify('NOTIFY_PREVIEW_EMAIL_ROW_ACTIONS', ['key' => $key, 'definition' => $def, 'available' => $on], $previewEmailRowExtra);
                    echo (string)$previewEmailRowExtra;
                    ?>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
        <?php } ?>
    </form>
</div>
<!-- body_eof //-->

<script>
(function () {
    // Every button in the list posts the same form; the button pressed says
    // which email, which action and which part. A preview opens in a new
    // tab; a test send stays on this page so the result message is seen.
    var form = document.getElementById('preview_email');
    if (!form) { return; }
    form.addEventListener('click', function (ev) {
        var button = ev.target.closest ? ev.target.closest('button[data-pe-email]') : null;
        if (!button) { return; }
        document.getElementById('pe-email').value = button.getAttribute('data-pe-email');
        document.getElementById('pe-action').value = button.getAttribute('data-pe-action');
        document.getElementById('pe-part').value = button.getAttribute('data-pe-part');
        form.target = (button.getAttribute('data-pe-action') === 'send') ? '_self' : '_blank';
    });
}());
</script>

<!-- footer //-->
<?php require DIR_WS_INCLUDES . 'footer.php'; ?>
<!-- footer_eof //-->
</body>
</html>
<?php require DIR_WS_INCLUDES . 'application_bottom.php'; ?>
