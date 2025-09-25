<?php
/**
 * Uninstall Simple Conditional Fields for CF7
 *
 * This file is executed when the plugin is deleted via WordPress admin.
 * It removes all plugin data from the database.
 */

// Prevent direct access
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Remove all plugin data
 */
function scf7c_uninstall_plugin() {
    global $wpdb;

    // Remove all post meta created by the plugin
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required for complete uninstall cleanup
    $wpdb->delete(
        $wpdb->postmeta,
        array('meta_key' => '_scf7c_conditions'),
        array('%s')
    );

    // Remove any cached data
    wp_cache_flush();

    // Remove any transients (if we had any)
    delete_transient('scf7c_forms_with_conditions');

    // Clean up any options (if we had any)
    delete_option('scf7c_plugin_version');
}

// Execute cleanup
scf7c_uninstall_plugin();