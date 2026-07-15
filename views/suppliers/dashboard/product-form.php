<?php
/**
 * Frontend Product Form for Suppliers
 * 
 * Display-only form — saving is handled by AWPS\Suppliers\Products
 * 
 * @package AWPS
 * @subpackage Suppliers/Dashboard
 */

defined( 'ABSPATH' ) || exit;

if ( ! is_user_logged_in() ) {
    wp_die( esc_html__( 'You must be logged in to access this page.', 'awps' ) );
}

$user = wp_get_current_user();
if ( ! in_array( 'supplier', (array) $user->roles, true ) ) {
    wp_die( esc_html__( 'Only suppliers can add or edit products.', 'awps' ) );
}

$product_id = isset( $_GET['product_id'] ) ? intval( $_GET['product_id'] ) : 0;

// Get product post object
$product = $product_id ? get_post( $product_id ) : null;

// Security: ensure user owns the product (if editing)
if ( $product && get_post_field( 'post_author', $product_id ) !== $user->ID ) {
    wp_die( esc_html__( 'You do not have permission to edit this product.', 'awps' ) );
}

// Fetch current values
$is_edit = (bool) $product_id;

$title           = $is_edit ? get_the_title( $product_id ) : '';
$description     = $is_edit ? get_post_field( 'post_content', $product_id ) : '';
$price           = $is_edit ? get_post_meta( $product_id, '_price', true ) : '';
$stock_status    = $is_edit ? get_post_meta( $product_id, '_stock_status', true ) : 'instock';
$product_status  = $is_edit ? get_post_status( $product_id ) : 'publish';

// Meta fields
$meta_fields = [
    '_made_in_pakistan', '_hs_code', '_moq', '_packaging_details', '_lead_time',
    '_shipping_options', '_payment_terms', '_incoterms', '_key_benefits',
    '_quality_control', '_sample_policies', '_certifications', '_factory_video_url',
];

$meta = [];
foreach ( $meta_fields as $field ) {
    $meta[ $field ] = $is_edit ? get_post_meta( $product_id, $field, true ) : '';
}

// Categories
$category_ids   = [];
$category_names = [];
if ( $is_edit ) {
    $terms = wp_get_post_terms( $product_id, 'product_cat' );
    foreach ( $terms as $term ) {
        if ( ! is_wp_error( $term ) ) {
            $category_ids[]                  = $term->term_id;
            $category_names[ $term->term_id ] = $term->name;
        }
    }
}

// Featured image
$featured_image_id  = $is_edit ? get_post_thumbnail_id( $product_id ) : 0;
$featured_image_url = $featured_image_id ? wp_get_attachment_image_url( $featured_image_id, 'medium' ) : '';
$featured_alt       = $featured_image_id ? get_post_meta( $featured_image_id, '_wp_attachment_image_alt', true ) : '';

// Gallery
$gallery_ids_str    = $is_edit ? get_post_meta( $product_id, '_product_image_gallery', true ) : '';
$gallery_ids_array  = $gallery_ids_str ? array_filter( array_map( 'intval', explode( ',', $gallery_ids_str ) ) ) : [];

// Rank Math SEO
$has_rank_math       = defined( 'RANK_MATH_FILE' );
$rank_math_title     = $is_edit ? get_post_meta( $product_id, 'rank_math_title', true ) : '';
$rank_math_description = $is_edit ? get_post_meta( $product_id, 'rank_math_description', true ) : '';

// Certifications (for display tags)
$selected_certs = [];
if ( $is_edit && ! empty( $meta['_certifications'] ) ) {
    $selected_certs = array_filter( array_map( 'intval', explode( ',', $meta['_certifications'] ) ) );
}

// Completion calculation for new products (only)
$completion = 0;
$is_ready   = false;
if ( ! $is_edit ) {
    $field_weights = [
        'title'                 => 2,
        'description'           => 2,
        'featured_image_id'     => 2,
        '_hs_code'              => 1,
        '_moq'                  => 1,
        '_lead_time'            => 1,
        '_packaging'            => 1,
        '_shipping'             => 1,
        '_payment_terms'        => 1,
        '_incoterms'            => 1,
        '_key_benefits'         => 1,
        '_certifications'       => 1,
        'rank_math_title'       => 1,
        'rank_math_description' => 1,
        'product_categories'    => 1,
        '_quality_control'      => 1,
        '_sample_policies'      => 1,
        '_factory_video'        => 1,
        'featured_image_alt'    => 0.5,
        'gallery_images'        => 1,
    ];
    $total_weight = array_sum( $field_weights );
    $filled       = 0;
    foreach ( $field_weights as $field => $weight ) {
        $filled += $weight; // Simplified for demo; real logic in JS
    }
    $completion = $total_weight > 0 ? round( ( $filled / $total_weight ) * 100 ) : 0;
    $is_ready   = $completion >= 50;
}
?>

