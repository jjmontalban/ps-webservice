<?php
/**
 * Redirect functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package WordPress
 * 
 * 
 */




 /**
 * WordPress function for redirecting users to login
 */
function jj_login_redirect( ) {
            return admin_url();
       
}
add_filter( 'login_redirect', 'jj_login_redirect', 1);


/**
 * Login Page customized 
 */

//https://codex.wordpress.org/Customizing_the_Login_Form
//https://blogtimenow.com/wordpress/customize-change-wordpress-login-logo-page/
//https://wordpress.stackexchange.com/questions/99027/remove-links-from-login-page

function my_login_logo() { 
    ?>
    <style type="text/css">
      #login h1 a  {
        background-image: url('<?php echo get_stylesheet_directory_uri();?>/img/logo.png');
        background-size:100% 100%;
        background-position:top center;
        background-repeat:no-repeat;
        width:326px;
        height:67px;
        text-indent:-9999px;
        outline:0;
        overflow:hidden;
        padding-bottom:15px;
        display:block;
      }
     
         
      .language-switcher, .privacy-policy-page-link, #nav, #backtoblog{
          display:none
      }    
    </style>
    <?php 
  }
add_action( 'login_enqueue_scripts', 'my_login_logo' );