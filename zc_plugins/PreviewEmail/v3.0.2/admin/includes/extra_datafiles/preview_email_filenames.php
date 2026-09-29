<?php
/**
 * Preview Email -- admin filename constant.
 *
 * `admin/includes/extra_datafiles/` is loaded per plugin by every release
 * from v1.5.8 onward, which a root-level filenames.php is not (v2.2.0+
 * only), so this is the single code path.
 *
 * @package  PreviewEmail
 * @license  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

if (!defined('FILENAME_PREVIEW_EMAIL')) {
    define('FILENAME_PREVIEW_EMAIL', 'preview_email');
}
