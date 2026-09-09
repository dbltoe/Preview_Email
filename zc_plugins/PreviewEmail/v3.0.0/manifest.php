<?php
/**
 * Preview Email -- plugin manifest.
 *
 * Preview Email 1.x and 2.x were written by Scott C. Wilson, That Software Guy,
 * and released under the GPL. Version 3.0.0 is a rewrite as an
 * encapsulated zc_plugin by My Zen Cart Host (dbltoe), keeping the name,
 * the purpose and the sample-data approach, and adding every email a store
 * sends, discovery of add-on emails, plain-text previews and test sends.
 *
 * Links shown in the Plugin Manager, alongside Install / Uninstall / Disable.
 *
 * Zen Cart stores `pluginDescription` in `plugin_control.description` (a TEXT
 * column) and echoes it into the Plugin Manager's info box as raw HTML,
 * without escaping, on every release from v1.5.8 to v3.0.0. Markup placed
 * here appears exactly once, next to the action buttons, with no core file
 * changed.
 *
 * The Read Me link is built from DIR_WS_CATALOG rather than hard-coded, so it
 * works whatever the store lives at and whatever the admin directory has been
 * renamed to. Zen Cart's shipped `zc_plugins/.htaccess` denies everything then
 * explicitly re-allows `.html`, so readme.html is reachable by design.
 *
 * On v1.5.8, v2.0 and v2.1 the description is written only by the INSERT that
 * first creates the plugin_control row and never refreshed, so nothing
 * state-dependent belongs here, and the forum URL must be set
 * BEFORE THE FIRST RELEASE: a URL added later reaches nobody on those
 * releases short of an uninstall and re-install.
 *
 * @package  PreviewEmail
 * @license  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

$previewEmailPluginDir = 'zc_plugins/PreviewEmail/v3.0.0/';
$previewEmailReadmeUrl = (defined('DIR_WS_CATALOG') ? DIR_WS_CATALOG : '/') . $previewEmailPluginDir . 'readme.html';
$previewEmailGithubUrl = 'https://github.com/dbltoe/Preview_Email';

/**
 * The Zen Cart forum's support thread for this plugin: the existing Preview
 * Email thread, as the permalink to its opening post.
 */
$previewEmailForumUrl = 'https://www.zen-cart.com/threads/201011?page=1#post-1286460';

$previewEmailButtonGap = '6px';

$previewEmailLinks =
    '<div style="margin:10px 0 0;padding:0 0 0 ' . $previewEmailButtonGap . '">'
    . '<a href="' . $previewEmailReadmeUrl . '" target="_blank" rel="noopener noreferrer"'
    . ' class="btn btn-primary" role="button"'
    . ' style="margin:0 ' . $previewEmailButtonGap . ' 0 0">Read Me</a>'
    . '<a href="' . $previewEmailGithubUrl . '" target="_blank" rel="noopener noreferrer"'
    . ' class="btn btn-primary" role="button"'
    . ' style="margin:0 ' . $previewEmailButtonGap . ' 0 0">GitHub</a>'
    . '</div>';

$previewEmailForumLink = '';
if ($previewEmailForumUrl !== '') {
    $previewEmailForumLink =
        '<div style="margin:8px 0 0;padding:0 0 0 ' . $previewEmailButtonGap . '">'
        . '<a href="' . $previewEmailForumUrl . '" target="_blank" rel="noopener noreferrer">'
        . 'Forum Support Thread</a>'
        . '</div>';
}

return [
    'pluginVersion' => 'v3.0.0',
    'pluginName' => 'Preview Email',
    'pluginDescription' =>
        'See every email your store can send, built from your own recent customers, orders and '
        . 'products, as HTML or as the plain-text part, and send yourself a test in either format. '
        . 'Covers all of Zen Cart\'s own emails, finds the emails your add-ons send, and lists any '
        . 'template file nothing claims. Tools &gt; Preview Email.'
        . $previewEmailLinks
        . $previewEmailForumLink,
    // Shown as the Author in Plugin Manager, and stored in
    // plugin_control.author / plugin_control_versions.author (varchar(64)).
    'pluginAuthor' => 'My Zen Cart Host (dbltoe)',
    // ID of the Plugins Library listing this continues, Preview Email by
    // swguy: https://www.zen-cart.com/plugins/preview-email-vb2220. It has
    // to be right the FIRST time a store installs the plugin: on v1.5.8/
    // v2.0/v2.1 the row keeps whatever the original INSERT put there.
    'pluginId' => 2220,
    'zcVersions' => ['v158', 'v200', 'v210', 'v220', 'v230', 'v300'],
    'changelog' => 'changelog.txt',
    'github_repo' => $previewEmailGithubUrl,
    'pluginGroups' => [],
];
