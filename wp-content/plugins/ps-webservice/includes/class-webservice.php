<?php

class Webservice
{			
	public function get_products($shop_url, $decrypt_pass)
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

	//Get Prestashop customers data with his adddresses: countries and states.
	// custom mapping. 
	public function get_customers($shop_url, $decrypt_pass)
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

