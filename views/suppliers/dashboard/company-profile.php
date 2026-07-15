<?php
/**
 * Company Profile Form Template
 * 
 * @package AWPS
 * @subpackage Suppliers/Dashboard
 */

defined( 'ABSPATH' ) || exit;

/**
 * Calculate profile completion percentage based on Post Meta
 */
if ( ! function_exists( 'awps_calculate_profile_completion' ) ) {
    function awps_calculate_profile_completion( $post_id ) {
        $fields = [
            'company_logo'    => 2,
            'banner_image'    => 2,
            'company_name'    => 2,
            'address'         => 1,
            'state'           => 1,
            'city'            => 1,
            'phone'           => 1,
            'website'         => 1,
            'facebook'        => 1,
            'instagram'       => 1,
            'linkedin'        => 1,
            'exports_to'      => 1,
            'annual_capacity' => 1,
            'payment_terms'   => 1,
            'languages'       => 1,
            'lead_time'       => 1,
            'packaging'       => 1,
            'contact_person'  => 1,
            'designation'     => 1,
            'whatsapp'        => 1,
            'skype'           => 1,
            'working_hours'   => 1,
            'about_company'   => 2,
        ];
        
        $filled_weight = 0;
        $total_weight  = 0;
        
        foreach ( $fields as $field => $weight ) {
            $total_weight += $weight;
            $value = get_post_meta( $post_id, $field, true );
            
            if ( is_array( $value ) ) {
                $value = ! empty( $value ) ? implode( ',', $value ) : '';
            }
            
            if ( in_array( $field, [ 'exports_to', 'payment_terms', 'languages', 'packaging' ], true ) ) {
                if ( ! empty( $value ) && count( array_filter( array_map( 'trim', explode( ',', (string) $value ) ) ) ) > 0 ) {
                    $filled_weight += $weight;
                }
            } elseif ( ! empty( $value ) && (string) $value !== '0' && trim( (string) $value ) !== '' ) {
                $filled_weight += $weight;
            }
        }
        
        return $total_weight > 0 ? round( ( $filled_weight / $total_weight ) * 100 ) : 0;
    }
}

/**
 * Get missing profile fields for the banner hint
 */
if ( ! function_exists( 'awps_get_missing_profile_fields' ) ) {
    function awps_get_missing_profile_fields( $post_id ) {
        $required = [
            'company_logo'   => __( 'Company Logo', 'awps' ),
            'banner_image'   => __( 'Banner Image', 'awps' ),
            'company_name'   => __( 'Company Name', 'awps' ),
            'about_company'  => __( 'About Company', 'awps' ),
            'phone'          => __( 'Phone Number', 'awps' ),
            'whatsapp'       => __( 'WhatsApp', 'awps' ),
            'exports_to'     => __( 'Export Destinations', 'awps' ),
            'payment_terms'  => __( 'Payment Terms', 'awps' ),
            'contact_person' => __( 'Contact Person', 'awps' ),
        ];

        $missing = [];
        foreach ( $required as $key => $label ) {
            $value = get_post_meta( $post_id, $key, true );
            if ( is_array( $value ) ) {
                $value = ! empty( $value ) ? implode( ',', $value ) : '';
            }
            if ( in_array( $key, [ 'exports_to', 'payment_terms' ], true ) ) {
                if ( empty( $value ) || count( array_filter( array_map( 'trim', explode( ',', (string) $value ) ) ) ) === 0 ) {
                    $missing[] = $label;
                }
            } elseif ( empty( $value ) || trim( (string) $value ) === '' || (string) $value === '0' ) {
                $missing[] = $label;
            }
        }
        return $missing;
    }
}

/**
 * Get the Supplier CPT post ID linked to a user
 */
function awps_get_supplier_post_id_by_user( $user_id ) {
    $supplier_posts = get_posts( [
        'post_type'   => 'supplier',
        'post_status' => 'any',
        'meta_query'  => [
            [
                'key'   => '_linked_user_id',
                'value' => $user_id,
            ],
        ],
        'numberposts' => 1,
        'fields'      => 'ids',
    ] );
    return ! empty( $supplier_posts ) ? $supplier_posts[0] : 0;
}

// Get current logged-in user
$user = wp_get_current_user();
if ( ! in_array( 'supplier', (array) $user->roles, true ) ) {
    wp_die( esc_html__( 'Access denied.', 'awps' ) );
}

// Find the linked SUPPLIER CPT post (not 'exporter')
$supplier_posts = get_posts( [
    'post_type'   => 'supplier',  // ✅ Changed from 'exporter' to 'supplier'
    'post_status' => 'any',
    'meta_query'  => [
        [
            'key'   => '_linked_user_id',
            'value' => $user->ID,
        ],
    ],
    'numberposts' => 1,
] );

// Handle missing profile gracefully (NO wp_die)
if ( empty( $supplier_posts ) ) {
    // Show error INSIDE the dashboard layout
    ?>
    <div class="awps-error-notice" style="background:#fef2f2; border:1px solid #fecaca; color:#991b1b; padding:20px; border-radius:8px; margin:20px 0;">
        <h3 style="margin:0 0 10px 0;">⚠️ No Supplier Profile Found</h3>
        <p style="margin:0 0 15px 0;">Your account isn't linked to a supplier profile yet.</p>
        <p style="margin:0;">
            <a href="<?php echo esc_url( home_url('/contact/') ); ?>" class="button button-primary">
                Contact Support
            </a>
            <a href="<?php echo esc_url( home_url('/dashboard/') ); ?>" class="button" style="margin-left:10px;">
                ← Back to Dashboard
            </a>
        </p>
    </div>
    <?php
    // Stop rendering the rest of the form
    return;
}

$post    = $supplier_posts[0];  // ✅ Use $supplier_posts
$post_id = $post->ID;

// Helper to get array-type post meta (comma-separated)
$get_array = function ( $key ) use ( $post_id ) {
    $val = get_post_meta( $post_id, $key, true );
    return $val ? array_filter( array_map( 'trim', explode( ',', $val ) ) ) : [];
};

// Load all needed data from POST META
$logo_url          = get_post_meta( $post_id, 'company_logo', true );
$banner_url        = get_post_meta( $post_id, 'banner_image', true );
$company_name      = get_post_meta( $post_id, 'company_name', true ) ?: $post->post_title;
$address           = get_post_meta( $post_id, 'address', true );
$saved_state       = get_post_meta( $post_id, 'state', true );
$saved_city        = get_post_meta( $post_id, 'city', true );
$phone             = get_post_meta( $post_id, 'phone', true );
$website           = get_post_meta( $post_id, 'website', true );
$facebook          = get_post_meta( $post_id, 'facebook', true );
$instagram         = get_post_meta( $post_id, 'instagram', true );
$linkedin          = get_post_meta( $post_id, 'linkedin', true );
$about_company     = get_post_meta( $post_id, 'about_company', true );
$contact_person    = get_post_meta( $post_id, 'contact_person', true );
$designation       = get_post_meta( $post_id, 'designation', true );
$whatsapp          = get_post_meta( $post_id, 'whatsapp', true );
$skype             = get_post_meta( $post_id, 'skype', true );
$working_hours     = get_post_meta( $post_id, 'working_hours', true );
$annual_capacity   = get_post_meta( $post_id, 'annual_capacity', true );
$lead_time         = get_post_meta( $post_id, 'lead_time', true );

