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

$plugin = plugin_basename( __FILE__ );

//Adding styles
function psws_custom_styles() {
    wp_enqueue_style('custom-styles', plugins_url('/css/styles.css', __FILE__ ));
}
add_action('admin_enqueue_scripts', 'psws_custom_styles');

//Load plugin translated strings
function psws_plugin_load_textdomain() {
    load_plugin_textdomain( 'gbc', false, basename( dirname( __FILE__ ) ) . '/lang' ); 
}
add_action( 'plugins_loaded', 'psws_plugin_load_textdomain' );

//Check if WooCommerce is activated
if ( ! function_exists( 'is_woocommerce_activated' ) ) {
    function is_woocommerce_activated() {
        if ( class_exists( 'woocommerce' ) ) { return true; } else { return false; }
    }
}

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
    add_menu_page(__('PS Webservice', 'gbc'), __('PS Webservice', 'gbc'), 'activate_plugins', 'webservice', 'psws_configuration');
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
    load_plugin_textdomain('gbc', false, dirname(plugin_basename(__FILE__)));
}
add_action('init', 'psws_languages');