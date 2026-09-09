<?php
/**
 * Preview Email -- Plugin Manager installer.
 *
 * The plugin keeps no configuration and no tables. All it needs is a menu
 * entry under Tools, so the installer registers that admin page and the
 * uninstaller removes it. Deliberately limited to the API that exists in
 * every supported Zen Cart release (v1.5.8 -> v3.0.0):
 *
 *   - zen_register_admin_page() / zen_deregister_admin_pages() live in
 *     admin_access.php on every release;
 *   - executeUpgrade() is declared with an optional argument, because ZC
 *     v1.5.8 calls it with none and ZC v2.x/v3.x calls it with $oldVersion.
 *
 * Every step is idempotent, so an upgrade is simply a re-run of the install.
 *
 * @package  PreviewEmail
 * @license  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

use Zencart\PluginSupport\ScriptedInstaller as ScriptedInstallBase;

class ScriptedInstaller extends ScriptedInstallBase
{
    /**
     * admin_pages.page_key values this plugin owns.
     */
    public const ADMIN_PAGE_KEYS = ['toolsPreviewEmail'];

    /**
     * The page_key Preview Email 1.x/2.x registered with its SQL patch.
     * Removed on install so a store upgrading from the old copy-files
     * version does not end up with two Preview Email entries under
     * Customers and Tools.
     */
    public const LEGACY_PAGE_KEYS = ['previewEmail'];

    protected function executeInstall()
    {
        $this->previewEmailRegisterAdminPage();
        $this->previewEmailLog('Preview Email: installed/upgraded. Menu entry: Tools > Preview Email.', 'info');
        return true;
    }

    /**
     * ZC v1.5.8 calls this with no argument; ZC v2.x/v3.x passes $oldVersion.
     */
    protected function executeUpgrade($oldVersion = null)
    {
        return $this->executeInstall();
    }

    protected function executeUninstall()
    {
        zen_deregister_admin_pages(self::ADMIN_PAGE_KEYS);
        $this->previewEmailLog('Preview Email: uninstalled. Nothing else was stored, so nothing else was removed.', 'info');
        return true;
    }

    protected function previewEmailRegisterAdminPage()
    {
        // zen_register_admin_page() performs a plain INSERT, so clear first.
        zen_deregister_admin_pages(array_merge(self::ADMIN_PAGE_KEYS, self::LEGACY_PAGE_KEYS));

        zen_register_admin_page(
            'toolsPreviewEmail',
            'BOX_TOOLS_PREVIEW_EMAIL',
            'FILENAME_PREVIEW_EMAIL',
            '',
            'tools',
            'Y',
            85
        );
    }

    /**
     * Write a line to the admin activity log, if we can. Guarded because the
     * installer runs mid-transaction in Plugin Manager and a missing logger
     * must never be the thing that fails an install.
     *
     * @param string $message
     * @param string $severity
     * @return void
     */
    protected function previewEmailLog($message, $severity = 'info')
    {
        if (function_exists('zen_record_admin_activity')) {
            zen_record_admin_activity($message, $severity);
        }
    }
}
