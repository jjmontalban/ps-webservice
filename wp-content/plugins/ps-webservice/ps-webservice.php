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
    add_menu_page( "PS Webservice", "PS Webservice", 'activate_plugins', 'webservice', 'psws_configuration');
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


/**
*  Insert custom fields in woo metas
**/
//Prestashop store 2 phones by each address. Woocommerce only 1 each address

//Billing
function custom_woocommerce_billing_fields($fields)
{
    $fields['billing_phone2'] = array(
        'label' => __('Phone 2', 'psws'), // Add custom field label
        'required' => false,
        'clear' => false, // add clear or not
        'type' => 'text', // add field type
        'class' => array('input-text'),    // add class name
    );    
    
    $fields['billing_cif'] = array(
        'label' => __('CIF/NIF', 'psws'), // Add custom field label
        'required' => false,
        'clear' => false, // add clear or not
        'type' => 'text', // add field type
        'class' => array('input-text'),    
    );
    
    $fields['billing_vat'] = array(
        'label' => __( "VAT Number", "psws" ), // Add custom field label
        'required' => false,
        'clear' => false, // add clear or not
        'type' => 'text', // add field type
        'class' => array('input-text'),    
    );
    
    
    $fields['billing_alias'] = array(
        'label' => __( "Address name", "psws" ), // Add custom field label
        'required' => false,
        'clear' => false, // add clear or not
        'type' => 'text', // add field type
        'class' => array('input-text'),    
    );
    
        return $fields;
}
add_filter('woocommerce_billing_fields', 'custom_woocommerce_billing_fields');

//Shipping
function custom_woocommerce_shipping_fields($fields)
{
    $fields['shipping_phone2'] = array(
        'label' => __( "Phone 2", "psws" ), // Add custom field label
        'required' => false,
        'clear' => false, // add clear or not
        'type' => 'text', // add field type
        'class' => array('input-text'),    // add class name
        //'priority' => 5, To change the field location increase or decrease this value
    );
    
    $fields['shipping_alias'] = array(
        'label' => __("Address Name", "psws"), // Add custom field label
        'required' => false,
        'clear' => false, // add clear or not
        'type' => 'text', // add field type
        'class' => array('input-text'),    // add class name
        'priority' => 1,  //To change the field location increase or decrease this value
    );
    
    return $fields;
}
add_filter('woocommerce_shipping_fields', 'custom_woocommerce_shipping_fields');

// Admin customer profile page
function custom_woocommerce_customer_meta_fields( $fields ) {
    
    $fields['billing']['fields']['billing_phone2'] = array( 'label' => __( "Phone 2", "psws" ), 'description' => '' );
    $fields['billing']['fields']['billing_cif'] = array( 'label' => __( "CIF/NIF", "psws" ), 'description' => '' );
    $fields['billing']['fields']['billing_vat'] = array( 'label' => __( "VAT Number", "psws" ), 'description' => '' );
    $fields['billing']['fields']['billing_alias'] = array( 'label' => __( "Address Name", "psws" ), 'description' => '' );

    $fields['shipping']['fields']['shipping_phone2'] = array( 'label' => __( 'Phone 2', "psws" ), 'description' => '' );
    $fields['shipping']['fields']['shipping_alias'] = array( 'label' => __( 'Address Name', "psws" ), 'description' => '' );
		
    return $fields;
}
add_filter( 'woocommerce_customer_meta_fields', 'custom_woocommerce_customer_meta_fields' );

