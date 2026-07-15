<?php
// views/suppliers/dashboard/product-list.php

// ─────────────────────────────────────────────────────────────
// HELPER FUNCTIONS (For display only - saving is in Products.php)
// ─────────────────────────────────────────────────────────────

/**
 * Calculate product completion percentage (UNIFIED & FIXED)
 * ⚠️ MUST match Products.php::calculate_product_completion() exactly
 */
function awps_calculate_product_completion($product_id) {
    $fields = [
        'post_title'                    => 2,
        'post_content'                  => 2,
        '_thumbnail_id'                 => 2,
        '_awps_product_hs_code'         => 1,
        '_awps_product_moq'             => 1,
        '_awps_product_lead_time'       => 1,
        '_awps_product_packaging'       => 1,
        '_awps_product_shipping'        => 1,
        '_awps_product_payment_terms'   => 1,
        '_awps_product_incoterms'       => 1,
        '_awps_product_key_benefits'    => 1,
        '_awps_product_quality_control' => 1,
        '_awps_product_sample_policies' => 1,
        '_awps_product_certifications'  => 1,
        '_awps_product_made_in_pakistan'=> 1,
        '_awps_product_factory_video'   => 1,
        'rank_math_title'               => 1,
        'rank_math_description'         => 1,
        'product_categories'            => 1,
        'featured_image_alt'            => 1,
        'gallery_images'                => 1,
    ];
    
    $filled_weight = 0;
    $total_weight = 0;
    
    foreach ($fields as $field => $weight) {
        $total_weight += $weight;
        $value = '';
        $is_filled = false;
        
        // ✅ SPECIAL: Gallery images
        if ($field === 'gallery_images') {
            $gallery_ids = get_post_meta($product_id, '_awps_product_gallery', true);
            $is_filled = !empty($gallery_ids) && count(array_filter(explode(',', $gallery_ids))) > 0;
            $value = $is_filled ? 'has_gallery' : 'no_gallery';
        }
        
        // ✅ SPECIAL: Featured Image Alt Text (stored on attachment)
        elseif ($field === 'featured_image_alt') {
            $thumb_id = get_post_thumbnail_id($product_id);
            $value = $thumb_id ? get_post_meta($thumb_id, '_wp_attachment_image_alt', true) : '';
            $is_filled = !empty($value) && trim((string) $value) !== '' && (string) $value !== '0';
        }
        
        // ✅ SPECIAL: Made in Pakistan checkbox
        elseif ($field === '_awps_product_made_in_pakistan') {
            $value = get_post_meta($product_id, $field, true);
            $is_filled = ($value === 'yes');
        }
        
        // ✅ SPECIAL: Product Categories (reads from taxonomy, not post meta)
        elseif ($field === 'product_categories') {
            $terms = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids', 'orderby' => 'none']);
            $value = !empty($terms) ? implode(',', $terms) : '';
            $is_filled = !empty($value);
        }
        
        // Regular fields
        else {
            if ($field === 'post_title') {
                $value = get_the_title($product_id);
            } elseif ($field === 'post_content') {
                $value = get_post_field('post_content', $product_id);
            } elseif ($field === '_thumbnail_id') {
                $value = get_post_thumbnail_id($product_id) ? '1' : '';
            } else {
                $value = get_post_meta($product_id, $field, true);
                if (is_array($value)) $value = implode(',', $value);
            }
            $is_filled = !empty($value) && trim((string) $value) !== '' && (string) $value !== '0';
        }
        
        if ($is_filled) $filled_weight += $weight;
    }
    
    return $total_weight > 0 ? round(($filled_weight / $total_weight) * 100) : 0;
}

/**
 * Get missing product fields for hints (READ-ONLY for display)
 */
function awps_get_missing_product_fields($product_id) {
    $required = [
        'post_title'              => 'Product Name',
        'post_content'            => 'Description',
        '_thumbnail_id'           => 'Featured Image',
        '_awps_product_hs_code'   => 'HS Code',
        '_awps_product_moq'       => 'MOQ',
        '_awps_product_lead_time' => 'Lead Time',
    ];
    
    $missing = [];
    foreach ($required as $field => $label) {
        if ($field === 'post_title') {
            $value = get_the_title($product_id);
        } elseif ($field === 'post_content') {
            $value = get_post_field('post_content', $product_id);
        } elseif ($field === '_thumbnail_id') {
            $value = get_post_thumbnail_id($product_id) ? '1' : '';
        } else {
            $value = get_post_meta($product_id, $field, true);
            if (is_array($value)) $value = implode(',', $value);
        }
        
        if (empty($value) || trim((string) $value) === '' || (string) $value === '0') {
            $missing[] = $label;
        }
    }
    
    return $missing;
}