// Array fields
$user_exports_to    = $get_array( 'exports_to' );
$user_payment_terms = $get_array( 'payment_terms' );
$user_languages     = $get_array( 'languages' );
$user_packaging     = $get_array( 'packaging' );

// Load dynamic options
$state_cities = [
    'Punjab'                    => [ 'Lahore', 'Faisalabad', 'Rawalpindi', 'Multan', 'Gujranwala' ],
    'Sindh'                     => [ 'Karachi', 'Hyderabad', 'Sukkur' ],
    'Khyber Pakhtunkhwa'        => [ 'Peshawar', 'Mardan', 'Abbottabad' ],
    'Balochistan'               => [ 'Quetta', 'Gwadar' ],
    'Islamabad Capital Territory' => [ 'Islamabad' ],
    'Gilgit-Baltistan'          => [ 'Gilgit', 'Skardu' ],
    'Azad Kashmir'              => [ 'Muzaffarabad', 'Mirpur' ],
];
$countries     = function_exists( 'get_countries' ) ? get_countries() : [];
$all_languages = function_exists( 'get_languages' ) ? get_languages() : [];

// ─────────────────────────────────────────────────────────────
// HANDLE FORM SAVE
// ─────────────────────────────────────────────────────────────
if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['awps_exporter_profile_nonce'] ) ) {
    
    if ( ! wp_verify_nonce( $_POST['awps_exporter_profile_nonce'], 'awps_exporter_profile' ) ) {
        wp_die( esc_html__( 'Security check failed.', 'awps' ) );
    }

    // ─────────────────────────────────────────────────────────
    // 1. HANDLE IMAGE REMOVAL (check "remove_*" flags first)
    // ─────────────────────────────────────────────────────────
    $image_fields = [
        'company_logo' => [
            'meta_key'      => 'company_logo',
            'file_input'    => 'company_logo_file',
            'remove_input'  => 'remove_company_logo',
            'alt_input'     => 'company_logo_alt',
        ],
        'banner_image' => [
            'meta_key'      => 'banner_image',
            'file_input'    => 'banner_image_file',
            'remove_input'  => 'remove_banner_image',
            'alt_input'     => 'banner_image_alt',
        ],
    ];

    foreach ( $image_fields as $config ) {
        // If user clicked "remove", delete the meta
        if ( ! empty( $_POST[ $config['remove_input'] ] ) && $_POST[ $config['remove_input'] ] === '1' ) {
            $old_url = get_post_meta( $post_id, $config['meta_key'], true );
            if ( $old_url ) {
                // Optional: Delete attachment post if it exists
                $old_id = attachment_url_to_postid( $old_url );
                if ( $old_id ) {
                    wp_delete_attachment( $old_id, true );
                }
            }
            delete_post_meta( $post_id, $config['meta_key'] );
            delete_post_meta( $post_id, $config['meta_key'] . '_alt' );
        }
    }

    // ─────────────────────────────────────────────────────────
    // 2. HANDLE NEW IMAGE UPLOADS
    // ─────────────────────────────────────────────────────────
    if ( ! function_exists( 'awps_handle_image_upload' ) ) {
        function awps_handle_image_upload( $file_input_name, $post_id, $meta_key, $alt_input_name = '' ) {
            if ( empty( $_FILES[ $file_input_name ]['name'] ) ) {
                return; // No file uploaded
            }

            // Require WordPress media functions
            if ( ! function_exists( 'media_handle_upload' ) ) {
                require_once ABSPATH . 'wp-admin/includes/image.php';
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/media.php';
            }

            // Upload the file
            $attachment_id = media_handle_upload( $file_input_name, $post_id );

            if ( is_wp_error( $attachment_id ) ) {
                error_log( 'Image upload error for ' . $meta_key . ': ' . $attachment_id->get_error_message() );
                return false;
            }

            // Get the image URL
            $image_url = wp_get_attachment_url( $attachment_id );
            if ( $image_url ) {
                update_post_meta( $post_id, $meta_key, $image_url );
                
                // Save alt text if provided
                if ( $alt_input_name && ! empty( $_POST[ $alt_input_name ] ) ) {
                    $alt_text = sanitize_text_field( wp_unslash( $_POST[ $alt_input_name ] ) );
                    update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt_text );
                    // Also save to post meta for easy access
                    update_post_meta( $post_id, $meta_key . '_alt', $alt_text );
                }
                return true;
            }
            return false;
        }
    }

    // Process each image field
    foreach ( $image_fields as $config ) {
        // Only upload if a new file was selected AND not marked for removal
        if ( ! empty( $_FILES[ $config['file_input'] ]['name'] ) && empty( $_POST[ $config['remove_input'] ] ) ) {
            awps_handle_image_upload( 
                $config['file_input'], 
                $post_id, 
                $config['meta_key'], 
                $config['alt_input'] 
            );
        }
    }

    // ─────────────────────────────────────────────────────────
    // 3. SAVE TEXT FIELDS
    // ─────────────────────────────────────────────────────────
    $text_fields = [
        'company_name', 'address', 'state', 'city', 'phone', 'whatsapp',
        'annual_capacity', 'lead_time', 'contact_person', 'designation',
        'skype', 'working_hours',
    ];
    foreach ( $text_fields as $field ) {
        if ( isset( $_POST[ $field ] ) ) {
            update_post_meta( $post_id, $field, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) );
        }
    }

    // ─────────────────────────────────────────────────────────
    // 4. SAVE URL FIELDS
    // ─────────────────────────────────────────────────────────
    $url_fields = [ 'website', 'facebook', 'instagram', 'linkedin' ];
    foreach ( $url_fields as $field ) {
        if ( isset( $_POST[ $field ] ) ) {
            update_post_meta( $post_id, $field, esc_url_raw( wp_unslash( $_POST[ $field ] ) ) );
        }
    }

    // ─────────────────────────────────────────────────────────
    // 5. SAVE TEXTAREA FIELDS
    // ─────────────────────────────────────────────────────────
    if ( isset( $_POST['about_company'] ) ) {
        update_post_meta( $post_id, 'about_company', sanitize_textarea_field( wp_unslash( $_POST['about_company'] ) ) );
    }

    // ─────────────────────────────────────────────────────────
    // 6. SAVE ARRAY FIELDS (comma-separated)
    // ─────────────────────────────────────────────────────────
    $array_fields = [ 'exports_to', 'payment_terms', 'languages', 'packaging' ];
    foreach ( $array_fields as $field ) {
        $vals = isset( $_POST[ $field ] ) 
            ? array_filter( array_map( 'trim', explode( ',', wp_unslash( $_POST[ $field ] ) ) ) )
            : [];
        update_post_meta( $post_id, $field, implode( ',', array_unique( $vals ) ) );
    }

    // ─────────────────────────────────────────────────────────
    // 7. UPDATE PROFILE COMPLETION & SYNC STATUS
    // ─────────────────────────────────────────────────────────
    $completion = awps_calculate_profile_completion( $post_id );
    update_post_meta( $post_id, '_awps_profile_completion', $completion );
    
    $has_logo   = ! empty( get_post_meta( $post_id, 'company_logo', true ) );
    $has_banner = ! empty( get_post_meta( $post_id, 'banner_image', true ) );
    $frontend_ready = ( $completion >= 40 && $has_logo && $has_banner );
    
    $supplier_post_id = awps_get_supplier_post_id_by_user( $user->ID );
    if ( $supplier_post_id ) {
        $new_status     = $frontend_ready ? 'enabled' : 'disabled';
        $current_status = get_post_meta( $supplier_post_id, 'company_status', true ) ?: 'enabled';
        
        if ( $new_status !== $current_status ) {
            update_post_meta( $supplier_post_id, 'company_status', $new_status );
        }
    }

    // Redirect with success flag
    wp_safe_redirect( add_query_arg( 'updated', '1' ) );
    exit;
}
?>

