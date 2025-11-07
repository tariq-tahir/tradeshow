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

//(new \AWPS\Exporters\ServiceProvider());


//Add Exporter Dropdown in Products
add_action('init', function() {
    add_post_type_support('product', 'author');
});


// Redirect logged-out users away from /dashboard
add_action('template_redirect', function () {
    if ( is_page('dashboard') && !is_user_logged_in() ) {
        wp_redirect( site_url('/my-account/') );
        exit;
    }
});



// 1. Redirect exporters after login
add_filter('woocommerce_login_redirect', function($redirect, $user) {
    if (in_array('exporter', (array) $user->roles)) {
        return site_url('/dashboard/');
    }
    return $redirect;
}, 10, 2);

// 2. Prevent exporters from accessing /my-account/
add_action('template_redirect', function() {
    if (is_account_page() && is_user_logged_in()) {
        $user = wp_get_current_user();
        if (in_array('exporter', (array) $user->roles)) {
            wp_redirect(site_url('/dashboard/'));
            exit;
        }
    }
});















function awps_url_to_attachment_id( $url ) {
    global $wpdb;
    $url = esc_url_raw($url);
    return $wpdb->get_var(
        $wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid=%s", $url)
    );
}





//Enqueue cropper files for User Profile Front End and Backend Users Page
function enqueue_cropper_assets() {
    wp_enqueue_style( 'cropper-css', get_template_directory_uri() . '/assets/dist/css/cropper.min.css' );
    wp_enqueue_script( 'cropper-js', get_template_directory_uri() . '/assets/dist/js/cropper.min.js', [], null, true );
}
add_action( 'wp_enqueue_scripts', 'enqueue_cropper_assets' );











// Enable / Disable Company & its products
// Example: Modifying the main WooCommerce product query
add_action('pre_get_posts', 'exclude_products_from_disabled_companies');
function exclude_products_from_disabled_companies($query) {
    // Only affect the main query on the front end for product post types
    if (!is_admin() && $query->is_main_query() && $query->get('post_type') === 'product') {
        
        // Get all user IDs where company_status is 'disabled'
        $disabled_users = get_users([
            'fields'     => 'ID',
            'meta_query' => [
                [
                    'key'     => 'company_status',
                    'value'   => 'disabled',
                    'compare' => '='
                ]
            ]
        ]);

        if (!empty($disabled_users)) {
            // Add a meta query to exclude products linked to these disabled users
            $meta_query = $query->get('meta_query', []);

            $meta_query[] = [
                'key'     => '_exporter_user_id', // Replace with your actual meta key
                'value'   => $disabled_users,
                'compare' => 'NOT IN'
            ];

            $query->set('meta_query', $meta_query);
        }
    }
}

// Prevent disabled exporters from logging in
add_filter('authenticate', 'check_exporter_status_on_login', 30, 3);
function check_exporter_status_on_login($user, $username, $password) {
    // If authentication has already failed (e.g., wrong password), don't interfere
    if (is_wp_error($user)) {
        return $user;
    }

    // If no user found, don't interfere
    if (empty($user)) {
        return $user;
    }

    // Check if the user has the 'exporter' role
    if (in_array('exporter', (array) $user->roles)) {
        // Get the company status
        $company_status = get_user_meta($user->ID, 'company_status', true);

        // If the company is disabled, block the login
        if ($company_status === 'disabled') {
            // Return a WP_Error to block login
            return new WP_Error(
                'account_disabled',
                __('Your account has been disabled. Please contact the administrator.', 'your-textdomain')
            );
        }
    }

    // If everything is OK, return the user object
    return $user;
}


// Display custom message on login page
add_action('login_form', 'display_disabled_account_message');
function display_disabled_account_message() {
    if (isset($_GET['login']) && $_GET['login'] === 'disabled') {
        echo '<p class="message" style="padding: 12px; margin: 12px 0; background-color: #ffebe8; color: #a00;">' .
             __('Your account has been disabled. Please contact the administrator.', 'your-textdomain') .
             '</p>';
    }
}





