<?php

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