<div class="awps-frontend-form">
    <form method="post" enctype="multipart/form-data" id="exporter-profile-form">
        
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px; margin-bottom:20px;">
    <h2 style="margin:0;"><?php esc_html_e( 'Company Profile', 'awps' ); ?></h2>
    
    <?php
    // Generate public profile URL
    $company_name = get_post_meta( $post_id, 'company_name', true ) ?: $post->post_title;
    $profile_slug = strtolower( preg_replace( '/[^a-z0-9]/i', '', $company_name ) );
    $public_profile_url = home_url( '/supplier/' . $profile_slug . '/' );
    
    // Check if profile is actually visible to public
    $completion = awps_calculate_profile_completion( $post_id );
    $has_logo   = ! empty( get_post_meta( $post_id, 'company_logo', true ) );
    $has_banner = ! empty( get_post_meta( $post_id, 'banner_image', true ) );
    $is_published = ( get_post_status( $post_id ) === 'publish' );
    
    $supplier_post_id = awps_get_supplier_post_id_by_user( $user->ID );
    $company_status = $supplier_post_id ? get_post_meta( $supplier_post_id, 'company_status', true ) : 'enabled';
    $backend_enabled = ( $company_status !== 'disabled' );
    
    $is_visible = ( $completion >= 40 && $has_logo && $has_banner && $backend_enabled && $is_published );
    ?>
    
    <?php if ( $is_visible ) : ?>
        <a href="<?php echo esc_url( $public_profile_url ); ?>" 
           target="_blank" 
           rel="noopener"
           style="display:inline-flex; align-items:center; gap:6px; background:#007cba; color:white !important; padding:10px 18px; border-radius:6px; text-decoration:none; font-weight:500; font-size:14px; transition:background 0.2s;">
            <i class="fas fa-external-link-alt" style="font-size:12px;" aria-hidden="true"></i>
            <?php esc_html_e( 'View Public Profile', 'awps' ); ?>
        </a>
    <?php else : ?>
        <span style="display:inline-flex; align-items:center; gap:6px; background:#f1f5f9; color:#64748b; padding:10px 18px; border-radius:6px; font-weight:500; font-size:14px; cursor:not-allowed;">
            <i class="fas fa-lock" style="font-size:12px;" aria-hidden="true"></i>
            <?php esc_html_e( 'Complete Profile to View', 'awps' ); ?>
        </span>
    <?php endif; ?>
