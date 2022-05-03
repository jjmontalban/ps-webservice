<?php
/**
* Plugin Name: PS Webservice
* Description: Plugin to sincronize customers Prestashop via webservice
* Version:     1.0.0
* Plugin URI:  https://jjmontalban.github.io
* Author:      JJMontalban
* Author URI:  https://jjmontalban.github.io
* License:     GPLv2 or later
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
* Text Domain: psws
* Domain Path: /languages
*/

defined( 'ABSPATH' ) or die( '' );

//Includes
require plugin_dir_path( __FILE__ ) . 'classes/webservice.php';

global $wpdb_db_version;
$wpdb_db_version = '1.0.0'; 

//Load plugin translated strings
function psws_plugin_load_textdomain() {
    load_plugin_textdomain( 'psws', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' ); 
}
add_action( 'init', 'psws_plugin_load_textdomain' );

//check if woocommerce active
function woo_activate() {
    $plugin = plugin_basename( __FILE__ );

    if ( !class_exists ('Woocommerce') ) 
    { 
        // Message error + allow back link.
        deactivate_plugins( $plugin );
        wp_die( _e( "This plugin requires Woocommerce to be installed and activated.", "psws" ), _e( "Error", "psws" ), array( 'back_link' => true ) );        
    }
}
register_activation_hook( __FILE__, 'woo_activate' ); // Register myplugin_activate on

function psws_install()
{
    global $wpdb_db_version;

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    // version
    add_option('psws_db_version', $wpdb_db_version);

    $installed_ver = get_option('psws_db_version');

    if ($installed_ver != $wpdb_db_version) {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        // version
        update_option('psws_db_version', $wpdb_db_version);
    }
}
register_activation_hook(__FILE__, 'psws_install');

function psws_update_db_check()
{
    global $wpdb_db_version;
    if (get_site_option('psws_db_version') != $wpdb_db_version) {
        psws_install();
    }
}
add_action('plugins_loaded', 'psws_update_db_check');

function psws_admin_menu()
{
    add_menu_page( __('PS Webservice', 'psws'), __('PS Webservice', 'psws'), 'activate_plugins', 'webservice', 'psws_configuration');
}
add_action('admin_menu', 'psws_admin_menu');

function psws_settings(){		
    register_setting('psws_config_group', 'psws_options', 'psws_sanitize');		
}
add_action('admin_init', 'psws_settings');

function psws_sanitize($input){
    return $input;
}

function psws_languages()
{
    load_plugin_textdomain('psws', false, dirname(plugin_basename( __FILE__ )));
}
add_action('init', 'psws_languages');