// Helper: Delete all media attached to a product (for delete action)
if (!function_exists('awps_delete_product_media')) {
    function awps_delete_product_media($product_id) {
        $thumbnail_id = get_post_thumbnail_id($product_id);
        if ($thumbnail_id) wp_delete_attachment($thumbnail_id, true);

        $gallery_meta = get_post_meta($product_id, '_product_image_gallery', true);
        if ($gallery_meta) {
            $gallery_ids = array_filter(array_map('intval', explode(',', $gallery_meta)));
            foreach ($gallery_ids as $id) {
                if ($id > 0) wp_delete_attachment($id, true);
            }
        }
    }
}

// ─────────────────────────────────────────────────────────────
// DASHBOARD SETUP & GET HANDLERS (No POST handling here)
// ─────────────────────────────────────────────────────────────

$current_dashboard_url = home_url('/dashboard/');

// Handle Notices
if (isset($_GET['saved'])) echo '<div class="notice notice-success"><p>✅ Product saved successfully!</p></div>';
if (isset($_GET['deleted'])) echo '<div class="notice notice-success"><p>🗑️ Product deleted successfully!</p></div>';
if (isset($_GET['toggled'])) echo '<div class="notice notice-success"><p>👁️ Product visibility updated!</p></div>';

// Handle Delete (GET request)
if (isset($_GET['delete']) && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_product_' . $_GET['delete'])) {
    $product_id = intval($_GET['delete']);
    if (get_post_field('post_author', $product_id) == get_current_user_id()) {
        awps_delete_product_media($product_id);
        wp_delete_post($product_id, true);
        wp_safe_redirect(add_query_arg(['tab' => 'products', 'deleted' => '1'], $current_dashboard_url));
        exit;
    }
}

// Handle Toggle Visibility (GET request)
if (isset($_GET['toggle']) && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'toggle_product_' . $_GET['toggle'])) {
    $product_id = intval($_GET['toggle']);
    if (get_post_field('post_author', $product_id) == get_current_user_id()) {
        $current_status = get_post_status($product_id);
        $new_status = ($current_status === 'publish') ? 'draft' : 'publish';
        wp_update_post(['ID' => $product_id, 'post_status' => $new_status]);
        wp_safe_redirect(add_query_arg(['tab' => 'products', 'toggled' => '1'], $current_dashboard_url));
        exit;
    }
}