<div class="awps-frontend-form awps-product-form">
    <h2><?php echo $is_edit ? esc_html__( 'Edit Product', 'awps' ) : esc_html__( 'Add New Product', 'awps' ); ?></h2>

    <?php if ( isset( $_GET['saved'] ) ) : ?>
        <div class="notice notice-success" role="status">
            <p>
                ✅ <?php esc_html_e( 'Product saved successfully!', 'awps' ); ?>
                <?php if ( ! empty( $_GET['product_id'] ) ) : ?>
                    <a href="<?php echo esc_url( get_permalink( (int) $_GET['product_id'] ) ); ?>">
                        <?php esc_html_e( 'View Product', 'awps' ); ?>
                    </a>
                <?php endif; ?>
            </p>
        </div>
    <?php endif; ?>

    <form id="awps-product-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <!-- CRITICAL: Tells WordPress which handler to run -->
        <input type="hidden" name="action" value="awps_save_product">
        
        <?php wp_nonce_field( 'awps_product', 'awps_product_nonce' ); ?>
        <?php if ( $is_edit ) : ?>
            <input type="hidden" name="product_id" value="<?php echo esc_attr( $product_id ); ?>">
        <?php endif; ?>

        <!-- Basic Info -->
        <div class="form-group">
            <label for="product-title"><?php esc_html_e( 'Product Name', 'awps' ); ?> <span class="required" aria-label="<?php esc_attr_e( 'Required field', 'awps' ); ?>">*</span></label>
            <input type="text" name="title" id="product-title" value="<?php echo esc_attr( $title ); ?>" placeholder="<?php esc_attr_e( 'Product name', 'awps' ); ?>" required>
        </div>

        <div class="form-group">
            <label for="product-description"><?php esc_html_e( 'Description', 'awps' ); ?></label>
            <textarea name="description" id="product-description" placeholder="<?php esc_attr_e( 'Product description', 'awps' ); ?>"><?php echo esc_textarea( $description ); ?></textarea>
        </div>

        <!-- Made in Pakistan -->
        <div class="form-group checkbox-group">
            <input type="hidden" name="made_in_pakistan" value="no">
            <label class="checkbox-label">
                <input type="checkbox" name="made_in_pakistan" id="made_in_pakistan" value="yes" <?php checked( $meta['_made_in_pakistan'], 'yes' ); ?>>
                <?php esc_html_e( 'Made in Pakistan', 'awps' ); ?>
            </label>
        </div>

        <div class="form-group checkbox-group">
            <label class="checkbox-label">
                <input type="checkbox" name="_product_visibility" id="_product_visibility" value="1" <?php checked( $product_status, 'publish' ); ?>>
                <?php esc_html_e( 'Show Product on Store', 'awps' ); ?>
            </label>
        </div>

        <!-- Featured Image -->
        <div class="form-group">
            <label><?php esc_html_e( '🖼️ Featured Image', 'awps' ); ?></label>
            <div class="media-upload-container">
                <div id="featured-image-container">
                    <?php if ( $featured_image_url ) : ?>
                        <div style="position:relative; display:inline-block;">
                            <img src="<?php echo esc_url( $featured_image_url ); ?>" alt="<?php echo esc_attr( $featured_alt ?: $title ); ?>" style="max-width:200px; border:1px solid #ddd; display:block;">
                            <button type="button" class="media-remove-btn" data-action="remove-featured" aria-label="<?php esc_attr_e( 'Remove featured image', 'awps' ); ?>">×</button>
                            <div class="alt-text-wrapper" style="margin-top:5px;">
                                <input type="text" name="featured_image_alt" id="featured_image_alt"
                                       value="<?php echo esc_attr( $featured_alt ); ?>"
                                       placeholder="<?php esc_attr_e( 'Alt text (e.g. Blue Cotton Shirt)', 'awps' ); ?>"
                                       style="width:100%; font-size:12px; padding:4px;"
                                       aria-label="<?php esc_attr_e( 'Featured image alt text', 'awps' ); ?>">
                            </div>
                        </div>
                    <?php else : ?>
                        <button type="button" class="media-upload-btn" data-action="upload-featured"><?php esc_html_e( 'Upload Image', 'awps' ); ?></button>
                    <?php endif; ?>
                </div>
                <input type="hidden" name="featured_image_id" id="featured_image_id" value="<?php echo esc_attr( $featured_image_id ); ?>">
                <input type="hidden" name="remove_featured_image" id="remove_featured_image" value="0">
                <input type="file" id="featured-upload-input" name="featured_image_upload" accept="image/*" style="display:none;" aria-label="<?php esc_attr_e( 'Upload featured image file', 'awps' ); ?>">
            </div>
        </div>

        <!-- Gallery -->
        <div class="form-group">
            <label><?php esc_html_e( '🖼️ Product Gallery', 'awps' ); ?></label>
            <div class="media-upload-container">
                <button type="button" class="media-upload-btn" data-action="upload-gallery"><?php esc_html_e( 'Add Gallery Images', 'awps' ); ?></button>
                <div id="gallery-preview" style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px;">
                    <?php if ( ! empty( $gallery_ids_array ) ) : ?>
                        <?php foreach ( $gallery_ids_array as $id ) :
                            $id = intval( $id );
                            if ( $id <= 0 ) continue;
                            $url = wp_get_attachment_image_url( $id, 'thumbnail' );
                            if ( ! $url ) continue;
                            $gallery_alt = get_post_meta( $id, '_wp_attachment_image_alt', true );
                        ?>
                            <div class="gallery-item" data-id="<?php echo esc_attr( $id ); ?>" style="position:relative; display:inline-block; margin:5px;">
                                <img src="<?php echo esc_url( $url ); ?>" alt="<?php echo esc_attr( $gallery_alt ); ?>" style="width:100px; height:100px; object-fit:cover; border-radius:4px; border:1px solid #ddd;">
                                <input type="hidden" name="gallery_image_id[]" value="<?php echo esc_attr( $id ); ?>">
                                <button type="button" class="media-remove-btn" data-action="remove-gallery" data-id="<?php echo esc_attr( $id ); ?>" aria-label="<?php esc_attr_e( 'Remove gallery image', 'awps' ); ?>">×</button>
                                <input type="text" name="gallery_image_alt[<?php echo esc_attr( $id ); ?>]"
                                       value="<?php echo esc_attr( $gallery_alt ); ?>"
                                       placeholder="<?php esc_attr_e( 'Alt text', 'awps' ); ?>"
                                       style="width:100px; display:block; margin-top:5px; font-size:11px;"
                                       aria-label="<?php esc_attr_e( 'Gallery image alt text', 'awps' ); ?>">
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <input type="file" id="gallery-upload-input" name="gallery_files[]" multiple accept="image/*" style="display:none;" aria-label="<?php esc_attr_e( 'Upload gallery images', 'awps' ); ?>">
                <input type="hidden" name="remove_gallery_ids" id="remove_gallery_ids" value="">
            </div>
        </div>

        <!-- Categories -->
        <div class="form-group awps-tag-container">
            <label for="product_categories_input"><?php esc_html_e( '🏷️ Product Categories', 'awps' ); ?></label>
            <div class="awps-autocomplete-wrapper" style="position:relative; display:inline-block; width:100%;">
                <input type="text" id="product_categories_input" class="awps-tag-input" placeholder="<?php esc_attr_e( 'Start typing...', 'awps' ); ?>" aria-autocomplete="list" aria-controls="product_categories_list">
                <div class="awps-autocomplete-loader" aria-hidden="true"></div>
            </div>
            <div id="product_categories_list" class="awps-tag-list" role="list">
                <?php foreach ( $category_ids as $cat_id ) :
                    $name = $category_names[ $cat_id ] ?? '';
                    if ( ! $name ) continue;
                ?>
                    <span class="tag" data-value="<?php echo esc_attr( $cat_id ); ?>" role="listitem">
                        <?php echo esc_html( $name ); ?> 
                        <button type="button" class="remove-tag" aria-label="<?php esc_attr_e( 'Remove category', 'awps' ); ?> <?php echo esc_attr( $name ); ?>">×</button>
                    </span>
                <?php endforeach; ?>
            </div>
            <input type="hidden" name="product_categories" id="product_categories" value="<?php echo esc_attr( implode( ',', array_map( 'strval', $category_ids ) ) ); ?>">
        </div>

        <!-- Custom Fields -->
        <div class="form-group">
            <label for="_hs_code"><?php esc_html_e( '🔢 HS Code', 'awps' ); ?></label>
            <input type="text" name="_hs_code" id="_hs_code" value="<?php echo esc_attr( $meta['_hs_code'] ); ?>" placeholder="<?php esc_attr_e( 'e.g., 1006.30', 'awps' ); ?>">
        </div>

        <div class="form-group">
            <label for="_moq"><?php esc_html_e( '📦 Minimum Order Quantity (MOQ)', 'awps' ); ?></label>
            <input type="text" name="_moq" id="_moq" value="<?php echo esc_attr( $meta['_moq'] ); ?>" placeholder="<?php esc_attr_e( 'e.g., 1 Ton', 'awps' ); ?>">
        </div>

        <div class="form-group awps-tag-container">
            <label for="_packaging_input"><?php esc_html_e( '🧷 Packaging Options', 'awps' ); ?></label>
            <input type="text" id="_packaging_input" class="awps-tag-input" placeholder="<?php esc_attr_e( 'Start typing packaging option...', 'awps' ); ?>" aria-describedby="_packaging_help">
            <div id="_packaging_list" class="awps-tag-list" role="list"></div>
            <input type="hidden" name="_packaging" value="<?php echo esc_attr( $meta['_packaging_details'] ); ?>">
            <small id="_packaging_help"><?php esc_html_e( 'Press Enter or select from dropdown to add.', 'awps' ); ?></small>
        </div>

        <div class="form-group">
            <label for="_lead_time"><?php esc_html_e( '⏱️ Lead Time', 'awps' ); ?></label>
            <input type="text" name="_lead_time" id="_lead_time" value="<?php echo esc_attr( $meta['_lead_time'] ); ?>" placeholder="<?php esc_attr_e( 'e.g., 15–20 Days After Order Confirmation', 'awps' ); ?>">
        </div>

        <div class="form-group awps-tag-container">
            <label for="_shipping_input"><?php esc_html_e( '🚢 Shipping Methods', 'awps' ); ?></label>
            <input type="text" id="_shipping_input" class="awps-tag-input" placeholder="<?php esc_attr_e( 'Start typing shipping method...', 'awps' ); ?>" aria-describedby="_shipping_help">
            <div id="_shipping_list" class="awps-tag-list" role="list"></div>
            <input type="hidden" name="_shipping" value="<?php echo esc_attr( $meta['_shipping_options'] ); ?>">
            <small id="_shipping_help"><?php esc_html_e( 'Press Enter or select from dropdown to add.', 'awps' ); ?></small>
        </div>

        <div class="form-group awps-tag-container">
            <label for="_payment_terms_input"><?php esc_html_e( '💵 Payment Terms', 'awps' ); ?></label>
            <input type="text" id="_payment_terms_input" class="awps-tag-input" placeholder="<?php esc_attr_e( 'Start typing payment term...', 'awps' ); ?>" aria-describedby="_payment_terms_help">
            <div id="_payment_terms_list" class="awps-tag-list" role="list"></div>
            <input type="hidden" name="_payment_terms" value="<?php echo esc_attr( $meta['_payment_terms'] ); ?>">
            <small id="_payment_terms_help"><?php esc_html_e( 'Press Enter or select from dropdown to add.', 'awps' ); ?></small>
        </div>

        <div class="form-group awps-tag-container">
            <label for="_incoterms_input"><?php esc_html_e( '🧭 Incoterms', 'awps' ); ?></label>
            <input type="text" id="_incoterms_input" class="awps-tag-input" placeholder="<?php esc_attr_e( 'Start typing incoterm code or description...', 'awps' ); ?>" aria-describedby="_incoterms_help">
            <div id="_incoterms_list" class="awps-tag-list" role="list"></div>
            <input type="hidden" name="_incoterms" value="<?php echo esc_attr( $meta['_incoterms'] ); ?>">
            <small id="_incoterms_help"><?php esc_html_e( 'Press Enter or select from dropdown to add.', 'awps' ); ?></small>
        </div>

        <div class="form-group awps-tag-container">
            <label for="_key_benefits_input"><?php esc_html_e( '✅ Key Benefits', 'awps' ); ?></label>
            <input type="text" id="_key_benefits_input" class="awps-tag-input" placeholder="<?php esc_attr_e( 'Start typing benefit or press Enter/Comma', 'awps' ); ?>" aria-describedby="_key_benefits_help">
            <div id="_key_benefits_list" class="awps-tag-list" role="list"></div>
            <input type="hidden" name="_key_benefits" value="<?php echo esc_attr( $meta['_key_benefits'] ); ?>">
            <small id="_key_benefits_help"><?php esc_html_e( 'Press Enter or comma to add.', 'awps' ); ?></small>
        </div>

        <div class="form-group awps-tag-container">
            <label for="_quality_control_input"><?php esc_html_e( '🧪 Quality Control', 'awps' ); ?></label>
            <input type="text" id="_quality_control_input" class="awps-tag-input" placeholder="<?php esc_attr_e( 'Start typing QC phrase or press Enter/Comma', 'awps' ); ?>" aria-describedby="_quality_control_help">
            <div id="_quality_control_list" class="awps-tag-list" role="list"></div>
            <input type="hidden" name="_quality_control" value="<?php echo esc_attr( $meta['_quality_control'] ); ?>">
            <small id="_quality_control_help"><?php esc_html_e( 'Press Enter or comma to add.', 'awps' ); ?></small>
        </div>

        <div class="form-group awps-tag-container">
            <label for="_sample_policies_input"><?php esc_html_e( '📦 Sample Policies', 'awps' ); ?></label>
            <input type="text" id="_sample_policies_input" class="awps-tag-input" placeholder="<?php esc_attr_e( 'Start typing sample policy...', 'awps' ); ?>" aria-describedby="_sample_policies_help">
            <div id="_sample_policies_list" class="awps-tag-list" role="list"></div>
            <input type="hidden" name="_sample_policies" value="<?php echo esc_attr( $meta['_sample_policies'] ); ?>">
            <small id="_sample_policies_help"><?php esc_html_e( 'Press Enter or select from dropdown to add.', 'awps' ); ?></small>
        </div>

        <!-- Certifications -->
        <div class="form-group awps-tag-container">
            <label for="_certifications_input"><?php esc_html_e( '🏅 Certifications', 'awps' ); ?></label>
            <div class="awps-autocomplete-wrapper" style="position: relative; display: inline-block; width: 100%;">
                <input type="text" id="_certifications_input" class="awps-tag-input" placeholder="<?php esc_attr_e( 'Start typing certification name...', 'awps' ); ?>" aria-autocomplete="list" aria-controls="_certifications_list">
                <div class="awps-autocomplete-loader" aria-hidden="true"></div>
            </div>
            <div id="_certifications_list" class="awps-tag-list" role="list">
                <?php foreach ( $selected_certs as $cert_id ) :
                    $cert = get_post( $cert_id );
                    if ( $cert ) : ?>
                        <span class="tag" data-value="<?php echo esc_attr( $cert_id ); ?>" role="listitem">
                            <?php echo esc_html( $cert->post_title ); ?> 
                            <button type="button" class="remove-tag" aria-label="<?php esc_attr_e( 'Remove certification', 'awps' ); ?> <?php echo esc_attr( $cert->post_title ); ?>">×</button>
                        </span>
                    <?php endif;
                endforeach; ?>
            </div>
            <input type="hidden" name="_certifications" id="_certifications_hidden" value="<?php echo esc_attr( implode( ',', $selected_certs ) ); ?>">
            <small><?php esc_html_e( 'Select from dropdown to add. Type to search.', 'awps' ); ?></small>
        </div>

        <!-- Factory Video -->
        <div class="form-group">
            <label for="_factory_video"><?php esc_html_e( '🎥 Factory/Processing Video URL (Optional)', 'awps' ); ?></label>
            <input type="url" name="_factory_video" id="_factory_video" value="<?php echo esc_attr( $meta['_factory_video_url'] ); ?>" placeholder="<?php esc_attr_e( 'https://youtube.com/watch?v=...', 'awps' ); ?>">
        </div>

        <!-- Rank Math SEO -->
        <?php if ( $has_rank_math ) : ?>
            <div class="form-group">
                <label for="rank_math_title"><?php esc_html_e( '🔍 SEO Title (For Search Engines)', 'awps' ); ?></label>
                <input type="text" name="rank_math_title" id="rank_math_title" value="<?php echo esc_attr( $rank_math_title ); ?>" placeholder="<?php esc_attr_e( 'SEO title for search engines', 'awps' ); ?>">
                <small><?php esc_html_e( 'Recommended: 50-60 characters. Leave empty to use product name.', 'awps' ); ?></small>
            </div>
            <div class="form-group">
                <label for="rank_math_description"><?php esc_html_e( '📝 Meta Description (For Search Engines)', 'awps' ); ?></label>
                <textarea name="rank_math_description" id="rank_math_description" rows="3" placeholder="<?php esc_attr_e( 'SEO description for search engines', 'awps' ); ?>"><?php echo esc_textarea( $rank_math_description ); ?></textarea>
                <small><?php esc_html_e( 'Recommended: 150-160 characters. Summarize your product for search results.', 'awps' ); ?></small>
            </div>
        <?php endif; ?>

        <!-- ✅ COMPLETION BANNER (Live JavaScript Calculation for New Products) -->
        <?php if ( ! $is_edit ) : // Only show for "Add New Product" ?>
            <!-- COMPLETION BANNER WITH GUIDANCE -->
            <div class="completion-banner">
                <div class="completion-header">
                    <div>
                        <span class="completion-percentage-text"><?php echo (int) $completion; ?>%</span>
                        <span class="completion-label"><?php esc_html_e( 'Product Complete', 'awps' ); ?></span>
                    </div>
                    <div>
                        <span class="completion-status-badge" style="background:<?php echo $is_ready ? 'rgba(40,167,69,0.2)' : 'rgba(255,193,7,0.2)'; ?>; color:<?php echo $is_ready ? '#28a745' : '#ffc107'; ?>; border-color:<?php echo $is_ready ? '#28a745' : '#ffc107'; ?>;">
                            <?php echo $is_ready ? esc_html__( '✅ Ready to Publish', 'awps' ) : esc_html__( '⚠️ Needs Improvement', 'awps' ); ?>
                        </span>
                    </div>
                </div>
                
                <!-- Progress Bar -->
                <div class="completion-progress">
                    <div id="completion-progress-bar" style="width: <?php echo (int) $completion; ?>%; background: <?php echo $completion >= 80 ? '#28a745' : ( $completion >= 50 ? '#ffc107' : '#dc3545' ); ?>;"></div>
                </div>
                
                <!-- Guidance Section -->
                <div class="completion-guidance">
                    <p>
                        <strong><?php esc_html_e( '📋 To reach 100% and maximize visibility, complete these fields:', 'awps' ); ?></strong>
                    </p>
                    <div class="checklist-grid">
                        <?php 
                        $all_fields = [
                            'title' => __( 'Product Name', 'awps' ),
                            'description' => __( 'Description', 'awps' ),
                            'featured_image_id' => __( 'Featured Image', 'awps' ),
                            '_hs_code' => __( 'HS Code', 'awps' ),
                            '_moq' => __( 'MOQ', 'awps' ),
                            '_lead_time' => __( 'Lead Time', 'awps' ),
                            '_packaging' => __( 'Packaging Options', 'awps' ),
                            '_shipping' => __( 'Shipping Methods', 'awps' ),
                            '_payment_terms' => __( 'Payment Terms', 'awps' ),
                            '_incoterms' => __( 'Incoterms', 'awps' ),
                            '_key_benefits' => __( 'Key Benefits', 'awps' ),
                            '_quality_control' => __( 'Quality Control', 'awps' ),
                            '_sample_policies' => __( 'Sample Policies', 'awps' ),
                            '_certifications' => __( 'Certifications', 'awps' ),
                            '_factory_video' => __( 'Factory Video', 'awps' ),
                            'product_categories' => __( 'Product Categories', 'awps' ),
                            'rank_math_title' => __( 'SEO Title', 'awps' ),
                            'rank_math_description' => __( 'Meta Description', 'awps' ),
                            'made_in_pakistan' => __( 'Made in Pakistan', 'awps' ),
                            'featured_image_alt' => __( 'Image Alt Text', 'awps' ),
                            'gallery_images' => __( 'Product Gallery', 'awps' ),
                        ];
                        
                        foreach ( $all_fields as $field => $label ) {
                            echo '<div class="checklist-item" data-field="' . esc_attr( $field ) . '">';
                            echo '<span class="checklist-icon" aria-hidden="true">⬜</span>';
                            echo '<span class="checklist-label">' . esc_html( $label ) . '</span>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <button type="submit" class="button button-primary">
            💾 <?php echo $is_edit ? esc_html__( 'Update Product', 'awps' ) : esc_html__( 'Save Product', 'awps' ); ?>
        </button>
    </form>
</div>

<!-- ✅ LIVE COMPLETION CALCULATOR (JavaScript for New Products) -->
<?php if ( ! $is_edit ) : ?>
<script>
(function() {
    // Field configuration matching your form
    const fieldConfig = {
        'title': { weight: 2 },
        'description': { weight: 2 },
        'featured_image_id': { weight: 2 },
        '_hs_code': { weight: 1 },
        '_moq': { weight: 1 },
        '_lead_time': { weight: 1 },
        '_packaging': { weight: 1 },
        '_shipping': { weight: 1 },
        '_payment_terms': { weight: 1 },
        '_incoterms': { weight: 1 },
        '_key_benefits': { weight: 1 },
        '_certifications': { weight: 1 },
        'rank_math_title': { weight: 1 },
        'rank_math_description': { weight: 1 },
        'product_categories': { weight: 1 },
        '_quality_control': { weight: 1 },
        '_sample_policies': { weight: 1 },
        '_factory_video': { weight: 1 },
        'featured_image_alt': { weight: 0.5 },
        'gallery_images': { weight: 1, type: 'array' },
    };
    
    const totalWeight = Object.values(fieldConfig).reduce((sum, f) => sum + f.weight, 0);
    
    function calculateAndDisplay() {
        let filledWeight = 0;
        
        for (const [fieldName, config] of Object.entries(fieldConfig)) {
            // Gallery images (array field)
            if (fieldName === 'gallery_images') {
                const galleryFields = document.querySelectorAll('[name="gallery_image_id[]"]');
                const hasAnyGallery = Array.from(galleryFields).some(f => f.value && f.value.trim() !== '');
                if (hasAnyGallery) filledWeight += config.weight;
                continue;
            }
            
            // Tag container fields (check hidden input OR visible input)
            const tagFields = ['_packaging', '_shipping', '_payment_terms', '_incoterms', '_key_benefits', '_quality_control', '_sample_policies', '_certifications'];
            if (tagFields.includes(fieldName)) {
                const hiddenInput = document.querySelector(`[name="${fieldName}"]`);
                const visibleInput = document.querySelector(`[id="${fieldName}_input"]`);
                
                const hiddenValue = hiddenInput ? hiddenInput.value.trim() : '';
                const visibleValue = visibleInput ? visibleInput.value.trim() : '';
                const hasValue = (hiddenValue && hiddenValue !== '0') || (visibleValue && visibleValue !== '0');
                
                if (hasValue) filledWeight += config.weight;
                continue;
            }
            
            // Regular field handling
            const field = document.querySelector(`[name="${fieldName}"]`);
            if (!field) continue;
            
            let value = '';
            if (field.type === 'checkbox') {
                value = field.checked ? 'yes' : '';
            } else {
                value = field.value.trim();
            }
            
            const hasValue = value && value !== '0' && value !== '';
            if (hasValue) {
                filledWeight += config.weight;
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
        const isReady = percentage >= 50;
        const statusBadge = document.querySelector('.completion-status-badge');
        if (statusBadge) {
            statusBadge.textContent = isReady ? '✅ <?php echo esc_js( __( 'Ready to Publish', 'awps' ) ); ?>' : '⚠️ <?php echo esc_js( __( 'Needs Improvement', 'awps' ) ); ?>';
            statusBadge.style.background = isReady ? 'rgba(40,167,69,0.2)' : 'rgba(255,193,7,0.2)';
            statusBadge.style.color = isReady ? '#28a745' : '#ffc107';
            statusBadge.style.borderColor = isReady ? '#28a745' : '#ffc107';
        }
        
        // Update guidance checklist icons
        for (const [fieldName, config] of Object.entries(fieldConfig)) {
            const checklistItem = document.querySelector(`[data-field="${fieldName}"] .checklist-icon`);
            if (!checklistItem) continue;
            
            let isFilled = false;
            
            if (fieldName === 'gallery_images') {
                const galleryFields = document.querySelectorAll('[name="gallery_image_id[]"]');
                isFilled = Array.from(galleryFields).some(f => f.value && f.value.trim() !== '');
            }
            else if (['_packaging', '_shipping', '_payment_terms', '_incoterms', '_key_benefits', '_quality_control', '_sample_policies', '_certifications'].includes(fieldName)) {
                const hiddenInput = document.querySelector(`[name="${fieldName}"]`);
                const visibleInput = document.querySelector(`[id="${fieldName}_input"]`);
                
                const hiddenValue = hiddenInput ? hiddenInput.value.trim() : '';
                const visibleValue = visibleInput ? visibleInput.value.trim() : '';
                isFilled = (hiddenValue && hiddenValue !== '0') || (visibleValue && visibleValue !== '0');
            }
            else if (fieldName === 'made_in_pakistan') {
                const checkbox = document.querySelector(`[name="${fieldName}"]`);
                isFilled = checkbox ? checkbox.checked : false;
            }
            else if (fieldName === 'featured_image_alt') {
                const altInput = document.querySelector(`[name="${fieldName}"]`);
                isFilled = altInput ? altInput.value.trim() !== '' : false;
            }
            else {
                const field = document.querySelector(`[name="${fieldName}"]`);
                if (field) {
                    const value = field.type === 'checkbox' ? (field.checked ? 'yes' : '') : field.value.trim();
                    isFilled = value && value !== '0' && value !== '';
                }
            }
            
            checklistItem.textContent = isFilled ? '✅' : '⬜';
            checklistItem.style.color = isFilled ? '#28a745' : '#ffc107';
            
            const label = checklistItem.closest('[data-field]')?.querySelector('.checklist-label');
            if (label) {
                label.style.fontWeight = isFilled ? 'normal' : '600';
                label.style.opacity = isFilled ? '0.7' : '1';
            }
        }
    }
    
    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('awps-product-form');
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
        
        // Also listen for click events (for buttons, etc.)
        form.addEventListener('click', function(e) {
            if (e.target.classList.contains('media-remove-btn') || 
                e.target.classList.contains('remove-tag')) {
                setTimeout(calculateAndDisplay, 200);
            }
        });
        
        // Initial calculation
        calculateAndDisplay();
    });
})();
</script>
<?php endif; ?>

<!-- ✅ MINIMAL CSS (can move to SASS later) -->
<style>
/* Product Form Layout */
.awps-product-form {
    max-width: 800px;
    margin: 0 auto;
}

/* Form Groups */
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
.form-group input[type="text"],
.form-group input[type="url"],
.form-group input[type="number"],
.form-group textarea,
.form-group select {
    width: 100%;
    max-width: 100%;
    padding: 0.5rem 0.75rem;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 0.95rem;
    box-sizing: border-box;
}
.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: #0ea5e9;
    box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.15);
}

