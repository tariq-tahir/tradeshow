<?php
/**
 * Supplier Dashboard Index Template
 * 
 * @package AWPS
 * @subpackage Suppliers/Dashboard
 */

// ✅ SINGLE, ROBUST AUTH CHECK (at very top)
if ( ! is_user_logged_in() ) {
    wp_safe_redirect( home_url( '/my-account/?error=login_required' ) );
    exit;
}

$current_user = wp_get_current_user();

// ✅ Check supplier role
if ( ! in_array( 'supplier', (array) $current_user->roles, true ) ) {
    wp_safe_redirect( home_url( '/my-account/?error=invalid_role' ) );
    exit;
}

// ✅ Check company status
$company_status = get_user_meta( $current_user->ID, 'company_status', true );
if ( 'disabled' === $company_status ) {
    wp_logout();
    wp_safe_redirect( add_query_arg( 'login', 'disabled', home_url( '/my-account/' ) ) );
    exit;
}

$user = $current_user;
$tab  = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'dashboard';

// ✅ Only load data needed for JS and company-profile.php
$state_cities  = function_exists( 'get_state_cities' ) ? get_state_cities() : array();
$countries     = function_exists( 'get_countries' ) ? get_countries() : array();
$all_languages = function_exists( 'get_languages' ) ? get_languages() : array();

// ✅ Helper: Normalize meta array values (handles comma-separated strings)
// Scoped to avoid global namespace pollution
if ( ! function_exists( 'awps_normalize_meta_array' ) ) {
    function awps_normalize_meta_array( $user_id, $key ) {
        $val = get_user_meta( $user_id, $key, true );
        
        if ( is_array( $val ) ) {
            if ( count( $val ) === 1 && is_string( $val[0] ) && false !== strpos( $val[0], ',' ) ) {
                $arr = array_map( 'trim', explode( ',', $val[0] ) );
            } else {
                $arr = array_map( 'strval', $val );
            }
        } elseif ( is_string( $val ) ) {
            $arr = ( '' === $val ) ? array() : array_map( 'trim', explode( ',', $val ) );
        } else {
            $arr = array();
        }
        
        return array_values( array_filter( $arr, function( $t ) { return (string) $t !== ''; } ) );
    }
}

$user_exports_to    = awps_normalize_meta_array( $user->ID, 'exports_to' );
$user_languages     = awps_normalize_meta_array( $user->ID, 'languages' );
$user_payment_terms = awps_normalize_meta_array( $user->ID, 'payment_terms' );
$user_fob_ports     = awps_normalize_meta_array( $user->ID, 'fob_ports' );
$user_packaging     = awps_normalize_meta_array( $user->ID, 'packaging' );

// ✅ Certifications (special handling)
$user_certs = get_user_meta( $user->ID, 'certifications', true );
if ( is_string( $user_certs ) ) {
    $user_certs = array_filter( array_map( 'trim', explode( ',', $user_certs ) ) );
} elseif ( ! is_array( $user_certs ) ) {
    $user_certs = array();
}
$user_certs = array_values( $user_certs );

// ✅ Pass data to JS via wp_add_inline_script (cleaner than inline)
$dashboard_data = array(
    'stateCities'    => $state_cities,
    'countries'      => $countries,
    'languages'      => $all_languages,
    'paymentTerms'   => $user_payment_terms,
    'packaging'      => $user_packaging,
    'incoterms'      => function_exists( 'get_intoterms' ) ? get_intoterms() : array(),
    'shipping'       => function_exists( 'get_shipping_methods' ) ? get_shipping_methods() : array(),
    'samplePolicies' => function_exists( 'get_sample_policies' ) ? get_sample_policies() : array(),
    'keyBenefits'    => function_exists( 'get_key_benefits' ) ? get_key_benefits() : array(),
    'qualityControl' => function_exists( 'get_quality_control' ) ? get_quality_control() : array(),
    'savedCity'      => get_user_meta( $user->ID, 'city', true ),
    'nonce'          => wp_create_nonce( 'awps_dashboard_nonce' ),
    'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
);

wp_add_inline_script(
    'awps-app',
    'window.wpExporterData = window.wpExporterData || {};
    Object.assign(window.wpExporterData, ' . wp_json_encode( $dashboard_data ) . ');',
    'after'
);
?>

<div class="exporter-dashboard exporter-dashboard-page">
    
    <!-- Welcome Header -->
    <h2>
        <?php
        $company_name = get_user_meta($user_id, 'company_name', true);
        $display_name = !empty($company_name) ? $company_name : $current_user->display_name;

        printf(
            /* translators: %s: User display name */
            esc_html__( 'Welcome, %s', 'awps' ),
            esc_html( $display_name )
        );
        ?>
    </h2>

    <!-- Tab Navigation -->
    <?php
    $base_url = home_url( '/dashboard/' );
    
    $tabs = array(
        'dashboard'       => __( 'Dashboard', 'awps' ),
        'company-profile' => __( 'Company Profile', 'awps' ),
        'products'        => __( 'My Products', 'awps' ),
        'add-product'     => __( 'Add Product', 'awps' ),
        'change-password' => __( 'Change Password', 'awps' ),
    );
    ?>

    <nav class="exporter-tabs">
        <?php foreach ( $tabs as $tab_key => $label ) : ?>
            <?php
            $tab_url = add_query_arg( 'tab', $tab_key, $base_url );
            $is_active = ( $tab === $tab_key );
            ?>
            <a href="<?php echo esc_url( $tab_url ); ?>" 
               <?php echo $is_active ? 'class="active"' : ''; ?>>
                <?php echo esc_html( $label ); ?>
            </a> |
        <?php endforeach; ?>
        <a href="<?php echo esc_url( wp_logout_url( home_url( '/my-account/' ) ) ); ?>">
            <?php esc_html_e( 'Logout', 'awps' ); ?>
        </a>
    </nav>
    
    <!-- Tab Content Area -->
    <div class="exporter-content">
        <?php if ( 'dashboard' === $tab ) : ?>
            <?php include get_theme_file_path( 'views/suppliers/dashboard/dashboard.php' ); ?>
        <?php elseif ( 'company-profile' === $tab ) : ?>
            <?php include get_theme_file_path( 'views/suppliers/dashboard/company-profile.php' ); ?>
        <?php elseif ( 'products' === $tab ) : ?>
            <?php include get_theme_file_path( 'views/suppliers/dashboard/product-list.php' ); ?>
        <?php elseif ( 'add-product' === $tab ) : ?>
            <?php include get_theme_file_path( 'views/suppliers/dashboard/product-form.php' ); ?>
        <?php elseif ( 'change-password' === $tab ) : ?>
            <?php include get_theme_file_path( 'views/suppliers/dashboard/change-password.php' ); ?>
        <?php else : ?>
            <p><?php esc_html_e( 'Unknown tab selected.', 'awps' ); ?></p>
        <?php endif; ?>
    </div>
    
</div><!-- .exporter-dashboard -->