// ─────────────────────────────────────────────────────────────
// EDIT VIEW (Form only - submits to Products.php handler)
// ─────────────────────────────────────────────────────────────
if (isset($_GET['edit']) && ($product_id = intval($_GET['edit'])) > 0) {
    // ✅ SECURITY: Check ownership
    if (get_post_field('post_author', $product_id) != get_current_user_id()) {
        echo '<p>You do not have permission to edit this product.</p>';
        return;
    }

    $product = get_post($product_id);
    if (!$product || $product->post_type !== 'product') {
        echo '<p>Product not found.</p>';
        return;
    }

    // Load Meta Data for form pre-fill
    $made_in_pakistan = get_post_meta($product_id, '_awps_product_made_in_pakistan', true) ?: 'yes';
    $hs_code          = get_post_meta($product_id, '_awps_product_hs_code', true);
    $moq              = get_post_meta($product_id, '_awps_product_moq', true);
    $packaging        = get_post_meta($product_id, '_awps_product_packaging', true);
    $lead_time        = get_post_meta($product_id, '_awps_product_lead_time', true);
    $shipping         = get_post_meta($product_id, '_awps_product_shipping', true);
    $payment_terms    = get_post_meta($product_id, '_awps_product_payment_terms', true);
    $incoterms        = get_post_meta($product_id, '_awps_product_incoterms', true);
    $key_benefits     = get_post_meta($product_id, '_awps_product_key_benefits', true);
    $quality_control  = get_post_meta($product_id, '_awps_product_quality_control', true);
    $sample_policies  = get_post_meta($product_id, '_awps_product_sample_policies', true);
    $certifications   = get_post_meta($product_id, '_awps_product_certifications', true);
    $factory_video    = get_post_meta($product_id, '_awps_product_factory_video', true);
    $status           = get_post_status($product_id);

    // Categories
    $categories = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']);
    $category_names = [];
    foreach ($categories as $cat_id) {
        $term = get_term($cat_id, 'product_cat');
        if ($term && !is_wp_error($term)) $category_names[$cat_id] = $term->name;
    }

    // Media
    $featured_image_id = get_post_thumbnail_id($product_id);
    $featured_image_url = $featured_image_id ? wp_get_attachment_image_url($featured_image_id, 'medium') : '';
    $featured_alt = get_post_meta($featured_image_id, '_wp_attachment_image_alt', true);
    $gallery_ids_str = get_post_meta($product_id, '_awps_product_gallery', true);
    $gallery_ids_array = $gallery_ids_str ? array_filter(array_map('intval', explode(',', $gallery_ids_str))) : [];

    // Rank Math
    $has_rank_math = defined('RANK_MATH_FILE');
    $rank_math_title = get_post_meta($product_id, 'rank_math_title', true);
    $rank_math_description = get_post_meta($product_id, 'rank_math_description', true);

    // Completion Check for Banner (READ-ONLY display)
    $completion = awps_calculate_product_completion($product_id);
    $has_image = has_post_thumbnail($product_id);
    $has_title = !empty(get_the_title($product_id));
    $has_desc = !empty(get_post_field('post_content', $product_id));
    $is_ready = ($completion >= 50 && $has_image && $has_title && $has_desc);
    $missing = awps_get_missing_product_fields($product_id);
    ?>

    <div class="awps-frontend-form">
        <h2>Edit Product</h2>
        
        <!-- ✅ FORM SUBMITS TO Products.php HANDLER VIA admin-post.php -->
        <form method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" id="awps-product-form">
            <!-- CRITICAL: Tells WordPress which handler to run -->
            <input type="hidden" name="action" value="awps_save_product">
            
            <?php wp_nonce_field('awps_product', 'awps_product_nonce'); ?>
            <input type="hidden" name="product_id" value="<?= esc_attr($product_id); ?>">

            <div class="form-group">
                <label>Product Name*</label>
                <input name="title" value="<?= esc_attr(get_the_title($product_id)); ?>" placeholder="Product name" required>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" placeholder="Product description"><?= esc_textarea(get_post_field('post_content', $product_id)); ?></textarea>
            </div>

            <div class="form-group checkbox-group">
                <input type="hidden" name="made_in_pakistan" value="no">
                <label class="checkbox-label">
                    <input type="checkbox" name="made_in_pakistan" value="yes" <?php checked($made_in_pakistan, 'yes'); ?>>
                    Made in Pakistan
                </label>
            </div>

            <div class="form-group checkbox-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="_product_visibility" value="1" <?= $status === 'publish' ? 'checked' : ''; ?>>
                    Show Product on Store
                </label>
            </div>

            <!-- Featured Image -->
            <div class="form-group">
                <label>🖼️ Featured Image</label>
                <div class="media-upload-container">
                    <div id="featured-image-container">
                        <?php if ($featured_image_url): ?>
                            <div style="position:relative; display:inline-block;">
                                <img src="<?= esc_url($featured_image_url); ?>" style="max-width:200px;border:1px solid #ddd; display:block;">
                                <button type="button" class="media-remove-btn" data-action="remove-featured">×</button>
                                <div class="alt-text-wrapper" style="margin-top:5px;">
                                    <input type="text" name="featured_image_alt" value="<?= esc_attr($featured_alt); ?>" placeholder="Alt text" style="width:100%; font-size:12px; padding:4px;">
                                </div>
                            </div>
                        <?php else: ?>
                            <button type="button" class="media-upload-btn" data-action="upload-featured">Upload Image</button>
                        <?php endif; ?>
                    </div>
                    <input type="hidden" name="featured_image_id" id="featured_image_id" value="<?= $featured_image_id; ?>">
                    <input type="hidden" name="remove_featured_image" id="remove_featured_image" value="0">
                    <input type="file" id="featured-upload-input" name="featured_image_upload" accept="image/*" style="display:none;">
                </div>
            </div>

            <!-- Gallery -->
            <div class="form-group">
                <label>🖼️ Product Gallery</label>
                <div class="media-upload-container">
                    <button type="button" class="media-upload-btn" data-action="upload-gallery">Add Gallery Images</button>
                    <div id="gallery-preview" style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px;">
                        <?php if (!empty($gallery_ids_array)): ?>
                            <?php foreach ($gallery_ids_array as $id):
                                $id = intval($id);
                                if ($id <= 0) continue;
                                $url = wp_get_attachment_image_url($id, 'thumbnail');
                                if (!$url) continue;
                                $gallery_alt = get_post_meta($id, '_wp_attachment_image_alt', true);
                            ?>
                                <div class="gallery-item" data-id="<?= $id; ?>" style="position:relative; display:inline-block; margin:5px;">
                                    <img src="<?= esc_url($url); ?>" style="width:100px;height:100px;object-fit:cover;border-radius:4px;border:1px solid #ddd;">
                                    <input type="hidden" name="gallery_image_id[]" value="<?= $id; ?>">
                                    <button type="button" class="media-remove-btn" data-action="remove-gallery" data-id="<?= $id; ?>">×</button>
                                    <input type="text" name="gallery_image_alt[<?= $id; ?>]" value="<?= esc_attr($gallery_alt); ?>" placeholder="Alt text" style="width:100px; display:block; margin-top:5px; font-size:11px;">
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <input type="file" id="gallery-upload-input" name="gallery_files[]" multiple accept="image/*" style="display:none;">
                    <input type="hidden" name="remove_gallery_ids" id="remove_gallery_ids" value="">
                </div>
            </div>

            <!-- Categories -->
            <div class="form-group awps-tag-container">
                <label>🏷️ Product Categories</label>
                <div class="awps-autocomplete-wrapper" style="position:relative;display:inline-block;width:100%;">
                    <input type="text" id="product_categories_input" class="awps-tag-input" placeholder="Start typing...">
                    <div class="awps-autocomplete-loader"></div>
                </div>
                <div id="product_categories_list" class="awps-tag-list">
                    <?php foreach ($categories as $cat_id):
                        $name = $category_names[$cat_id] ?? '';
                        if (!$name) continue;
                    ?>
                        <span class="tag" data-value="<?= esc_attr($cat_id); ?>"><?= esc_html($name); ?> <span class="remove-tag">×</span></span>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="product_categories" id="product_categories" value="<?= esc_attr(implode(',', array_map('strval', $categories))); ?>">
            </div>

            <!-- HS Code -->
            <div class="form-group">
                <label>🔢 HS Code</label>
                <input name="_hs_code" value="<?= esc_attr($hs_code); ?>" placeholder="e.g., 1006.30">
            </div>

            <!-- MOQ -->
            <div class="form-group">
                <label>📦 Minimum Order Quantity (MOQ)</label>
                <input name="_moq" value="<?= esc_attr($moq); ?>" placeholder="e.g., 1 Ton">
            </div>

            <!-- Packaging -->
            <div class="form-group awps-tag-container">
                <label>🧷 Packaging Options</label>
                <input type="text" id="_packaging_input" class="awps-tag-input" placeholder="Start typing packaging option...">
                <div id="_packaging_list" class="awps-tag-list"></div>
                <input type="hidden" name="_packaging" value="<?= esc_attr($packaging); ?>">
            </div>

            <!-- Lead Time -->
            <div class="form-group">
                <label>⏱️ Lead Time</label>
                <input name="_lead_time" value="<?= esc_attr($lead_time); ?>" placeholder="e.g., 15–20 Days After Order Confirmation">
            </div>

            <!-- Shipping -->
            <div class="form-group awps-tag-container">
                <label>🚢 Shipping Methods</label>
                <input type="text" id="_shipping_input" class="awps-tag-input" placeholder="Start typing shipping method...">
                <div id="_shipping_list" class="awps-tag-list"></div>
                <input type="hidden" name="_shipping" value="<?= esc_attr($shipping); ?>">
            </div>

            <!-- Payment Terms -->
            <div class="form-group awps-tag-container">
                <label>💵 Payment Terms</label>
                <input type="text" id="_payment_terms_input" class="awps-tag-input" placeholder="Start typing payment term...">
                <div id="_payment_terms_list" class="awps-tag-list"></div>
                <input type="hidden" name="_payment_terms" value="<?= esc_attr($payment_terms); ?>">
            </div>

            <!-- Incoterms -->
            <div class="form-group awps-tag-container">
                <label>🧭 Incoterms</label>
                <input type="text" id="_incoterms_input" class="awps-tag-input" placeholder="Start typing incoterm code or description...">
                <div id="_incoterms_list" class="awps-tag-list"></div>
                <input type="hidden" name="_incoterms" value="<?= esc_attr($incoterms); ?>">
            </div>

            <!-- Key Benefits -->
            <div class="form-group awps-tag-container">
                <label>✅ Key Benefits</label>
                <input type="text" id="_key_benefits_input" class="awps-tag-input" placeholder="Start typing benefit...">
                <div id="_key_benefits_list" class="awps-tag-list"></div>
                <input type="hidden" name="_key_benefits" value="<?= esc_attr($key_benefits); ?>">
            </div>

            <!-- Quality Control -->
            <div class="form-group awps-tag-container">
                <label>🧪 Quality Control</label>
                <input type="text" id="_quality_control_input" class="awps-tag-input" placeholder="Start typing QC phrase...">
                <div id="_quality_control_list" class="awps-tag-list"></div>
                <input type="hidden" name="_quality_control" value="<?= esc_attr($quality_control); ?>">
            </div>

            <!-- Sample Policies -->
            <div class="form-group awps-tag-container">
                <label>📦 Sample Policies</label>
                <input type="text" id="_sample_policies_input" class="awps-tag-input" placeholder="Start typing sample policy...">
                <div id="_sample_policies_list" class="awps-tag-list"></div>
                <input type="hidden" name="_sample_policies" value="<?= esc_attr($sample_policies); ?>">
            </div>

            <!-- Certifications -->
            <div class="form-group awps-tag-container">
                <label>🏅 Certifications</label>
                <div class="awps-autocomplete-wrapper" style="position: relative; display: inline-block; width: 100%;">
                    <input type="text" id="_certifications_input" class="awps-tag-input" placeholder="Search certifications...">
                    <div class="awps-autocomplete-loader"></div>
                </div>
                <div id="_certifications_list" class="awps-tag-list">
                    <?php
                    $saved_meta = $certifications;
                    $selected_certs = [];
                    if (!empty($saved_meta)) $selected_certs = array_filter(array_map('intval', explode(',', $saved_meta)));
                    foreach ($selected_certs as $cert_id):
                        $cert = get_post($cert_id);
                        if ($cert && $cert->post_type === 'certification' && $cert->post_status === 'publish'): ?>
                            <span class="tag" data-value="<?= esc_attr($cert_id); ?>">
                                <?= esc_html($cert->post_title); ?> 
                                <span class="remove-tag">×</span>
                            </span>
                        <?php endif;
                    endforeach; ?>
                </div>
                <input type="hidden" name="_certifications" id="_certifications_hidden" value="<?= esc_attr(implode(',', $selected_certs)); ?>">
                <small>Select from dropdown to add. Type to search.</small>
            </div>

            <!-- Factory Video -->
            <div class="form-group">
                <label>🎥 Factory/Processing Video URL (Optional)</label>
                <input name="_factory_video" type="url" value="<?= esc_attr($factory_video); ?>" placeholder="https://youtube.com/watch?v=...">
            </div>
            
            <!-- Rank Math SEO -->
            <?php if ($has_rank_math): ?>
                <div class="form-group">
                    <label>🔍 SEO Title</label>
                    <input name="rank_math_title" value="<?= esc_attr($rank_math_title); ?>" placeholder="SEO title">
                    <small>Recommended: 50-60 characters.</small>
                </div>
                <div class="form-group">
                    <label>📝 Meta Description</label>
                    <textarea name="rank_math_description" rows="3" placeholder="SEO description"><?= esc_textarea($rank_math_description); ?></textarea>
                    <small>Recommended: 150-160 characters.</small>
                </div>
            <?php endif; ?>

            <!-- ✅ COMPLETION BANNER WITH GUIDANCE (EXACT MATCH TO product-form.php) -->
            <div style="background:linear-gradient(135deg, #007cba 0%, #005a87 100%); color:white; padding:20px; border-radius:8px; margin:20px 0; box-shadow:0 4px 12px rgba(0,0,0,0.15);">
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px; margin-bottom:15px;">
                    <div>
                        <span style="font-size:28px; font-weight:700;" class="completion-percentage-text"><?php echo (int) $completion; ?>%</span>
                        <span style="font-size:14px; opacity:0.9; margin-left:10px;">Product Complete</span>
                    </div>
                    <div style="text-align:right;">
                        <span style="display:inline-block; padding:6px 14px; border-radius:20px; font-size:12px; font-weight:600; 
                            background:<?php echo $is_ready ? 'rgba(40,167,69,0.2)' : 'rgba(255,193,7,0.2)'; ?>; 
                            color:<?php echo $is_ready ? '#28a745' : '#ffc107'; ?>; 
                            border:1px solid <?php echo $is_ready ? '#28a745' : '#ffc107'; ?>;" 
                            class="completion-status-badge">
                            <?php echo $is_ready ? '✅ Ready to Publish' : '⚠️ Needs Improvement'; ?>
                        </span>
                    </div>
                </div>
                
                <!-- Progress Bar -->
                <div style="background:rgba(255,255,255,0.2); border-radius:6px; height:8px; margin-bottom:15px; overflow:hidden;">
                    <div id="completion-progress-bar" style="background:<?php echo $completion >= 80 ? '#28a745' : ($completion >= 50 ? '#ffc107' : '#dc3545'); ?>; height:100%; width:<?php echo (int) $completion; ?>%; transition:width 0.4s ease; border-radius:6px;"></div>
                </div>
                
                <!-- Guidance Section -->
                <div style="background:rgba(255,255,255,0.1); border-radius:6px; padding:15px;">
                    <p style="margin:0 0 10px 0; font-size:13px; opacity:0.95;">
                        <strong>📋 To reach 100% and maximize visibility, complete these fields:</strong>
                    </p>
                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(250px, 1fr)); gap:8px; font-size:12px;">
                        <?php 
                        $all_fields = [
                            'title' => 'Product Name',
                            'description' => 'Description',
                            'featured_image_id' => 'Featured Image',
                            '_hs_code' => 'HS Code',
                            '_moq' => 'MOQ',
                            '_lead_time' => 'Lead Time',
                            '_packaging' => 'Packaging Options',
                            '_shipping' => 'Shipping Methods',
                            '_payment_terms' => 'Payment Terms',
                            '_incoterms' => 'Incoterms',
                            '_key_benefits' => 'Key Benefits',
                            '_quality_control' => 'Quality Control',
                            '_sample_policies' => 'Sample Policies',
                            '_certifications' => 'Certifications',
                            '_factory_video' => 'Factory Video',
                            'product_categories' => 'Product Categories',
                            'rank_math_title' => 'SEO Title',
                            'rank_math_description' => 'Meta Description',
                            'made_in_pakistan' => 'Made in Pakistan',
                            'featured_image_alt' => 'Image Alt Text',
                            'gallery_images' => 'Product Gallery',
                        ];
                        
                        foreach ($all_fields as $field => $label) {
                            // Calculate if field is filled (server-side for initial render)
                            $is_filled = false;
                            if ($field === 'title') $is_filled = !empty(get_the_title($product_id));
                            elseif ($field === 'description') $is_filled = !empty(get_post_field('post_content', $product_id));
                            elseif ($field === 'featured_image_id') $is_filled = has_post_thumbnail($product_id);
                            elseif ($field === 'gallery_images') {
                                $gallery_ids = get_post_meta($product_id, '_awps_product_gallery', true);
                                $is_filled = !empty($gallery_ids) && count(array_filter(explode(',', $gallery_ids))) > 0;
                            }
                            elseif ($field === 'featured_image_alt') {
                                $thumb_id = get_post_thumbnail_id($product_id);
                                $alt = $thumb_id ? get_post_meta($thumb_id, '_wp_attachment_image_alt', true) : '';
                                $is_filled = !empty($alt) && trim($alt) !== '';
                            }
                            elseif ($field === 'made_in_pakistan') {
                                $is_filled = (get_post_meta($product_id, '_awps_product_made_in_pakistan', true) === 'yes');
                            }
                            elseif ($field === 'product_categories') {
                                $terms = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']);
                                $is_filled = !empty($terms);
                            }
                            else {
                                $value = get_post_meta($product_id, '_awps_product_' . ltrim($field, '_'), true);
                                $is_filled = !empty($value) && trim((string)$value) !== '' && (string)$value !== '0';
                            }
                            
                            $icon = $is_filled ? '✅' : '⬜';
                            $color = $is_filled ? '#28a745' : '#ffc107';
                            $label_style = $is_filled ? 'opacity:0.7;' : 'font-weight:600;';
                            
                            echo '<div style="display:flex; align-items:center; gap:6px;" data-field="' . esc_attr($field) . '">';
                            echo '<span class="checklist-icon" style="color:' . $color . ';">' . $icon . '</span>';
                            echo '<span class="checklist-label" style="' . $label_style . '">' . esc_html($label) . '</span>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>

            <button type="submit" class="button">💾 Update Product</button>
        </form>
    </div>

    <!-- ✅ LIVE COMPLETION CALCULATOR (JavaScript - EXACT MATCH TO product-form.php) -->
    <script>
    (function() {
        console.log('🔍 Product Completion Script Loading (Edit)...');
        
        // Field configuration matching your form (EDIT PRODUCT FIELDS)
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
            '_quality_control': { weight: 1 },
            '_sample_policies': { weight: 1 },
            '_certifications': { weight: 1 },
            '_factory_video': { weight: 1 },
            'product_categories': { weight: 1 },
            'rank_math_title': { weight: 1 },
            'rank_math_description': { weight: 1 },
            'made_in_pakistan': { weight: 1 },
            'featured_image_alt': { weight: 1 },
            'gallery_images': { weight: 1, type: 'array' },
        };
        
        const totalWeight = Object.values(fieldConfig).reduce((sum, f) => sum + f.weight, 0);
        console.log('📊 Total Weight:', totalWeight);
        
        function calculateAndDisplay() {
            let filledWeight = 0;
            
            for (const [fieldName, config] of Object.entries(fieldConfig)) {
                
                // ✅ SPECIAL: Gallery images (array field)
                if (fieldName === 'gallery_images') {
                    const galleryFields = document.querySelectorAll('[name="gallery_image_id[]"]');
                    const hasAnyGallery = Array.from(galleryFields).some(f => f.value && f.value.trim() !== '');
                    if (hasAnyGallery) filledWeight += config.weight;
                    continue;
                }
                
                // ✅ SPECIAL: Tag container fields (check hidden input OR visible input)
                const tagFields = ['_packaging', '_shipping', '_payment_terms', '_incoterms', '_key_benefits', '_quality_control', '_sample_policies', '_certifications'];
                if (tagFields.includes(fieldName)) {
                    const hiddenInput = document.querySelector(`[name="${fieldName}"]`);
                    const visibleInput = document.querySelector(`[id="${fieldName}_input"]`);
                    
                    // Field is filled if hidden input has value OR visible input has text
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
                statusBadge.textContent = isReady ? '✅ Ready to Publish' : '⚠️ Needs Improvement';
                statusBadge.style.background = isReady ? 'rgba(40,167,69,0.2)' : 'rgba(255,193,7,0.2)';
                statusBadge.style.color = isReady ? '#28a745' : '#ffc107';
                statusBadge.style.borderColor = isReady ? '#28a745' : '#ffc107';
            }
            
            // ✅ Update guidance checklist icons (tag-aware version)
            for (const [fieldName, config] of Object.entries(fieldConfig)) {
                const checklistItem = document.querySelector(`[data-field="${fieldName}"] .checklist-icon`);
                if (!checklistItem) continue;
                
                let isFilled = false;
                
                // Special handling for gallery_images (array field)
                if (fieldName === 'gallery_images') {
                    const galleryFields = document.querySelectorAll('[name="gallery_image_id[]"]');
                    isFilled = Array.from(galleryFields).some(f => f.value && f.value.trim() !== '');
                }
                // Special handling for tag container fields
                else if (['_packaging', '_shipping', '_payment_terms', '_incoterms', '_key_benefits', '_quality_control', '_sample_policies', '_certifications'].includes(fieldName)) {
                    const hiddenInput = document.querySelector(`[name="${fieldName}"]`);
                    const visibleInput = document.querySelector(`[id="${fieldName}_input"]`);
                    
                    const hiddenValue = hiddenInput ? hiddenInput.value.trim() : '';
                    const visibleValue = visibleInput ? visibleInput.value.trim() : '';
                    isFilled = (hiddenValue && hiddenValue !== '0') || (visibleValue && visibleValue !== '0');
                }
                // Special handling for checkboxes
                else if (fieldName === 'made_in_pakistan') {
                    const checkbox = document.querySelector(`[name="${fieldName}"]`);
                    isFilled = checkbox ? checkbox.checked : false;
                }
                // Special handling for featured_image_alt
                else if (fieldName === 'featured_image_alt') {
                    const altInput = document.querySelector(`[name="${fieldName}"]`);
                    isFilled = altInput ? altInput.value.trim() !== '' : false;
                }
                // Regular field handling
                else {
                    const field = document.querySelector(`[name="${fieldName}"]`);
                    if (field) {
                        const value = field.type === 'checkbox' ? (field.checked ? 'yes' : '') : field.value.trim();
                        isFilled = value && value !== '0' && value !== '';
                    }
                }
                
                // Update the icon
                checklistItem.textContent = isFilled ? '✅' : '⬜';
                checklistItem.style.color = isFilled ? '#28a745' : '#ffc107';
                
                // Optional: Update label style
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
            
            // ✅ Listen for tag additions/removals (custom events from your tag system)
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
        
        console.log('✅ Product Completion Script Loaded (Edit)');
    })();
    </script>

    <?php
    return; // End of Edit View
}

// ─────────────────────────────────────────────────────────────
// PRODUCT LIST TABLE (Read-only display)
// ─────────────────────────────────────────────────────────────

$mine = new WP_Query([
    'post_type'      => 'product',
    'author'         => get_current_user_id(),
    'posts_per_page' => -1,
    'post_status'    => ['publish', 'draft']
]);

if ($mine->have_posts()) {
    echo '<table class="product-list-table">';
    echo '<thead><tr><th>Image</th><th>Product</th><th>Completion</th><th>Views</th><th>Status</th><th>Actions</th></tr></thead>';
    echo '<tbody>';
    while($mine->have_posts()) {
        $mine->the_post();
        $product_id = get_the_ID();
        $edit_url = add_query_arg(['tab' => 'products', 'edit' => $product_id], $current_dashboard_url);
        $delete_url = wp_nonce_url(add_query_arg(['tab' => 'products', 'delete' => $product_id], $current_dashboard_url), 'delete_product_' . $product_id, '_wpnonce');
        $toggle_url = wp_nonce_url(add_query_arg(['tab' => 'products', 'toggle' => $product_id], $current_dashboard_url), 'toggle_product_' . $product_id, '_wpnonce');
        $status = get_post_status($product_id);
        $status_label = ($status === 'publish') ? '✅ Published' : '👁️ Hidden';
        $views = (int) get_post_meta($product_id, 'views', true);
        
        // Calculate completion for display
        $completion = awps_calculate_product_completion($product_id);
        $completion_color = $completion >= 80 ? '#28a745' : ($completion >= 50 ? '#ffc107' : '#dc3545');
        
        // Thumbnail
        $featured_id = get_post_thumbnail_id($product_id);
        if ($featured_id) {
            $thumbnail = wp_get_attachment_image($featured_id, [60, 60], false, ['style' => 'object-fit: cover; border-radius: 4px; width: 60px; height: 60px;']);
        } else {
            $thumbnail = '<span style="display:inline-block;width:60px;height:60px;background:#f0f0f0;border-radius:4px;"></span>';
        }

        echo '<tr>';
        echo '<td>' . $thumbnail . '</td>';
        echo '<td><a href="' . get_permalink() . '" target="_blank">' . get_the_title() . '</a></td>';
        echo '<td style="text-align:center;"><span style="color:' . esc_attr($completion_color) . ';font-weight:600;">' . (int) $completion . '%</span></td>';
        echo '<td>' . esc_html($views) . '</td>';
        echo '<td><span class="' . esc_attr($status === 'publish' ? 'status-published' : 'status-draft') . '">' . $status_label . '</span></td>';
        echo '<td>';
        echo '<a href="' . esc_url($edit_url) . '">Edit</a> | ';
        echo '<a href="' . esc_url($toggle_url) . '" onclick="return confirm(\'' . esc_js($status === 'publish' ? 'Hide this product?' : 'Show this product?') . '\');">' . ($status === 'publish' ? 'Hide' : 'Show') . '</a> | ';
        echo '<a href="' . esc_url($delete_url) . '" onclick="return confirm(\'Delete this product permanently?\');">Delete</a>';
        echo '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    wp_reset_postdata();
} else {
    echo '<p>No products yet.</p>';
}
?>