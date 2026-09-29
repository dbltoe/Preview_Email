<?php
/**
 * Preview Email -- global-namespace stubs for the plugin-local phpunit tests.
 *
 * Zen Cart's phpunit bootstrap does not load the function library, and the
 * text-part derivation calls this one core helper. Declared here rather than
 * inside the test class, because a function declared inside a namespaced
 * file belongs to that namespace and would not satisfy the global call.
 *
 * @package  PreviewEmail
 * @license  http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

if (!function_exists('zen_output_string_protected')) {
    function zen_output_string_protected($s)
    {
        return htmlspecialchars((string)$s, ENT_COMPAT, 'utf-8', true);
    }
}
