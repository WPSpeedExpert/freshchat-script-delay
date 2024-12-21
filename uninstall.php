<?php
/**
 * Uninstall script for the Freshchat Script Delay plugin.
 * This script removes all plugin-related settings from the database.
 */

// Exit if accessed directly
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Option name used by the plugin
$option_name = 'freshchat_widget_token';

// Delete the plugin's option from the database
delete_option($option_name);