// Redirect User if account is disabled
add_action('template_redirect', 'check_exporter_account_status');
function check_exporter_account_status() {
    // Only run this check on the exporter dashboard pages
    if (is_page('dashboard') || strpos($_SERVER['REQUEST_URI'], '/dashboard/') !== false) {
        $current_user = wp_get_current_user();
        
        // Check if user is logged in and is an exporter
        if ($current_user->ID && in_array('exporter', (array) $current_user->roles)) {
            $company_status = get_user_meta($current_user->ID, 'company_status', true);
            if ($company_status === 'disabled') {
                // Clear any output that might have started
                if (ob_get_level()) {
                    ob_end_clean();
                }
                // Log them out
                wp_logout();
                // Redirect to FRONTEND login page with an error message
                wp_redirect(add_query_arg('login', 'disabled', wc_get_page_permalink('myaccount')));
                exit;
            }
        }
        
        // If user is not logged in, redirect to FRONTEND login page
        if (!$current_user->ID) {
            wp_redirect(wc_get_page_permalink('myaccount'));
            exit;
        }
    }
}

// Display custom message on frontend login page if account is disabled
add_action('woocommerce_before_customer_login_form', 'show_disabled_account_message_on_frontend');
function show_disabled_account_message_on_frontend() {
    if (isset($_GET['login']) && $_GET['login'] === 'disabled') {
        wc_print_notice(
            'Your company account has been disabled. Please contact the administrator for assistance.',
            'error'
        );
    }
}




// Ensure product data is set up on single product pages
add_action('wp', 'ensure_product_data_setup');
function ensure_product_data_setup() {
    if (is_product() && !did_action('woocommerce_setup_product_data')) {
        global $wp_query;
        if ($wp_query->post) {
            wc_setup_product_data($wp_query->post);
        }
    }
}



/**
 * Restrict media library to current user's uploads (frontend only)
 */
function awps_restrict_media_library_to_user($query) {
    // Only apply on frontend dashboard pages
    if (!is_admin() && is_user_logged_in()) {
        $user_id = get_current_user_id();
        if ($user_id && isset($query['author']) && $query['author'] === '0') {
            $query['author'] = $user_id;
        } elseif (!isset($query['author'])) {
            $query['author'] = $user_id;
        }
    }
    return $query;
}
add_filter('ajax_query_attachments_args', 'awps_restrict_media_library_to_user');






// Handle product visibility toggle via AJAX
add_action('wp_ajax_toggle_product_visibility', 'awps_toggle_product_visibility');
function awps_toggle_product_visibility() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['visibility_nonce'], 'toggle_visibility')) {
        wp_send_json_error();
    }

    $product_id = intval($_POST['product_id']);
    $user_id = get_current_user_id();

    // Security: ensure user owns the product
    if (get_post_field('post_author', $product_id) != $user_id) {
        wp_send_json_error();
    }

    $current_status = get_post_status($product_id);
    $new_status = ($current_status === 'publish') ? 'draft' : 'publish';
    wp_update_post(['ID' => $product_id, 'post_status' => $new_status]);

    wp_send_json_success(['status' => $new_status]);
}


















// 1. Remove WooCommerce styles and scripts completely
add_action('wp_enqueue_scripts', 'remove_all_woocommerce_assets', 100);
function remove_all_woocommerce_assets() {
    // Dequeue WooCommerce frontend styles
    wp_dequeue_style('woocommerce-general');
    wp_dequeue_style('woocommerce-layout');
    wp_dequeue_style('woocommerce-smallscreen');
    wp_dequeue_style('wc-blocks-style');
    wp_dequeue_style('brands-styles');

    // Dequeue WooCommerce scripts
    wp_dequeue_script('wc-add-to-cart');
    wp_dequeue_script('woocommerce');
    wp_dequeue_script('wc-cart-fragments');
    wp_dequeue_script('jquery-blockui');
    wp_dequeue_script('js-cookie');
    wp_dequeue_script('sourcebuster-js');
    wp_dequeue_script('wc-order-attribution');

    // Deregister to prevent re-enqueue
    wp_deregister_style('woocommerce-general');
    wp_deregister_style('woocommerce-layout');
    wp_deregister_style('woocommerce-smallscreen');
    wp_deregister_style('wc-blocks-style');
    wp_deregister_style('brands-styles');

    wp_deregister_script('wc-add-to-cart');
    wp_deregister_script('woocommerce');
    wp_deregister_script('wc-cart-fragments');
}