</div>
        
        <?php wp_nonce_field( 'awps_exporter_profile', 'awps_exporter_profile_nonce' ); ?>

        <?php
        // ─────────────────────────────────────────────────────────────
        // TOP BANNER: Profile Completion + Status + Public Link
        // ─────────────────────────────────────────────────────────────
        $completion       = awps_calculate_profile_completion( $post_id );
        $has_logo         = ! empty( get_post_meta( $post_id, 'company_logo', true ) );
        $has_banner       = ! empty( get_post_meta( $post_id, 'banner_image', true ) );
        $frontend_ready   = ( $completion >= 40 && $has_logo && $has_banner );
        
        $supplier_post_id = awps_get_supplier_post_id_by_user( $user->ID );
        $company_status   = $supplier_post_id ? get_post_meta( $supplier_post_id, 'company_status', true ) : 'enabled';
        $backend_enabled  = ( $company_status !== 'disabled' );
        
        $is_published = ( get_post_status( $post_id ) === 'publish' );
        $is_visible   = ( $frontend_ready && $backend_enabled && $is_published );
        
        $public_profile_url = $is_visible 
            ? home_url( '/supplier/' . sanitize_title( get_post_meta( $post_id, 'company_name', true ) ?: $post->post_name ) . '/' ) 
            : '#';
        ?>

        

        <!-- Images Section -->
        <div class="form-group awps-tag-container">
            <h3>🖼️ <?php esc_html_e( 'Images', 'awps' ); ?></h3>

            <!-- Logo -->
            <div class="awps-iso-wrap" data-field="company_logo">
                <label for="company_logo_file"><?php esc_html_e( 'Company Logo', 'awps' ); ?></label>
                <?php 
                $logo_id  = $logo_url ? attachment_url_to_postid( $logo_url ) : 0;
                $logo_alt = $logo_id ? get_post_meta( $logo_id, '_wp_attachment_image_alt', true ) : '';
                ?>
                <div class="awps-iso-preview-cont" style="<?= $logo_url ? 'display:block;' : 'display:none;'; ?>">
                    <div class="awps-iso-preview-box" style="position: relative; display: inline-block;">
                        <?php if ( $logo_url ) : ?>
                            <img src="<?= esc_url( $logo_url ); ?>" alt="<?= esc_attr( $logo_alt ?: $company_name ); ?>" style="max-width:150px;height:auto;">
                            <input type="text" name="company_logo_alt" value="<?= esc_attr( $logo_alt ); ?>" placeholder="<?php esc_attr_e( 'Alt text...', 'awps' ); ?>" style="width:100%; font-size:12px; padding:4px;" aria-label="<?php esc_attr_e( 'Logo alt text', 'awps' ); ?>">
                        <?php endif; ?>
                        <button type="button" class="awps-iso-remove-btn" aria-label="<?php esc_attr_e( 'Remove logo', 'awps' ); ?>">×</button>
                    </div>
                </div>
                <div class="awps-iso-controls" style="<?= $logo_url ? 'display:none;' : 'display:block;'; ?>">
                    <input type="file" name="company_logo_file" id="company_logo_file" accept="image/*" class="awps-iso-input" aria-describedby="company_logo_help">
                    <small id="company_logo_help" style="display:block; margin-top:4px; color:#666;"><?php esc_html_e( 'Recommended: 400×400px, PNG or JPG', 'awps' ); ?></small>
                </div>
                <input type="hidden" name="remove_company_logo" value="">
            </div>

            <br>

            <!-- Banner -->
            <div class="awps-iso-wrap" data-field="banner_image">
                <label for="banner_image_file"><?php esc_html_e( 'Banner Image', 'awps' ); ?></label>
                <?php 
                $banner_id  = $banner_url ? attachment_url_to_postid( $banner_url ) : 0;
                $banner_alt = $banner_id ? get_post_meta( $banner_id, '_wp_attachment_image_alt', true ) : '';
                ?>
                <div class="awps-iso-preview-cont" style="<?= $banner_url ? 'display:block;' : 'display:none;'; ?>">
                    <div class="awps-iso-preview-box" style="position: relative; display: inline-block;">
                        <?php if ( $banner_url ) : ?>
                            <img src="<?= esc_url( $banner_url ); ?>" alt="<?= esc_attr( $banner_alt ?: $company_name ); ?>" style="max-width:400px;height:auto;">
                            <input type="text" name="banner_image_alt" value="<?= esc_attr( $banner_alt ); ?>" placeholder="<?php esc_attr_e( 'Alt text...', 'awps' ); ?>" style="width:100%; font-size:12px; padding:4px;" aria-label="<?php esc_attr_e( 'Banner alt text', 'awps' ); ?>">
                        <?php endif; ?>
                        <button type="button" class="awps-iso-remove-btn" aria-label="<?php esc_attr_e( 'Remove banner', 'awps' ); ?>">×</button>
                    </div>
                </div>
                <div class="awps-iso-controls" style="<?= $banner_url ? 'display:none;' : 'display:block;'; ?>">
                    <input type="file" name="banner_image_file" id="banner_image_file" accept="image/*" class="awps-iso-input" aria-describedby="banner_image_help">
                    <small id="banner_image_help" style="display:block; margin-top:4px; color:#666;"><?php esc_html_e( 'Recommended: 1200×300px, PNG or JPG', 'awps' ); ?></small>
                </div>
                <input type="hidden" name="remove_banner_image" value="">
            </div>
        </div>

        <!-- Cropping Modal -->
        <div id="cropper-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); align-items:center; justify-content:center; z-index:9999;" role="dialog" aria-modal="true" aria-labelledby="cropper-title">
            <div style="background:#fff; padding:10px; max-width:90%; max-height:90%; border-radius:8px;">
                <h3 id="cropper-title" style="margin:0 0 10px;"><?php esc_html_e( 'Crop Image', 'awps' ); ?></h3>
                <div style="text-align:right; margin-bottom:10px;">
                    <button id="cropper-cancel" type="button"><?php esc_html_e( 'Cancel', 'awps' ); ?></button>
                    <button id="cropper-save" type="button"><?php esc_html_e( 'Crop & Save', 'awps' ); ?></button>
                </div>
                <div style="max-height:70vh; overflow:auto;">
                    <img id="cropper-image" src="" alt="" style="max-width:100%; display:block;">
                </div>
            </div>
        </div>

        <!-- Company Info -->
        <h3>🏢 <?php esc_html_e( 'Company Info', 'awps' ); ?></h3>
        <div class="form-group awps-tag-container">
            <p>
                <label for="company_name"><?php esc_html_e( 'Company Name', 'awps' ); ?> <span class="required" aria-label="<?php esc_attr_e( 'Required field', 'awps' ); ?>">*</span></label>
                <input type="text" name="company_name" id="company_name" value="<?= esc_attr( $company_name ); ?>" class="regular-text" required>
            </p>

            <p>
                <label for="address"><?php esc_html_e( 'Address', 'awps' ); ?></label>
                <input type="text" name="address" id="address" value="<?= esc_attr( $address ); ?>" class="regular-text">
            </p>

            <p>
                <label for="state"><?php esc_html_e( 'State', 'awps' ); ?></label>
                <select name="state" id="state" class="regular-text">
                    <option value=""><?php esc_html_e( '— Select State —', 'awps' ); ?></option>
                    <?php foreach ( $state_cities as $state_name => $cities ) : ?>
                        <option value="<?= esc_attr( $state_name ); ?>" <?= selected( $saved_state, $state_name, false ); ?>>
                            <?= esc_html( $state_name ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>

            <p>
                <label for="city"><?php esc_html_e( 'City', 'awps' ); ?></label>
                <select name="city" id="city" class="regular-text">
                    <option value=""><?php esc_html_e( '— Select State First —', 'awps' ); ?></option>
                </select>
            </p>

            <p>
                <label for="phone"><?php esc_html_e( 'Phone', 'awps' ); ?></label>
                <input type="tel" name="phone" id="phone" value="<?= esc_attr( $phone ); ?>" class="regular-text" placeholder="+92 300 1234567">
            </p>

            <p>
                <label for="website"><?php esc_html_e( 'Website', 'awps' ); ?></label>
                <input type="url" name="website" id="website" value="<?= esc_url( $website ); ?>" class="regular-text" placeholder="https://example.com">
            </p>

            <p>
                <label for="facebook"><?php esc_html_e( 'Facebook', 'awps' ); ?></label>
                <input type="url" name="facebook" id="facebook" value="<?= esc_url( $facebook ); ?>" class="regular-text" placeholder="https://facebook.com/yourpage">
            </p>

            <p>
                <label for="instagram"><?php esc_html_e( 'Instagram', 'awps' ); ?></label>
                <input type="url" name="instagram" id="instagram" value="<?= esc_url( $instagram ); ?>" class="regular-text" placeholder="https://instagram.com/yourpage">
            </p>

            <p>
                <label for="linkedin"><?php esc_html_e( 'LinkedIn', 'awps' ); ?></label>
                <input type="url" name="linkedin" id="linkedin" value="<?= esc_url( $linkedin ); ?>" class="regular-text" placeholder="https://linkedin.com/company/yourcompany">
            </p>
        </div>

        <!-- Export Details -->
        <h3>🌍 <?php esc_html_e( 'Export Details', 'awps' ); ?></h3>

        <!-- Exports To -->
        <div class="form-group awps-tag-container">
            <label for="exports-to-input"><?php esc_html_e( '🌎 Exports To', 'awps' ); ?></label>
            <input type="text" id="exports-to-input" class="awps-tag-input" placeholder="<?php esc_attr_e( 'Start typing country name...', 'awps' ); ?>" aria-describedby="exports-to-help">
            <div id="exports-to-list" class="awps-tag-list" role="list">
                <?php foreach ( $user_exports_to as $country ) : ?>
                    <span class="tag" data-value="<?= esc_attr( $country ); ?>" role="listitem">
                        <?= esc_html( $country ); ?> 
                        <button type="button" class="remove-tag" aria-label="<?php esc_attr_e( 'Remove', 'awps' ); ?> <?= esc_attr( $country ); ?>">×</button>
                    </span>
                <?php endforeach; ?>
            </div>
            <input type="hidden" name="exports_to" value="<?= esc_attr( implode( ',', $user_exports_to ) ); ?>">
            <small id="exports-to-help"><?php esc_html_e( 'Select from dropdown to add. Type to search.', 'awps' ); ?></small>
        </div>

        <div class="form-group awps-tag-container">
            <p>
                <label for="annual_capacity"><?php esc_html_e( 'Annual Capacity', 'awps' ); ?></label>
                <input type="text" name="annual_capacity" id="annual_capacity" value="<?= esc_attr( $annual_capacity ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., 10,000 Tons', 'awps' ); ?>">
            </p>
        </div>

        <!-- Payment Terms -->
        <div class="form-group awps-tag-container">
            <label for="payment_terms_input"><?php esc_html_e( '💵 Payment Terms', 'awps' ); ?></label>
            <input type="text" id="payment_terms_input" class="awps-tag-input" placeholder="<?php esc_attr_e( 'Start typing payment term...', 'awps' ); ?>" aria-describedby="payment-terms-help">
            <div id="payment_terms_list" class="awps-tag-list" role="list">
                <?php foreach ( $user_payment_terms as $term ) : ?>
                    <span class="tag" data-value="<?= esc_attr( $term ); ?>" role="listitem">
                        <?= esc_html( $term ); ?> 
                        <button type="button" class="remove-tag" aria-label="<?php esc_attr_e( 'Remove', 'awps' ); ?> <?= esc_attr( $term ); ?>">×</button>
                    </span>
                <?php endforeach; ?>
            </div>
            <input type="hidden" name="payment_terms" value="<?= esc_attr( implode( ',', $user_payment_terms ) ); ?>">
            <small id="payment-terms-help"><?php esc_html_e( 'Select from dropdown to add. Type to search.', 'awps' ); ?></small>
        </div>

        <!-- Languages -->
        <div class="form-group awps-tag-container">
            <label for="languages-input"><?php esc_html_e( '🗣️ Languages Spoken', 'awps' ); ?></label>
            <input type="text" id="languages-input" class="awps-tag-input" placeholder="<?php esc_attr_e( 'Start typing language...', 'awps' ); ?>" aria-describedby="languages-help">
            <div id="languages-list" class="awps-tag-list" role="list">
                <?php foreach ( $user_languages as $lang ) : ?>
                    <span class="tag" data-value="<?= esc_attr( $lang ); ?>" role="listitem">
                        <?= esc_html( $lang ); ?> 
                        <button type="button" class="remove-tag" aria-label="<?php esc_attr_e( 'Remove', 'awps' ); ?> <?= esc_attr( $lang ); ?>">×</button>
                    </span>
                <?php endforeach; ?>
            </div>
            <input type="hidden" name="languages" value="<?= esc_attr( implode( ',', $user_languages ) ); ?>">
            <small id="languages-help"><?php esc_html_e( 'Select from dropdown to add. Type to search.', 'awps' ); ?></small>
        </div>

        <!-- Lead Time -->
        <div class="form-group awps-tag-container">
            <p>
                <label for="lead_time"><?php esc_html_e( 'Lead Time', 'awps' ); ?></label>
                <input type="text" name="lead_time" id="lead_time" value="<?= esc_attr( $lead_time ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'e.g., 15–20 Days', 'awps' ); ?>">
            </p>
        </div>

        <!-- Packaging -->
        <div class="form-group awps-tag-container">
            <label for="packaging_details_input"><?php esc_html_e( '📦 Packaging Options', 'awps' ); ?></label>
            <input type="text" id="packaging_details_input" class="awps-tag-input" placeholder="<?php esc_attr_e( 'Start typing packaging option...', 'awps' ); ?>" aria-describedby="packaging-help">
            <div id="packaging_details_list" class="awps-tag-list" role="list">
                <?php foreach ( $user_packaging as $item ) : ?>
                    <span class="tag" data-value="<?= esc_attr( $item ); ?>" role="listitem">
                        <?= esc_html( $item ); ?> 
                        <button type="button" class="remove-tag" aria-label="<?php esc_attr_e( 'Remove', 'awps' ); ?> <?= esc_attr( $item ); ?>">×</button>
                    </span>
                <?php endforeach; ?>
            </div>
            <input type="hidden" name="packaging" value="<?= esc_attr( implode( ',', $user_packaging ) ); ?>">
            <small id="packaging-help"><?php esc_html_e( 'Press Enter or select from dropdown to add.', 'awps' ); ?></small>
        </div>

        <!-- Contact Person -->
        <div class="form-group awps-tag-container">
            <h3>📞 <?php esc_html_e( 'Contact Person', 'awps' ); ?></h3>
            <p>
                <label for="contact_person"><?php esc_html_e( 'Contact Person', 'awps' ); ?></label>
                <input type="text" name="contact_person" id="contact_person" value="<?= esc_attr( $contact_person ); ?>" class="regular-text">
            </p>

            <p>
                <label for="designation"><?php esc_html_e( 'Designation', 'awps' ); ?></label>
                <input type="text" name="designation" id="designation" value="<?= esc_attr( $designation ); ?>" class="regular-text">
            </p>
        
            <p>
                <label for="whatsapp"><?php esc_html_e( 'WhatsApp', 'awps' ); ?></label>
                <input type="tel" name="whatsapp" id="whatsapp" value="<?= esc_attr( $whatsapp ); ?>" class="regular-text" placeholder="+92 300 1234567">
            </p>
        
            <p>
                <label for="skype"><?php esc_html_e( 'Skype', 'awps' ); ?></label>
                <input type="text" name="skype" id="skype" value="<?= esc_attr( $skype ); ?>" class="regular-text">
            </p>
        </div>

        <div class="form-group awps-tag-container">
            <p>
                <label for="working_hours"><?php esc_html_e( 'Working Hours', 'awps' ); ?></label>
                <input type="text" name="working_hours" id="working_hours" value="<?= esc_attr( $working_hours ); ?>" class="regular-text" placeholder="<?php esc_attr_e( '9AM–6PM PKT (GMT+5)', 'awps' ); ?>">
            </p>
        </div>

        <!-- About & Certifications -->
        <div class="form-group awps-tag-container">
            <h3>📝 <?php esc_html_e( 'Tell us about your company', 'awps' ); ?></h3>
            <p>
                <label for="about_company"><?php esc_html_e( 'About Company', 'awps' ); ?></label>
                <textarea name="about_company" id="about_company" rows="5" class="regular-text"><?= esc_textarea( $about_company ); ?></textarea>
            </p>
        </div>


        <!-- ✅ COMPLETION BANNER WITH LIVE CHECKLIST -->
<div class="completion-banner" style="background:linear-gradient(135deg, #007cba 0%, #005a87 100%); color:white; padding:20px; border-radius:8px; margin-bottom:25px; box-shadow:0 4px 12px rgba(0,0,0,0.15);">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px; margin-bottom:15px;">
        
        <!-- Left: Completion Score -->
        <div style="flex:1; min-width:200px;">
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:8px;">
                <span class="completion-percentage-text" style="font-size:28px; font-weight:700;"><?php echo (int) $completion; ?>%</span>
                <span class="completion-label" style="font-size:14px; opacity:0.9;"><?php esc_html_e( 'Profile Complete', 'awps' ); ?></span>
            </div>
            
            <!-- Progress Bar -->
            <div class="completion-progress" style="background:rgba(255,255,255,0.2); border-radius:6px; height:8px; overflow:hidden;">
                <div id="completion-progress-bar" style="background:<?php echo $completion >= 80 ? '#28a745' : ( $completion >= 50 ? '#ffc107' : '#dc3545' ); ?>; height:100%; width:<?php echo (int) $completion; ?>%; transition:width 0.4s ease; border-radius:6px;"></div>
            </div>
        </div>
        
        <!-- Right: Status Badge -->
        <div style="text-align:right; min-width:180px;">
            <span class="completion-status-badge" style="display:inline-block; padding:6px 14px; border-radius:20px; font-size:12px; font-weight:600; background:<?php echo $completion >= 80 ? 'rgba(40,167,69,0.2)' : ( $completion >= 50 ? 'rgba(255,193,7,0.2)' : 'rgba(220,53,69,0.2)' ); ?>; color:<?php echo $completion >= 80 ? '#28a745' : ( $completion >= 50 ? '#ffc107' : '#dc3545' ); ?>; border:1px solid <?php echo $completion >= 80 ? '#28a745' : ( $completion >= 50 ? '#ffc107' : '#dc3545' ); ?>;">
                <?php 
                if ( $completion >= 80 ) : 
                    esc_html_e( '✅ Excellent', 'awps' ); 
                elseif ( $completion >= 50 ) : 
                    esc_html_e( '🟡 Good Progress', 'awps' ); 
                else : 
                    esc_html_e( '🔴 Needs Work', 'awps' ); 
                endif; 
                ?>
            </span>
        </div>
    </div>
    
    <!-- Guidance Checklist -->
    <div class="completion-guidance" style="background:rgba(255,255,255,0.1); border-radius:6px; padding:15px;">
        <p style="margin:0 0 10px 0; font-size:13px; opacity:0.95;">
            <strong><?php esc_html_e( '📋 Complete these fields to reach 100%:', 'awps' ); ?></strong>
        </p>
        <div class="checklist-grid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:8px; font-size:12px;">
            <?php 
            $all_fields = [
                'company_logo'    => __( 'Company Logo', 'awps' ),
                'banner_image'    => __( 'Banner Image', 'awps' ),
                'company_name'    => __( 'Company Name', 'awps' ),
                'about_company'   => __( 'About Company', 'awps' ),
                'address'         => __( 'Address', 'awps' ),
                'state'           => __( 'State', 'awps' ),
                'city'            => __( 'City', 'awps' ),
                'phone'           => __( 'Phone', 'awps' ),
                'whatsapp'        => __( 'WhatsApp', 'awps' ),
                'website'         => __( 'Website', 'awps' ),
                'facebook'        => __( 'Facebook', 'awps' ),
                'instagram'       => __( 'Instagram', 'awps' ),
                'linkedin'        => __( 'LinkedIn', 'awps' ),
                'exports_to'      => __( 'Export Destinations', 'awps' ),
                'annual_capacity' => __( 'Annual Capacity', 'awps' ),
                'payment_terms'   => __( 'Payment Terms', 'awps' ),
                'languages'       => __( 'Languages', 'awps' ),
                'lead_time'       => __( 'Lead Time', 'awps' ),
                'packaging'       => __( 'Packaging Options', 'awps' ),
                'contact_person'  => __( 'Contact Person', 'awps' ),
                'designation'     => __( 'Designation', 'awps' ),
                'skype'           => __( 'Skype', 'awps' ),
                'working_hours'   => __( 'Working Hours', 'awps' ),
            ];
            
            foreach ( $all_fields as $field => $label ) {
                // Check if field is filled (server-side initial state)
                $is_filled = false;
                if ( in_array( $field, [ 'exports_to', 'payment_terms', 'languages', 'packaging' ], true ) ) {
                    $val = get_post_meta( $post_id, $field, true );
                    $is_filled = ! empty( $val ) && count( array_filter( array_map( 'trim', explode( ',', $val ) ) ) ) > 0;
                } elseif ( in_array( $field, [ 'company_logo', 'banner_image' ], true ) ) {
                    $is_filled = ! empty( get_post_meta( $post_id, $field, true ) );
                } else {
                    $val = get_post_meta( $post_id, $field, true );
                    $is_filled = ! empty( $val ) && trim( (string) $val ) !== '' && (string) $val !== '0';
                }
                
                echo '<div class="checklist-item" data-field="' . esc_attr( $field ) . '" style="display:flex; align-items:center; gap:6px;">';
                echo '<span class="checklist-icon" aria-hidden="true" style="font-size:1rem; color:' . ( $is_filled ? '#28a745' : '#ffc107' ) . ';">' . ( $is_filled ? '✅' : '⬜' ) . '</span>';
                echo '<span class="checklist-label" style="font-weight:' . ( $is_filled ? 'normal' : '600' ) . '; opacity:' . ( $is_filled ? '0.7' : '1' ) . ';">' . esc_html( $label ) . '</span>';
                echo '</div>';
            }
            ?>
        </div>
    </div>
</div>

        <p>
            <button type="submit" class="button button-primary">💾 <?php esc_html_e( 'Save Profile', 'awps' ); ?></button>
        </p>
    </form>
</div>

<!-- ✅ INLINE JS FOR TAG INPUTS & STATE/CITY (kept exactly as working) -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // State → City dropdown logic
    const stateEl = document.getElementById('state');
    const cityEl = document.getElementById('city');
    const stateCities = <?php echo wp_json_encode( $state_cities ); ?>;
    const savedCity = <?php echo wp_json_encode( $saved_city ); ?>;

    function updateCities() {
        const state = stateEl.value;
        cityEl.innerHTML = '';
        
        if (!state) {
            cityEl.disabled = true;
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = '— Select State First —';
            cityEl.appendChild(opt);
            return;
        }

        cityEl.disabled = false;
        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = '— Select City —';
        cityEl.appendChild(placeholder);

        const cities = stateCities[state] || [];
        cities.forEach(cityName => {
            const opt = document.createElement('option');
            opt.value = cityName;
            opt.textContent = cityName;
            if (cityName === savedCity) opt.selected = true;
            cityEl.appendChild(opt);
        });
    }

    if (stateEl && cityEl) {
        stateEl.addEventListener('change', updateCities);
        updateCities(); // Initial load
    }

    // Tag input logic (exports_to, payment_terms, languages, packaging)
    function initTagInput(inputId, listId, hiddenId, options) {
        const input = document.getElementById(inputId);
        const list = document.getElementById(listId);
        const hidden = document.getElementById(hiddenId);
        if (!input || !list || !hidden) return;

        // Add tag on Enter or selection
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && input.value.trim()) {
                e.preventDefault();
                addTag(input.value.trim());
                input.value = '';
            }
        });

        // Remove tag
        list.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-tag')) {
                e.target.closest('.tag').remove();
                updateHidden();
            }
        });

        function addTag(value) {
            if (!value) return;
            // Check for duplicates
            const existing = Array.from(list.querySelectorAll('.tag')).map(t => t.dataset.value);
            if (existing.includes(value)) return;

            const tag = document.createElement('span');
            tag.className = 'tag';
            tag.dataset.value = value;
            tag.setAttribute('role', 'listitem');
            tag.innerHTML = `${value} <button type="button" class="remove-tag" aria-label="Remove ${value}">×</button>`;
            list.appendChild(tag);
            updateHidden();
        }

        function updateHidden() {
            const values = Array.from(list.querySelectorAll('.tag')).map(t => t.dataset.value);
            hidden.value = values.join(',');
        }

        // Optional: autocomplete from options array
        if (Array.isArray(options) && options.length) {
            input.addEventListener('input', function() {
                // Simple filter: show matching options (you can enhance with a dropdown)
                const query = input.value.toLowerCase();
                // Implementation depends on your UI library
            });
        }
    }

    // Initialize all tag inputs
    initTagInput('exports-to-input', 'exports-to-list', 'exports_to', <?php echo wp_json_encode( $countries ); ?>);
    initTagInput('payment_terms_input', 'payment_terms_list', 'payment_terms', []);
    initTagInput('languages-input', 'languages-list', 'languages', <?php echo wp_json_encode( $all_languages ); ?>);
    initTagInput('packaging_details_input', 'packaging_details_list', 'packaging', []);

    // Image preview & remove logic
    document.querySelectorAll('.awps-iso-wrap').forEach(wrap => {
        const field = wrap.dataset.field;
        const input = wrap.querySelector('.awps-iso-input');
        const previewCont = wrap.querySelector('.awps-iso-preview-cont');
        const controls = wrap.querySelector('.awps-iso-controls');
        const previewBox = wrap.querySelector('.awps-iso-preview-box');
        const removeBtn = wrap.querySelector('.awps-iso-remove-btn');
        const hiddenRemove = wrap.querySelector(`input[name="remove_${field}"]`);

        if (input) {
            input.addEventListener('change', function(e) {
                if (e.target.files && e.target.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(ev) {
                        let img = previewBox.querySelector('img');
                        if (!img) {
                            img = document.createElement('img');
                            img.style.cssText = 'max-width:150px;height:auto;';
                            previewBox.insertBefore(img, previewBox.firstChild);
                        }
                        img.src = ev.target.result;
                        previewCont.style.display = 'block';
                        controls.style.display = 'none';
                    };
                    reader.readAsDataURL(e.target.files[0]);
                }
            });
        }

        if (removeBtn) {
            removeBtn.addEventListener('click', function() {
                previewBox.querySelector('img')?.remove();
                previewBox.querySelector('input[type="text"]')?.remove();
                previewCont.style.display = 'none';
                controls.style.display = 'block';
                if (input) input.value = '';
                if (hiddenRemove) hiddenRemove.value = '1';
            });
        }
    });
});




