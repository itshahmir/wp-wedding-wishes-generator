<?php
/**
 * Wedding Wishes Generator
 *
 * @package       Wedding Wishes Generator
 * @author        Syed Ali Haider
 * @version       1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:   Wedding Wishes Generator
 * Plugin URI:    https://www.your-site.com/
 * Description:   A plugin to generate personalized wedding wishes using ChatGPT.
 * Version:       1.0.0
 * Author:        Syed Ali Haider
 * Author URI:    https://www.fiverr.com/syedali157
 * Text Domain:    Wedding-Wishes-Generator
 */

// Activation Hook
register_activation_hook(__FILE__, 'install_wishes_restrict_tables');
register_deactivation_hook(__FILE__, 'deactivate_wishes_restrict_plugin');

// Create Tables On Activation
function install_wishes_restrict_tables()
{
    global $wpdb;

    // Your table names
    $table_name_functions = $wpdb->prefix . 'ip_restrict_wishes';

    // SQL to create the 'configx_functions_code' table
    $sql_functions = "CREATE TABLE $table_name_functions (
        id INT NOT NULL AUTO_INCREMENT,
        ipAddress VARCHAR(255) NOT NULL,
        no_of_requests VARCHAR(255) NOT NULL,
        last_request_timestamp INT NULL,
        status VARCHAR(20) NOT NULL,
        PRIMARY KEY  (id)
    )";

    // Include upgrade.php to use dbDelta()
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Execute the SQL queries
    dbDelta($sql_functions);
}

// Callback function for uninstallation
function deactivate_wishes_restrict_plugin()
{
    global $wpdb;

    // Your table names
    $table_name_functions = $wpdb->prefix . 'ip_restrict_wishes';

    // SQL to drop the table
    $sql_drop_functions = "DROP TABLE IF EXISTS $table_name_functions";

    // Drop the table
    $wpdb->query($sql_drop_functions);
}

function getUserIP()
{
    // Get real visitor IP behind CloudFlare network
    if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
              $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
              $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
    }
    $client  = @$_SERVER['HTTP_CLIENT_IP'];
    $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
    $remote  = $_SERVER['REMOTE_ADDR'];

    if(filter_var($client, FILTER_VALIDATE_IP))
    {
        $ip = $client;
    }
    elseif(filter_var($forward, FILTER_VALIDATE_IP))
    {
        $ip = $forward;
    }
    else
    {
        $ip = $remote;
    }

    return $ip;
}


// Include necessary files
include_once(plugin_dir_path(__FILE__) . 'includes/admin.php');
include_once(plugin_dir_path(__FILE__) . 'includes/frontend.php');

function enqueue_wedding_wishes_styles() {
    wp_enqueue_style('wedding-wishes-style', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0', 'all');
}
add_action('wp_enqueue_scripts', 'enqueue_wedding_wishes_styles');