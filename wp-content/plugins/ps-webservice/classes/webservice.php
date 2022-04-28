<?php
class psws_webservice
{			
	public function psws_getProducts($shop_url, $decrypt_pass)
	{				
		$url = $shop_url . '/api/products/?display=full&output_format=JSON';
		$args = array(
			'headers' => array(
				'Authorization' => 'Basic '.base64_encode( $decrypt_pass.':' )
			),
			'timeout'     => 120
			);
		$response = wp_remote_get( $url, $args );		
		$body = wp_remote_retrieve_body( $response );		
		$array = json_decode($body, true);				

		return $array;
	}


	public function psws_getCustomers($shop_url, $decrypt_pass)
	{				
		$args = array(
			'headers' => array(
				'Authorization' => 'Basic '.base64_encode( $decrypt_pass.':' )
			),
			'timeout'     => 120
			);
		
		//get customers rows	
		$url = $shop_url . '/api/customers/?display=[id,email,firstname,lastname,company,passwd,date_add]&output_format=JSON';
		$response = wp_remote_get( $url, $args );		
		$body = wp_remote_retrieve_body( $response );	
		$result = json_decode( $body, true );
		$customers = $result['customers'];

		//get addresses
		$url = $shop_url . '/api/addresses/?display=[id_customer,firstname,lastname,id_state,id_country,address1,address2,postcode,city,other,phone,phone_mobile,vat_number,dni,company]&output_format=JSON';
		$response = wp_remote_get( $url, $args );
		$body = wp_remote_retrieve_body( $response );	
		$result = json_decode( $body, true );
		$addresses = $result['addresses'];
		
		//get country isos. Only actives
		$url = $shop_url . '/api/countries/?display=[id,iso_code]&filter[active]=[1]&output_format=JSON';
		$response = wp_remote_get( $url, $args );
		$body = wp_remote_retrieve_body( $response );	
		$result = json_decode( $body, true );
		$countries = $result['countries'];

		//change id_country by his iso_code
		foreach( $addresses as $pos => $address ) 
		{	
			$country_pos = array_search($address['id_country'], array_column($countries, 'id'));
			$addresses[$pos]['id_country'] = $countries[$country_pos]['iso_code'];
		}

		//get states. Only actives
		$url = $shop_url . '/api/states/?display=[id,name]&filter[active]=[1]&output_format=JSON';
		$response = wp_remote_get( $url, $args );
		$body = wp_remote_retrieve_body( $response );	
		$result = json_decode( $body, true );
		$states = $result['states'];

		//change id_state by his ISO Code (only for Spain)
		foreach( $addresses as $pos => $address ) 
		{	
			$state_pos = array_search($address['id_state'], array_column($states, 'id'));
			$addresses[$pos]['id_state'] = $states[$state_pos]['iso_code'];
		}

		if (isset($customers))
		{		
			foreach ($customers as $customer_pos => $customer) 
			{		
				//delete registered
				if ( get_user_by( 'email', $customer['email']) ) 
				{
					unset( $customer );

				}else{
					//get item addresses
					$keys = array_keys( array_column( $addresses, 'id_customer'), $customer['id'] );

					if(!empty( $keys ))
					{	
						foreach( $keys as $cont => $key ) 
						{
							//saving max 2 addresses
							if($cont > 1 ) { break;	}	
							$customers[$customer_pos]['addresses'][] = $addresses[$key];	
						}
					}
				}
			}
			//reindexing array
			array_values( $customers );

		}else{	
			echo "error al sincronizar los clientes";
		}

		return $customers;
	}
}