</script>

<!-- ✅ LIVE COMPLETION CALCULATOR (JavaScript) -->
<script>
(function() {
    // Field configuration matching your PHP completion logic
    const fieldConfig = {
        'company_logo':    { weight: 2, type: 'image' },
        'banner_image':    { weight: 2, type: 'image' },
        'company_name':    { weight: 2, type: 'text' },
        'about_company':   { weight: 2, type: 'textarea' },
        'address':         { weight: 1, type: 'text' },
        'state':           { weight: 1, type: 'select' },
        'city':            { weight: 1, type: 'select' },
        'phone':           { weight: 1, type: 'tel' },
        'whatsapp':        { weight: 1, type: 'tel' },
        'website':         { weight: 1, type: 'url' },
        'facebook':        { weight: 1, type: 'url' },
        'instagram':       { weight: 1, type: 'url' },
        'linkedin':        { weight: 1, type: 'url' },
        'exports_to':      { weight: 1, type: 'array' },
        'annual_capacity': { weight: 1, type: 'text' },
        'payment_terms':   { weight: 1, type: 'array' },
        'languages':       { weight: 1, type: 'array' },
        'lead_time':       { weight: 1, type: 'text' },
        'packaging':       { weight: 1, type: 'array' },
        'contact_person':  { weight: 1, type: 'text' },
        'designation':     { weight: 1, type: 'text' },
        'skype':           { weight: 1, type: 'text' },
        'working_hours':   { weight: 1, type: 'text' },
    };
    
    const totalWeight = Object.values(fieldConfig).reduce((sum, f) => sum + f.weight, 0);
    
    function calculateAndDisplay() {
        let filledWeight = 0;
        
        for (const [fieldName, config] of Object.entries(fieldConfig)) {
            let isFilled = false;
            
            // Array fields (exports_to, payment_terms, etc.)
            if (config.type === 'array') {
                const hiddenInput = document.querySelector(`[name="${fieldName}"]`);
                const visibleInput = document.querySelector(`[id="${fieldName}-input"]`) || document.querySelector(`[id="${fieldName}_input"]`);
                
                const hiddenValue = hiddenInput ? hiddenInput.value.trim() : '';
                const visibleValue = visibleInput ? visibleInput.value.trim() : '';
                const hasValue = (hiddenValue && hiddenValue !== '0') || (visibleValue && visibleValue !== '0');
                
                if (hasValue) {
                    filledWeight += config.weight;
                    isFilled = true;
                }
            }
            // Image fields (check if preview container has image)
            else if (config.type === 'image') {
                const previewCont = document.querySelector(`[data-field="${fieldName}"] .awps-iso-preview-cont`);
                if (previewCont && previewCont.style.display !== 'none') {
                    filledWeight += config.weight;
                    isFilled = true;
                }
            }
            // Regular fields
            else {
                const field = document.querySelector(`[name="${fieldName}"]`);
                if (field) {
                    let value = '';
                    if (field.type === 'checkbox') {
                        value = field.checked ? 'yes' : '';
                    } else if (field.tagName === 'SELECT') {
                        value = field.value;
                    } else {
                        value = field.value.trim();
                    }
                    
                    const hasValue = value && value !== '0' && value !== '';
                    if (hasValue) {
                        filledWeight += config.weight;
                        isFilled = true;
                    }
                }
            }
            
            // Update checklist icon
            const checklistItem = document.querySelector(`[data-field="${fieldName}"] .checklist-icon`);
            const checklistLabel = document.querySelector(`[data-field="${fieldName}"] .checklist-label`);
            if (checklistItem) {
                checklistItem.textContent = isFilled ? '✅' : '⬜';
                checklistItem.style.color = isFilled ? '#28a745' : '#ffc107';
            }
            if (checklistLabel) {
                checklistLabel.style.fontWeight = isFilled ? 'normal' : '600';
                checklistLabel.style.opacity = isFilled ? '0.7' : '1';
            }
        }
        
        const percentage = Math.round((filledWeight / totalWeight) * 100);
        updateBanner(percentage);
    }
    
    function updateBanner(percentage) {
        // Update percentage number
        const percentageText = document.querySelector('.completion-percentage-text');
        if (percentageText) percentageText.textContent = percentage + '%';
        
        // Update progress bar width and color
        const progressBar = document.querySelector('#completion-progress-bar');
        if (progressBar) {
            progressBar.style.width = percentage + '%';
            progressBar.style.background = percentage >= 80 ? '#28a745' : 
                                        (percentage >= 50 ? '#ffc107' : '#dc3545');
        }
        
        // Update status badge
        const statusBadge = document.querySelector('.completion-status-badge');
        if (statusBadge) {
            if (percentage >= 80) {
                statusBadge.textContent = '✅ <?php echo esc_js( __( 'Excellent', 'awps' ) ); ?>';
                statusBadge.style.background = 'rgba(40,167,69,0.2)';
                statusBadge.style.color = '#28a745';
                statusBadge.style.borderColor = '#28a745';
            } else if (percentage >= 50) {
                statusBadge.textContent = '🟡 <?php echo esc_js( __( 'Good Progress', 'awps' ) ); ?>';
                statusBadge.style.background = 'rgba(255,193,7,0.2)';
                statusBadge.style.color = '#ffc107';
                statusBadge.style.borderColor = '#ffc107';
            } else {
                statusBadge.textContent = '🔴 <?php echo esc_js( __( 'Needs Work', 'awps' ) ); ?>';
                statusBadge.style.background = 'rgba(220,53,69,0.2)';
                statusBadge.style.color = '#dc3545';
                statusBadge.style.borderColor = '#dc3545';
            }
        }
    }
    
    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('exporter-profile-form');
        if (!form) return;
        
        // Listen for changes on ALL inputs
        form.addEventListener('input', function(e) {
            setTimeout(calculateAndDisplay, 100);
        });
        
        form.addEventListener('change', function(e) {
            calculateAndDisplay();
        });
        
        // Listen for tag additions/removals (custom events from your tag system)
        document.addEventListener('awpsTagAdded', calculateAndDisplay);
        document.addEventListener('awpsTagRemoved', calculateAndDisplay);
        
        // Listen for image preview changes
        form.addEventListener('awpsImagePreviewChanged', function(e) {
            calculateAndDisplay();
        });
        
        // Also listen for click events (for remove buttons, etc.)
        form.addEventListener('click', function(e) {
            if (e.target.classList.contains('awps-iso-remove-btn') || 
                e.target.classList.contains('remove-tag')) {
                setTimeout(calculateAndDisplay, 200);
            }
        });
        
        // Initial calculation
        calculateAndDisplay();
    });
})();
</script>

