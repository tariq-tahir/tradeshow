<?php
/**
 *
 * This theme uses PSR-4 and OOP logic instead of procedural coding
 * Every function, hook and action is properly divided and organized inside related folders and files
 * Use the file `inc/Custom/Custom.php` to write your custom functions
 *
 * @package awps
 */

if ( file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) :
	require_once dirname( __FILE__ ) . '/vendor/autoload.php';
endif;

if ( class_exists( 'Awps\\Init' ) ) :
	Awps\Init::register_services();
endif;


// Load SupplierProfile class
require_once get_template_directory() . '/inc/Suppliers/SupplierProfile.php';


// Redirect CPT archive to prettier /products/ URL
add_action('template_redirect', function() {
    if (is_post_type_archive('product') && !is_paged()) {
        wp_safe_redirect(home_url('/products/'), 301);
        exit;
    }
});





add_action( 'awps_breadcrumb', 'awps_render_breadcrumb' );
function awps_render_breadcrumb() {
    if ( function_exists( 'awps_custom_breadcrumb' ) ) {
        awps_custom_breadcrumb();
    } elseif ( function_exists( 'do_shortcode' ) ) {
        echo do_shortcode( '[awps_custom_breadcrumb]' );
    }
}