/* Checkboxes */
.checkbox-group {
    margin: 0.5rem 0;
}
.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: normal;
    cursor: pointer;
}
.checkbox-label input[type="checkbox"] {
    width: auto;
    margin: 0;
}

/* Media Upload */
.media-upload-container {
    margin-top: 0.5rem;
}
.media-upload-btn,
.media-remove-btn {
    padding: 0.5rem 1rem;
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 0.9rem;
    cursor: pointer;
}
.media-upload-btn:hover,
.media-remove-btn:hover {
    background: #e2e8f0;
}
.media-remove-btn {
    position: absolute;
    top: -8px;
    right: -8px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #ef4444;
    color: #fff;
    border: none;
    font-size: 1rem;
    line-height: 1;
    padding: 0;
}

/* Tag Inputs */
.awps-tag-container {
    margin-bottom: 1.5rem;
}
.awps-tag-input {
    width: 100%;
    max-width: 100%;
    padding: 0.5rem 0.75rem;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 0.95rem;
    margin-bottom: 0.5rem;
}
.awps-tag-list {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin: 0.5rem 0;
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

/* Completion Banner */
.completion-banner {
    background: linear-gradient(135deg, #007cba 0%, #005a87 100%);
    color: #fff;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
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
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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

/* Buttons */
.button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    background: #007cba;
    color: #fff !important;
    border: none;
    border-radius: 4px;
    padding: 10px 20px;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: background 0.2s;
}
.button:hover {
    background: #005a87;
}
.button-primary {
    background: #007cba;
}
.button-primary:hover {
    background: #005a87;
}

/* Notices */
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

/* Responsive */
@media (max-width: 768px) {
    .completion-header {
        flex-direction: column;
        align-items: flex-start;
    }
    .checklist-grid {
        grid-template-columns: 1fr;
    }
}
</style>