<style>
/* Tag styles */
.awps-tag-list {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin: 8px 0;
}
.tag {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #e2e8f0;
    color: #0f172a;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 0.85rem;
}
.tag .remove-tag {
    background: none;
    border: none;
    color: #64748b;
    cursor: pointer;
    font-size: 1rem;
    line-height: 1;
    padding: 0 2px;
}
.tag .remove-tag:hover {
    color: #ef4444;
}
.awps-tag-input {
    width: 100%;
    max-width: 300px;
    padding: 0.5rem 0.75rem;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 0.95rem;
}
.awps-tag-input:focus {
    outline: none;
    border-color: #0ea5e9;
    box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.15);
}

/* Form styles */
.form-group {
    margin-bottom: 1.5rem;
}
.form-group label {
    display: block;
    margin-bottom: 0.4rem;
    font-weight: 500;
    color: #334155;
}
.form-group .required {
    color: #ef4444;
    margin-left: 2px;
}
.regular-text {
    width: 100%;
    max-width: 400px;
    padding: 0.5rem 0.75rem;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 0.95rem;
}
.regular-text:focus {
    outline: none;
    border-color: #0ea5e9;
    box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.15);
}
textarea.regular-text {
    max-width: 100%;
    min-height: 100px;
}

/* Button styles */
.button {
    display: inline-block;
    padding: 0.5rem 1rem;
    background: #f1f5f9;
    color: #0f172a;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 0.95rem;
    font-weight: 500;
    cursor: pointer;
    text-decoration: none;
}
.button-primary {
    background: #0ea5e9;
    color: #fff;
    border-color: #0ea5e9;
}
.button-primary:hover {
    background: #0284c7;
}

