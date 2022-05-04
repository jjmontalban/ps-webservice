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

	// get Prestashop user & address data via webservice
	// custom mapping. 
	
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

		//get addresses rows
		$url = $shop_url . '/api/addresses/?display=[id_customer,firstname,lastname,id_state,id_country,address1,address2,postcode,city,alias,phone,phone_mobile,vat_number,dni,company]&filter[deleted]=[0]&output_format=JSON';
		$response = wp_remote_get( $url, $args );
		$body = wp_remote_retrieve_body( $response );	
		$result = json_decode( $body, true );
		$addresses = $result['addresses'];
		
		//get country rows with iso codes and only actives
		$url = $shop_url . '/api/countries/?display=[id,iso_code]&filter[active]=[1]&output_format=JSON';
		$response = wp_remote_get( $url, $args );
		$body = wp_remote_retrieve_body( $response );	
		$result = json_decode( $body, true );
		$countries = $result['countries'];

		//change id_country by his iso_code and store in $addresses array
		foreach( $addresses as $pos => $address ) 
		{	
			$country_pos = array_search($address['id_country'], array_column($countries, 'id'));
			$addresses[$pos]['id_country'] = $countries[$country_pos]['iso_code'];
		}

		//get state rows with iso codes and only actives
		$url = $shop_url . '/api/states/?display=[id,name, iso_code]&filter[active]=[1]&output_format=JSON';
		$response = wp_remote_get( $url, $args );
		$body = wp_remote_retrieve_body( $response );	
		$result = json_decode( $body, true );
		$states = $result['states'];

		//change id_state by his ISO Code and store in $addresses array
		foreach( $addresses as $pos => $address ) 
		{	
			$state_pos = array_search($address['id_state'], array_column($states, 'id'));
			$addresses[$pos]['id_state'] = $states[$state_pos]['iso_code'];
		}

		if ( isset( $customers ) )
		{	
			//get id_customer field for each address
			$valor_col = array_column( $addresses, 'id_customer');

			foreach ( $customers as $pos => $customer ) 
			{		
				//if email is not registered
				if ( !get_user_by( 'email', $customer['email'] ) ) 
				{
					//Get id_address addresses of this customer
					$keys = array_keys( $valor_col, $customer['id'] );

					if( isset( $keys[0] ) )			
						$customers[$pos]['addresses'][0] = $addresses[ $keys[0] ];	 

					//saving max 2 addresses
					if( isset( $keys[1] ) ) 
						$customers[$pos]['addresses'][1] = $addresses[ $keys[1] ];	 					
				}
			}

		} else {	
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
		<?php _e( "Prestashop WebService configuration", "psws" );?>
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
					<?php _e( "Prestashop URL:", "psws" );?>
				</th>
				<td>							
					<input type="text" name="option_url_ws" required value="<?php echo (isset($psws_options['option_url_ws'])) ? esc_attr($psws_options['option_url_ws']) : '';?>" />
					<em><br><?php _e( "Insert http or https without final '/' e.g: https://jjmontalban.github.io or http://jjmontalban.github.io", "psws" );?></em>						</td>
				</td>
			</tr>	
			<tr>
				<th>
					<?php _e( "Webservice Key:", "psws" );?>
				</th>
				<td>
					<input type="text" name="option_pass_ws" required value="<?php echo (isset($psws_options['option_pass_ws'])) ? psws_encryption::decrypt($psws_options['option_pass_ws']) : '';?>" />			
					<em><br><?php _e( "You can make a webservice key from Prestashop -> Admin Information -> Web Service => Active webservice.", "psws" );?></em>					
				</td>
			</tr>	 
		</table>
		<input type="submit" class="button-primary" value="<?php _e( "Save Configuration", "psws" );?>" /> 
	</form>
	<br>
	<form method="post">
		<input type="submit" name="customers" class="button-primary" value="<?php _e( "Sincronize Customers", "psws" );?>">
	</form>

	<?php if( isset( $_POST['customers'] ) ) { check_customers(); } ?>

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