// 2. Prevent WooCommerce from enqueuing anything in the first place
add_filter('woocommerce_enqueue_styles', '__return_empty_array');
add_filter('woocommerce_enable_cart_fragmentation', '__return_false');

// 3. Remove inline WooCommerce CSS (like .required visibility)
add_action('wp_print_styles', 'remove_woocommerce_inline_css', 999);
function remove_woocommerce_inline_css() {
    wp_dequeue_style('woocommerce-inline');
    // Also remove if it's printed via wp_add_inline_style elsewhere
    global $wp_styles;
    if (isset($wp_styles->registered['woocommerce-inline'])) {
        unset($wp_styles->registered['woocommerce-inline']);
    }
}

// 4. Remove WordPress emoji scripts and styles
function disable_wp_emoji() {
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
    add_filter('emoji_svg_url', '__return_false');
}
add_action('init', 'disable_wp_emoji');

// 5. Remove WordPress block library & global styles (if you don't use Gutenberg blocks)
function remove_block_library_css() {
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('wp-block-library-theme');
    wp_dequeue_style('global-styles');
    wp_dequeue_style('classic-theme-styles');
}
add_action('wp_enqueue_scripts', 'remove_block_library_css', 100);

// 1. Disable speculation rules (removes <script type="speculationrules">)
add_filter('wp_speculation_rules', '__return_empty_array');

// 2. Try standard removal of WooCommerce no-js script
add_action('init', function() {
    remove_action('wp_footer', 'woocommerce_no_js', 10);
});

// 3. Fallback: Strip it from final HTML if still present
add_action('template_redirect', 'start_remove_woocommerce_nojs_buffer');
function start_remove_woocommerce_nojs_buffer() {
    if (!is_admin() && !wp_doing_ajax()) {
        ob_start('remove_woocommerce_nojs_script_from_output');
    }
}
function remove_woocommerce_nojs_script_from_output($html) {
    $pattern = '#<script type=[\'"]text/javascript[\'"]>\s*\(function\s*\(\)\s*\{[^}]*woocommerce-no-js[^}]*\}\)\(\);\s*</script>#s';
    return preg_replace($pattern, '', $html);
}

add_filter('wp_speculation_rules', '__return_empty_array');
// Remove <script type="speculationrules"> from final output
add_action('template_redirect', 'start_speculation_rules_removal_buffer');
function start_speculation_rules_removal_buffer() {
    if (!is_admin() && !wp_doing_ajax() && !wp_is_json_request()) {
        ob_start(function($buffer) {
            // Remove the entire speculationrules script block
            $buffer = preg_replace('#<script type="speculationrules">.*?</script>#s', '', $buffer);
            return $buffer;
        });
    }
}

// Final HTML cleanup: remove leftover empty customizer CSS and WooCommerce noscript
add_action('template_redirect', 'start_html_cleanup_buffer');
function start_html_cleanup_buffer() {
    if (!is_admin() && !wp_doing_ajax()) {
        ob_start(function($html) {
            // Remove empty Customizer CSS comments and tags
            $html = preg_replace('#<!--Customizer CSS-->.*?<style[^>]*>\s*</style>.*?<!--/Customizer CSS-->#s', '', $html);
            
            // Remove WooCommerce gallery noscript block
            $html = preg_replace('#<noscript><style>\.woocommerce-product-gallery\{[^}]*\}</style></noscript>#', '', $html);
            
            return $html;
        });
    }
}

// Remove generator tags
remove_action('wp_head', 'wp_generator');
add_filter('woocommerce_generator_tag', '__return_empty_string');

// Remove RSS feed links
remove_action('wp_head', 'feed_links', 2);
remove_action('wp_head', 'feed_links_extra', 3);

// Remove RSD / XML-RPC link
remove_action('wp_head', 'rsd_link');

// Remove shortlink
remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0);
remove_action('template_redirect', 'wp_shortlink_header', 11, 0);

// Remove oEmbed discovery links (optional)
remove_action('wp_head', 'wp_oembed_add_discovery_links');