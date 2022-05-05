<?php

//Includes
require_once plugin_dir_path( __FILE__ ) . 'class-encryption.php';

function sync_customers() 
{
	global $wpdb;
	$psws_options = get_option('psws_options');	
	$decrypt_pass = Encryption::decrypt($psws_options['option_pass_ws']);						
	$shop_url = esc_attr($psws_options['option_url_ws']);	
	$webService = new Webservice();
	$customers = $webService->get_customers($shop_url, $decrypt_pass);		
	$cont = 0;

	if (isset( $customers ))
	{
		foreach ( $customers as $customer_pos => $customer )
		{			
			if( empty( get_user_by( 'login', $customer['email'] ) ) && empty( get_user_by( 'email', $customer['email'] ) ) ) 
			{
				$userdata = array(
					'user_login' => $customer['email'],
					'nickname' => $customer['email'],
					'display_name' => $customer['firstname'],
					'user_pass' => $customer['passwd'],
					'user_email' => $customer['email'],
					'first_name' => $customer['firstname'],
					'last_name' => $customer['lastname'],
					'role' => 'customer',
					'user_registered' => $customer['date_add'],
				);

				$user_id = wp_insert_user( $userdata );
				$cont++;

				//if got error
				if (is_wp_error( $user_id )) {
					echo json_encode( array( 'resp' => 'error', 'message' => $user_id->get_error_message() ) );
					exit;
				}
				else{

					//woo metas
					update_user_meta( $user_id, "billing_email", $customer['email'] );

					$billing_first_name = $customer['addresses'][0]['firstname'] ?? '';
					update_user_meta( $user_id, "billing_first_name", $billing_first_name );

					$billing_last_name = $customer['addresses'][0]['lastname'] ?? '';
					update_user_meta( $user_id, "billing_last_name", $billing_last_name );

					$billing_address_1 = $customer['addresses'][0]['address1']  ?? ''; 
					update_user_meta( $user_id, "billing_address_1", $billing_address_1 );

					$billing_address_2 = $customer['addresses'][0]['address2'] ?? '';
					update_user_meta( $user_id, "billing_address_2", $billing_address_2 );

					$billing_city = $customer['addresses'][0]['city'] ?? '';
					update_user_meta( $user_id, "billing_city", $billing_city );

					$billing_postcode = $customer['addresses'][0]['postcode'] ?? '';
					update_user_meta( $user_id, "billing_postcode", $billing_postcode );

					$billing_country = $customer['addresses'][0]['id_country'] ?? '';
					update_user_meta( $user_id, "billing_country", $billing_country );
					
					$billing_state = $customer['addresses'][0]['id_state'] ?? '';
					update_user_meta( $user_id, "billing_state", $billing_state );
			
					//get phones (for billing)
					$billing_phone = $customer['addresses'][0]['phone_mobile'] ?? '';
					update_user_meta( $user_id, "billing_phone", $billing_phone );
					
					//Custom billings
					$billing_phone2 = $customer['addresses'][0]['phone'] ?? '';
					update_user_meta( $user_id, "billing_phone2", $billing_phone2 );

					$billing_cif = $customer['addresses'][0]['dni'] ?? '';
					update_user_meta( $user_id, "billing_cif", $billing_cif );

					$billing_vat = $customer['addresses'][0]['vat_number'] ?? '';
					update_user_meta( $user_id, "billing_vat", $billing_vat );
					
					$billing_alias = $customer['addresses'][0]['alias'] ?? '';
					update_user_meta( $user_id, "billing_alias", $billing_alias );
					
					
					//second address.
					$shipping_address_1 = $customer['addresses'][1]['address1'] ?? '';
					update_user_meta( $user_id, "shipping_address_1", $shipping_address_1 );
					
					$shipping_address_2 = $customer['addresses'][1]['address2'] ?? '';
					update_user_meta( $user_id, "shipping_address_2", $shipping_address_2 );
					
					$shipping_city = $customer['addresses'][1]['city'] ?? '';
					update_user_meta( $user_id, "shipping_city", $shipping_city );

					$shipping_postcode = $customer['addresses'][1]['postcode'] ?? '';
					update_user_meta( $user_id, "shipping_postcode", $shipping_postcode );

					$shipping_first_name =  $customer['addresses'][1]['firstname'] ?? '';
					update_user_meta( $user_id, "shipping_first_name", $shipping_first_name );
					
					$shipping_last_name = $customer['addresses'][1]['lastname'] ?? '';
					update_user_meta( $user_id, "shipping_last_name", $shipping_last_name );
					
					$shipping_country = $customer['addresses'][1]['id_country']  ?? '';
					update_user_meta( $user_id, "shipping_country", $shipping_country );
					
					$shipping_state = $customer['addresses'][1]['id_state'] ?? '';
					update_user_meta( $user_id, "shipping_state", $shipping_state );

					$shipping_phone = $customer['addresses'][1]['phone'] ?? '';
					update_user_meta( $user_id, "shipping_phone", $shipping_phone );
					
					//Custom shippings
					$shipping_phone2 = $customer['addresses'][1]['phone_mobile'] ?? '';
					update_user_meta( $user_id, "shipping_phone2", $shipping_phone2 );
	
					$shipping_alias = $customer['addresses'][1]['alias'] ?? '';
					update_user_meta( $user_id, "shipping_alias", $shipping_alias );

					//get company name (for billing). 
					//Prestashop is redundant when saving the company name
					//Is stored in customer data and in each customer address...
					if ( isset( $customer['company'] ) ) {
						update_user_meta( $user_id, "billing_company", $customer['company']);

					}else if( isset( $customer['addresses'][0]['company'] ) ){
						update_user_meta( $user_id, "billing_company", $customer['addresses'][0]['company']);
					
					}else if( isset( $customer['addresses'][1]['company'] ) ) {
						update_user_meta( $user_id, "billing_company", $customer['addresses'][1]['company']);
					}
					//get company name (for shipping)
					if ( isset( $customer['company'] ) ) {
						update_user_meta( $user_id, "shipping_company", $customer['company']);

					}else if( isset( $customer['addresses'][1]['company'] ) ){
						update_user_meta( $user_id, "shipping_company", $customer['addresses'][1]['company']);
					}
				}

			}else {
					continue;
				  }
	
		}

	}	

 	echo "Se sincronizaron " . $cont . " clientes";
}