function psws_configuration()
{								
?>
<div class="wrap">								
	<h2>
		<?php _e("Prestashop WebService configuration", "gbc");?>
	</h2>

	<?php 
	settings_fields('psws_config_group');
	$psws_options = get_option('psws_options');
	
	if (isset($_POST['option_pass_ws'])){
		check_admin_referer('save_psws_settings','psws_save_settings');
		$psws_options['option_url_ws']=sanitize_text_field($_POST['option_url_ws']);
		$psws_options['option_pass_ws']=psws_encryption::encrypt($_POST['option_pass_ws']);
		update_option( 'psws_options', $psws_options );						
	}								
	?>

	<form method="post" action="admin.php?page=webservice">
		<?php wp_nonce_field('save_psws_settings','psws_save_settings');?>
		
		<table class="form-table">																				
			<tr>
				<th>
					<?php _e("Prestashop Url:","gbc");?>
				</th>
				<td>							
					<input type="text" name="option_url_ws" required value="<?php echo (isset($psws_options['option_url_ws'])) ? esc_attr($psws_options['option_url_ws']) : '';?>" />
					<em><br><?php _e("Insert http or https without final '/' e.g: https://jjmontalban.github.io or http://jjmontalban.github.io", "gbc");?></em>						</td>
				</td>
			</tr>	
			<tr>
				<th>
					<?php _e("Webservice key:", "gbc");?>
				</th>
				<td>
					<input type="text" name="option_pass_ws" required value="<?php echo (isset($psws_options['option_pass_ws'])) ? psws_encryption::decrypt($psws_options['option_pass_ws']) : '';?>" />			
					<em><br><?php _e("You can make a webservice key from Prestashop -> Admin Information -> Web Service => Active webservice.", "gbc");?></em>					
				</td>
			</tr>	 
		</table>
		<input type="submit" class="button-primary" value="<?php _e("Save", "gbc");?>" /> 
	</form>
	<br>
	<form method="post">
		<input type="submit" name="customers" class="button-primary" value="<?php _e("Check Customers", "gbc");?>">	
		<input type="submit" name="otro" class="button-primary" value="<?php _e("Check Otra cosa", "gbc");?>">	
	</form>

	<?php if( isset( $_POST['customers'] ) ) { check_customers(); } ?>
	<?php if( isset( $_POST['otro'] ) ) { echo "es lo siguiente"; } ?>

</div>
    
    <?php
}