/* Notice styles */
.notice {
    padding: 1rem;
    border-radius: 6px;
    margin: 1rem 0;
}
.notice-success {
    background: #f0fdf4;
    color: #166534;
    border: 1px solid #bbf7d0;
}
.notice-error {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
}


/* Completion Banner */
.completion-banner {
    background: linear-gradient(135deg, #007cba 0%, #005a87 100%);
    color: #fff;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 25px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.completion-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 15px;
}
.completion-percentage-text {
    font-size: 28px;
    font-weight: 700;
}
.completion-label {
    font-size: 14px;
    opacity: 0.9;
    margin-left: 10px;
}
.completion-status-badge {
    display: inline-block;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    border: 1px solid;
}
.completion-progress {
    background: rgba(255,255,255,0.2);
    border-radius: 6px;
    height: 8px;
    margin-bottom: 15px;
    overflow: hidden;
}
.completion-progress > div {
    height: 100%;
    transition: width 0.4s ease;
    border-radius: 6px;
}
.completion-guidance {
    background: rgba(255,255,255,0.1);
    border-radius: 6px;
    padding: 15px;
}
.completion-guidance p {
    margin: 0 0 10px 0;
    font-size: 13px;
    opacity: 0.95;
}
.checklist-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 8px;
    font-size: 12px;
}
.checklist-item {
    display: flex;
    align-items: center;
    gap: 6px;
}
.checklist-icon {
    font-size: 1rem;
}
.checklist-label {
    font-weight: 600;
}


/* Public Profile Link */
.public-profile-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #007cba;
    color: #fff !important;
    padding: 10px 18px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    font-size: 14px;
    transition: background 0.2s;
}
.public-profile-link:hover {
    background: #005a87;
}
.public-profile-link i {
    font-size: 12px;
}
.public-profile-disabled {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #f1f5f9;
    color: #64748b;
    padding: 10px 18px;
    border-radius: 6px;
    font-weight: 500;
    font-size: 14px;
    cursor: not-allowed;
}
</style>