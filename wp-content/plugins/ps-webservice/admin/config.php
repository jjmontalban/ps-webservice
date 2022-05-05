<?php

//Includes
require_once plugin_dir_path( __FILE__ ) . '../includes/customer.php';
require_once plugin_dir_path( __FILE__ ) . '../includes/class-encryption.php';

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
		$psws_options['option_url_ws'] = sanitize_text_field($_POST['option_url_ws']);
		$psws_options['option_pass_ws'] = Encryption::encrypt($_POST['option_pass_ws']);
		update_option( 'psws_options', $psws_options );						
	}								
	?>

	<form method="post" action="admin.php?page=webservice">
		<?php wp_nonce_field( 'save_psws_settings','psws_save_settings' );?>
		
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
					<input type="text" name="option_pass_ws" required value="<?php echo (isset($psws_options['option_pass_ws'])) ? Encryption::decrypt($psws_options['option_pass_ws']) : '';?>" />			
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

	<?php if( isset( $_POST['customers'] ) ) { sync_customers(); } ?>

</div>
<?php
}