function check_customers() 
{
	global $wpdb;
	$psws_options = get_option('psws_options');	
	$decrypt_pass = psws_encryption::decrypt($psws_options['option_pass_ws']);						
	$shop_url = esc_attr($psws_options['option_url_ws']);	
	$webService = new psws_webservice();

	$customers = $webService->psws_getCustomers($shop_url, $decrypt_pass);		
	
	if (isset( $customers ))
	{
		foreach ( $customers as $customer_pos => $customer )
		{			
			$userdata = array(
				'ID' => $customer['id'],
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

			$user = get_userdata( $customer['id'] );

			if ( $user === false ) {

				//user id dont exist, to creating an user with same Prestashop ID
				if( !get_user_by( 'email', $customer['email'] )) {	
						$wpdb->insert( $wpdb->users, array(
										'ID' => $customer['id'],
										'user_login' => $customer['email'],
										) 
								);
				}

				$user_id = wp_insert_user( $userdata );

				if (is_wp_error( $user_id )) {
					echo json_encode( array( 'resp' => 'error', 'message' => $user_id->get_error_message() ) );
					exit;
				}
			}else{
				continue;
			}

			//woo metas

			update_user_meta( $user_id, "billing_first_name", $customers[$customer_pos]['addresses'][0]['firstname'] );
			update_user_meta( $user_id, "billing_last_name", $customers[$customer_pos]['addresses'][0]['lastname']);
			
			//get company name (for billing)
			if ( !empty( $customers[$customer_pos]['company']) ) {
				update_user_meta( $user_id, "billing_company", $customers[$customer_pos]['company']);

			}else if( !empty( $customers[$customer_pos]['addresses'][0]['company'] ) ){
				update_user_meta( $user_id, "billing_company", $customers[$customer_pos]['addresses'][0]['company']);
			
			}else if( !empty( $customers[$customer_pos][1]['company']) ) {
				update_user_meta( $user_id, "billing_company", $customers[$customer_pos]['addresses'][1]['company']);
			}

			update_user_meta( $user_id, "billing_email", $customers[$customer_pos]['email'] );
			update_user_meta( $user_id, "billing_address_1", $customers[$customer_pos]['addresses'][0]['address1']);
			update_user_meta( $user_id, "billing_address_2", $customers[$customer_pos]['addresses'][0]['address2'] );
			update_user_meta( $user_id, "billing_city", $customers[$customer_pos]['addresses'][0]['city']);
			update_user_meta( $user_id, "billing_postcode", $customers[$customer_pos]['addresses'][0]['postcode'] );
			update_user_meta( $user_id, "billing_country", $customers[$customer_pos]['addresses'][0]['id_country']);
			
			//States for Spanish customers
			if( $customers[$customer_pos]['addresses'][0]['id_country'] == 'ES' ) {
				update_user_meta( $user_id, "billing_state", $customers[$customer_pos]['addresses'][0]['id_state'] );
			}

			//get phones (for billing)
			if( !empty( $customers[$customer_pos]['addresses'][0]['phone_mobile']) ) {
				update_user_meta( $user_id, "billing_phone", $customers[$customer_pos]['addresses'][0]['phone_mobile']);
			}else {
				update_user_meta( $user_id, "billing_phone", $customers[$customer_pos]['addresses'][0]['phone']);
			} 

			update_user_meta( $user_id, "shipping_first_name", $customers[$customer_pos]['addresses'][1]['firstname']);
			update_user_meta( $user_id, "shipping_last_name", $customer[$customer_pos]['addresses'][1]['lastname']);
			
			//get company name (for shipping)
			if ( !empty( $customers[$customer_pos]['company']) ) {
				update_user_meta( $user_id, "shipping_company", $customers[$customer_pos]['company']);

			}else if( !empty( $customers[$customer_pos]['addresses'][1]['company'] ) ){
				update_user_meta( $user_id, "shipping_company", $customers[$customer_pos]['addresses'][1]['company']);
			
			}else if( !empty( $customers[$customer_pos][1]['company']) ) {
				update_user_meta( $user_id, "shipping_company", $customers[$customer_pos]['addresses'][0]['company']);
			}else {
				update_user_meta( $user_id, "shipping_company", get_user_meta( $user_id, 'billing_company' , true ) );
			}

			update_user_meta( $user_id, "shipping_address_1", $customers[$customer_pos]['addresses'][1]['address1']);
			update_user_meta( $user_id, "shipping_address_2", $customers[$customer_pos]['addresses'][1]['address2']);
			update_user_meta( $user_id, "shipping_city", $customers[$customer_pos]['addresses'][1]['city']);
			update_user_meta( $user_id, "shipping_postcode", $customers[$customer_pos]['addresses'][1]['postcode'] );
			
			//get phones (for shipping)
			if( !empty( $customers[$customer_pos]['addresses'][1]['phone_mobile'] )) {
				update_user_meta( $user_id, "shipping_phone", $customers[$customer_pos]['addresses'][1]['phone_mobile']);
			}else if( !empty( $customers[$customer_pos]['addresses'][1]['phone'] ) ){
				update_user_meta( $user_id, "shipping_phone", $customers[$customer_pos]['addresses'][1]['phone']);
			}else {
				update_user_meta( $user_id, "shipping_phone", get_user_meta( $user_id, 'billing_phone' , true ) );
			} 

			update_user_meta( $user_id, "shipping_country", $customers[$customer_pos]['addresses'][1]['id_country']);
			
			//States for Spanish customers
			if( $customers[$customer_pos]['addresses'][1]['id_country'] == 'ES' ) {
				update_user_meta( $user_id, "shipping_state", $customers[$customer_pos]['addresses'][1]['id_state']);
			}
		}

	}else{
		echo "No hay clientes que sincronizar";
	}		

	return "Se sincronizaron" . count( $customers ) . "clientes";
}



function psws_shortcode_products($atts) {		        

	$psws_options = get_option('psws_options');	
	$decrypt_pass = psws_encryption::decrypt($psws_options['option_pass_ws']);						
	$shop_url = esc_attr($psws_options['option_url_ws']);	
	$webService = new psws_webservice();							
	$xml = $webService->psws_getProducts($shop_url, $decrypt_pass);		
	$resources = $xml['products'];						
	$content='<ul class="short-products">';

	if (isset($resources)){		
		foreach ($resources as $resource){		
			$name = $resource['name'][1]['value'];
			$link = $shop_url . '/' . $resource['link_rewrite'][1]['value'];		
			$content .= "<li>";					
			$content .= "<a title='".$name."' href='".$link."' target='_blank'>";																						
			$content .= $name;
			$content .= "</a><br>";																			
			$content .= "</li>";							
		}	
	}else{
		$content="";
	}			

	return $content;
}
add_shortcode('psws_products', 'psws_shortcode_products');


class psws_encryption{	

	public static function encrypt($string){	
		global $wpdb;
		$key ='asasest&A2oeds3-asdwas23'.$wpdb->base_prefix.'Acunt#33ddasd_asextod2Dseprueba31';		
		$iv = '12as16as78as12as';			
		$encrypted = openssl_encrypt($string,'AES-256-CBC',$key,0,$iv); 
		
		return $encrypted; 
	} 

	public static function decrypt($string){  		
		global $wpdb;
		$key ='asasest&A2oeds3-asdwas23'.$wpdb->base_prefix.'Acunt#33ddasd_asextod2Dseprueba31';	
		$iv = '12as16as78as12as';	   	
	   	$decrypted = openssl_decrypt($string,'AES-256-CBC',$key,0,$iv); 	
		
		return $decrypted;
	}
}