<?php

//Includes
require_once plugin_dir_path( __FILE__ ) . 'class-webb-service.php';
require_once plugin_dir_path( __FILE__ ) . 'class-encryption.php';

function sync_products($atts) {		        

$psws_options = get_option('psws_options');	
$decrypt_pass = Encryption::decrypt($psws_options['option_pass_ws']);						
$shop_url = esc_attr($psws_options['option_url_ws']);	
$webService = new Web_Service();							
$xml = $webService->get_products($shop_url, $decrypt_pass);		
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
