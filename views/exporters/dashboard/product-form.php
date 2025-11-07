<?php
/**
 * Frontend Product Form for Exporters
 * Handles both Create and Edit
 */

// Ensure user is logged in and is an exporter
if (!is_user_logged_in()) {
    wp_die('You must be logged in to access this page.');
}

// Check if editing existing product
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$product = $product_id ? wc_get_product($product_id) : null;

// Get product status safely
$product_status = $product ? $product->get_status() : 'publish';

// Security check: ensure user owns this product
if ($product && $product->post->post_author != get_current_user_id()) {
    wp_die('You do not have permission to edit this product.');
}

// Handle form submission
if ($_POST && isset($_POST['awps_product_nonce']) && wp_verify_nonce($_POST['awps_product_nonce'], 'awps_product')) {
    // Prepare product data
    $title = sanitize_text_field($_POST['title']);
    $description = sanitize_textarea_field($_POST['description']);
    $price = floatval($_POST['price']);
    $sku = sanitize_text_field($_POST['sku'] ?? '');
    $stock_status = isset($_POST['_stock_status']) ? 'instock' : 'outofstock';

    if ($product_id && $product) {
        // Update existing product
        $product->set_name($title);
        $product->set_description($description);
        $product->set_price($price);
        $product->set_sku($sku);
        $product->set_stock_status($stock_status);
        $status = isset($_POST['_product_visibility']) ? 'publish' : 'draft';
        $product->set_status($status);
        $product->save();
        $product_id = $product->get_id();
    } else {
        // Create new product
        $product = new WC_Product_Simple();
        $product->set_name($title);
        $product->set_description($description);
        $product->set_price($price);
        $product->set_sku($sku);
        $product->set_stock_status($stock_status);
        //$product->set_status('publish');

        $status = isset($_POST['_product_visibility']) ? 'publish' : 'draft';
        $product->set_status($status);
        $product->set_catalog_visibility('visible');
        $product->set_sold_individually(false);
        $product_id = $product->save();
        
        // Set author to current user
        wp_update_post([
            'ID' => $product_id,
            'post_author' => get_current_user_id()
        ]);
    }

    // Save custom meta fields
    $meta_fields = [
        '_made_in_pakistan',
        '_hs_code',
        '_moq',
        '_packaging_details',
        '_lead_time',
        '_shipping_options',
        '_payment_terms',
        '_incoterms',
        '_key_benefits',
        '_quality_control',
        '_sample_policies',
        '_certifications',
        '_factory_video_url'
    ];

    foreach ($meta_fields as $field) {
        if ($field === '_made_in_pakistan') {
            // Always save yes/no
            $value = (!empty($_POST[$field]) && $_POST[$field] === 'yes') ? 'yes' : 'no';
            update_post_meta($pid, $field, $value);
        } else {
            if (isset($_POST[$field])) {
                update_post_meta($pid, $field, sanitize_textarea_field($_POST[$field]));
            }
        }
    }

    // Save categories
    if (isset($_POST['product_categories']) && !empty($_POST['product_categories'])) {
        $category_ids = array_filter(array_map('intval', explode(',', $_POST['product_categories'])));
        wp_set_object_terms($product_id, $category_ids, 'product_cat');
    } else {
        wp_delete_object_term_relationships($product_id, 'product_cat');
    }

    // Save Yoast SEO fields if Yoast is active
    if (class_exists('WPSEO_Meta')) {
        if (isset($_POST['_yoast_wpseo_title'])) {
            WPSEO_Meta::set_value('title', sanitize_text_field($_POST['_yoast_wpseo_title']), $product_id);
        }
        if (isset($_POST['_yoast_wpseo_metadesc'])) {
            WPSEO_Meta::set_value('metadesc', sanitize_textarea_field($_POST['_yoast_wpseo_metadesc']), $product_id);
        }
    }

    // Handle featured image
    if (isset($_POST['featured_image_id']) && $_POST['featured_image_id'] > 0) {
        set_post_thumbnail($product_id, intval($_POST['featured_image_id']));
    } elseif (isset($_POST['remove_featured_image'])) {
        delete_post_thumbnail($product_id);
    }

    // Handle gallery images
    if (isset($_POST['gallery_image_ids'])) {
        $gallery_ids = array_filter(array_map('intval', explode(',', $_POST['gallery_image_ids'])));
        update_post_meta($product_id, '_product_image_gallery', implode(',', $gallery_ids));
    }

    // Redirect or show success
    echo '<div class="notice notice-success"><p>✅ Product saved successfully! <a href="' . get_permalink($product_id) . '">View Product</a></p></div>';
}

// Get current values
$title = $product ? $product->get_name() : '';
$description = $product ? $product->get_description() : '';
$price = $product ? $product->get_price() : '';
$sku = $product ? $product->get_sku() : '';
$stock_status = $product ? $product->get_stock_status() : 'instock';

// Get meta values
$made_in_pakistan = $product ? get_post_meta($product->get_id(), '_made_in_pakistan', true) : 'yes';
$hs_code = $product ? get_post_meta($product->get_id(), '_hs_code', true) : '';
$moq = $product ? get_post_meta($product->get_id(), '_moq', true) : '';
$packaging = $product ? get_post_meta($product->get_id(), '_packaging_details', true) : '';
$lead_time = $product ? get_post_meta($product->get_id(), '_lead_time', true) : '';
$shipping = $product ? get_post_meta($product->get_id(), '_shipping_options', true) : '';
$payment_terms = $product ? get_post_meta($product->get_id(), '_payment_terms', true) : '';
$incoterms = $product ? get_post_meta($product->get_id(), '_incoterms', true) : '';
$key_benefits = $product ? get_post_meta($product->get_id(), '_key_benefits', true) : '';
$quality_control = $product ? get_post_meta($product->get_id(), '_quality_control', true) : '';
$sample_policies = $product ? get_post_meta($product->get_id(), '_sample_policies', true) : '';
$certifications = $product ? get_post_meta($product->get_id(), '_certifications', true) : '';
$factory_video = $product ? get_post_meta($product->get_id(), '_factory_video_url', true) : '';

// Get categories
$categories = $product ? wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'ids']) : [];
$category_names = $product ? wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'names']) : [];

// Get featured image
$featured_image_id = $product ? get_post_thumbnail_id($product->get_id()) : 0;
$featured_image_url = $featured_image_id ? wp_get_attachment_image_url($featured_image_id, 'medium') : '';

// Get gallery images
$gallery_ids = $product ? get_post_meta($product->get_id(), '_product_image_gallery', true) : '';
$gallery_ids_array = $gallery_ids ? explode(',', $gallery_ids) : [];
$gallery_images = [];
foreach ($gallery_ids_array as $id) {
    $url = wp_get_attachment_image_url($id, 'thumbnail');
    if ($url) {
        $gallery_images[] = ['id' => $id, 'url' => $url];
    }
}

// Get Yoast SEO fields
$yoast_title = $product ? (class_exists('WPSEO_Meta') ? WPSEO_Meta::get_value('title', $product->get_id()) : '') : '';
$yoast_description = $product ? (class_exists('WPSEO_Meta') ? WPSEO_Meta::get_value('metadesc', $product->get_id()) : '') : '';

?>

    <?php wp_head(); ?>
    <style>
        .awps-frontend-form {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .awps-frontend-form h2 {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #007cba;
            color: #212529;
        }

        .awps-frontend-form .form-group {
            margin-bottom: 25px;
        }

        .awps-frontend-form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #495057;
        }

        .awps-frontend-form input,
        .awps-frontend-form textarea,
        .awps-frontend-form select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 1rem;
            box-sizing: border-box;
        }

        .awps-frontend-form textarea {
            min-height: 120px;
            resize: vertical;
        }

        .awps-frontend-form button {
            background: #007cba;
            color: white;
            border: none;
            padding: 14px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .awps-frontend-form button:hover {
            background: #005a87;
        }

        /* Reuse your profile's tag styles */
        .awps-tag-container { 
            margin: 15px 0; 
            padding: 10px; 
            background: #fafafa; 
            border: 1px solid #eee; 
            border-radius: 6px; 
        }
        .awps-tag-container label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 600; 
        }
        .awps-tag-input { 
            width: 100%; 
            padding: 8px 12px; 
            margin-bottom: 8px; 
            box-sizing: border-box; 
            border: 1px solid #ddd; 
            border-radius: 4px; 
        }
        .awps-tag-list { 
            min-height: 40px; 
            border: 1px solid #ddd; 
            padding: 8px; 
            border-radius: 4px; 
            background: #fff; 
            margin-bottom: 5px; 
        }
        .awps-tag-list .tag { 
            display: inline-block; 
            background: #f0f0f0; 
            padding: 5px 10px; 
            margin: 3px; 
            border-radius: 3px; 
            font-size: 14px; 
        }
        .awps-tag-list .tag .remove-tag { 
            color: #d00; 
            cursor: pointer; 
            margin-left: 5px; 
            font-weight: bold; 
        }

        /* Media Upload */
        .media-upload-container {
            border: 2px dashed #e9ecef;
            padding: 20px;
            text-align: center;
            margin: 15px 0;
            border-radius: 6px;
            background: #fafafa;
        }
        .media-upload-container img {
            max-width: 100%;
            height: auto;
            margin: 10px 0;
            border-radius: 4px;
        }
        .media-upload-btn {
            background: #007cba;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        .media-remove-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        .gallery-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 15px 0;
        }
        .gallery-preview img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ddd;
        }

        /* Autocomplete */
        .ui-autocomplete {
            max-width: 400px !important;
            width: auto !important;
            background: #fff !important;
            border: 1px solid #ddd !important;
            border-radius: 4px !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1) !important;
            z-index: 9999 !important;
        }
        .ui-helper-hidden-accessible { display: none !important; }

        /* Notices */
        .notice { padding: 10px; margin: 20px 0; border-left: 4px solid #00a0d2; background: #f0f0f0; }
        .notice-success { border-left-color: #46b450; }
        .notice-error { border-left-color: #dc3232; }
    </style>

<div class="awps-frontend-form">
    <h2><?= $product_id ? 'Edit Product' : 'Add New Product' ?></h2>
    
    <form method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('awps_product','awps_product_nonce'); ?>
        <input type="hidden" name="product_id" value="<?= esc_attr($product_id); ?>">

        <div class="form-group">
            <label>Product Name*</label>
            <input name="title" value="<?= esc_attr($title); ?>" placeholder="Product name" required>
        </div>

        <div class="form-group">
            <label>SKU</label>
            <input name="sku" value="<?= esc_attr($sku); ?>" placeholder="Stock Keeping Unit">
        </div>

        <div class="form-group">
            <label>Description</label>
            <textarea name="description" placeholder="Product description"><?= esc_textarea($description); ?></textarea>
        </div>

        <div class="form-group">
            <label>Price ($)</label>
            <input name="price" type="number" step="0.01" value="<?= esc_attr($price); ?>" placeholder="Ex-Factory Price">
        </div>

        <!-- Stock Status -->
        <div class="form-group">
            <label>
                <input type="checkbox" name="_stock_status" value="1" <?= $stock_status === 'instock' ? 'checked' : ''; ?>>
                ✅ In Stock
            </label>
        </div>

        <!-- Publish Status -->
        <div class="form-group">
            <label>
                <input type="checkbox" name="_product_visibility" value="1" 
                    <?= $product_status === 'publish' ? 'checked' : ''; ?>>
                👁️ Show Product on Store (Uncheck to hide)
            </label>
            <small>Products set to "Hide" will not appear in search results or category pages.</small>
        </div>

        <!-- Featured Image -->
        <div class="form-group">
            <label>🖼️ Featured Image</label>
            <div class="media-upload-container">
                <?php if ($featured_image_url): ?>
                    <img src="<?= esc_url($featured_image_url); ?>" alt="Featured Image">
                    <input type="hidden" name="featured_image_id" value="<?= esc_attr($featured_image_id); ?>">
                    <button type="button" class="media-remove-btn" onclick="removeFeaturedImage(this)">Remove Image</button>
                <?php else: ?>
                    <button type="button" class="media-upload-btn" onclick="uploadFeaturedImage(this)">Upload Image</button>
                    <input type="hidden" name="featured_image_id" value="">
                <?php endif; ?>
            </div>
        </div>

        <!-- Product Gallery -->
        <div class="form-group">
            <label>🖼️ Product Gallery</label>
            <div class="media-upload-container">
                <button type="button" class="media-upload-btn" onclick="uploadGalleryImages(this)">Add Gallery Images</button>
                <div class="gallery-preview" id="gallery-preview">
                    <?php foreach ($gallery_images as $img): ?>
                        <div class="gallery-item">
                            <img src="<?= esc_url($img['url']); ?>" alt="Gallery Image">
                            <button type="button" class="media-remove-btn" style="width:100%; padding:3px;" onclick="removeGalleryImage(this)">Remove</button>
                            <input type="hidden" name="gallery_image_id[]" value="<?= esc_attr($img['id']); ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="gallery_image_ids" id="gallery_image_ids" value="<?= esc_attr($gallery_ids); ?>">
            </div>
        </div>

        <!-- Product Categories -->
        <div class="form-group awps-tag-container">
            <label>🏷️ Product Categories</label>
            <input type="text" id="product_categories_input" class="awps-tag-input" placeholder="Start typing category name...">
            <div id="product_categories_list" class="awps-tag-list"></div>
            <input type="hidden" name="product_categories" id="product_categories" value="<?= esc_attr(implode(',', $categories)); ?>">
        </div>

        <!-- Made in Pakistan -->
        <div class="form-group">
            <input type="hidden" name="_made_in_pakistan" value="no">
            <label>
                <input type="checkbox" name="_made_in_pakistan" value="yes" <?php checked($made_in_pakistan, 'yes'); ?>>
                Made in Pakistan
            </label>
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

        <!-- Packaging Details -->
        <div class="form-group awps-tag-container">
            <label>🧷 Packaging Options</label>
            <input type="text" id="_packaging_details_input" class="awps-tag-input" placeholder="Start typing packaging option...">
            <div id="_packaging_details_list" class="awps-tag-list"></div>
            <input type="hidden" name="_packaging_details" value="<?= esc_attr($packaging); ?>">
        </div>

        <!-- Lead Time -->
        <div class="form-group">
            <label>⏱️ Lead Time</label>
            <input name="_lead_time" value="<?= esc_attr($lead_time); ?>" placeholder="e.g., 15–20 Days After Order Confirmation">
        </div>

        <!-- Shipping Options -->
        <div class="form-group awps-tag-container">
            <label>🚢 Shipping Methods</label>
            <input type="text" id="_shipping_options_input" class="awps-tag-input" placeholder="Start typing shipping method...">
            <div id="_shipping_options_list" class="awps-tag-list"></div>
            <input type="hidden" name="_shipping_options" value="<?= esc_attr($shipping); ?>">
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
            <input type="text" id="_key_benefits_input" class="awps-tag-input" placeholder="Start typing benefit or press Enter/Comma">
            <div id="_key_benefits_list" class="awps-tag-list"></div>
            <input type="hidden" name="_key_benefits" value="<?= esc_attr($key_benefits); ?>">
        </div>

        <!-- Quality Control -->
        <div class="form-group awps-tag-container">
            <label>🧪 Quality Control</label>
            <input type="text" id="_quality_control_input" class="awps-tag-input" placeholder="Start typing QC phrase or press Enter/Comma">
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
            <input type="text" id="_certifications_input" class="awps-tag-input" placeholder="Start typing certification name...">
            <div id="_certifications_list" class="awps-tag-list"></div>
            <input type="hidden" name="_certifications" value="<?= esc_attr($certifications); ?>">
        </div>

        <!-- Factory Video -->
        <div class="form-group">
            <label>🎥 Factory/Processing Video URL (Optional)</label>
            <input name="_factory_video_url" type="url" value="<?= esc_attr($factory_video); ?>" placeholder="https://youtube.com/watch?v=...">
        </div>

        <!-- Yoast SEO Fields -->
        <?php if (class_exists('WPSEO_Meta')): ?>
            <div class="form-group">
                <label>🔍 Meta Title (Yoast SEO)</label>
                <input name="_yoast_wpseo_title" value="<?= esc_attr($yoast_title); ?>" placeholder="SEO title for search engines">
            </div>
            <div class="form-group">
                <label>📝 Meta Description (Yoast SEO)</label>
                <textarea name="_yoast_wpseo_metadesc" placeholder="SEO description for search engines"><?= esc_textarea($yoast_description); ?></textarea>
            </div>
        <?php endif; ?>

        <button type="submit" class="button">💾 <?= $product_id ? 'Update Product' : 'Save Product' ?></button>
    </form>
</div>

<script>
// Include setupTagWidget if not in app.js
if (typeof setupTagWidget !== 'function') {
    function setupTagWidget(opts) {
        const $input = jQuery(opts.inputSelector);
        const $list = jQuery(opts.listSelector);
        const $hidden = jQuery(opts.hiddenSelector);
        if ($input.length === 0 || $list.length === 0 || $hidden.length === 0) return;

        function getCurrentValues() {
            return $list.find('.tag').toArray().map(function(el) {
                return jQuery(el).attr('data-value') ? jQuery(el).attr('data-value').toString() : jQuery(el).clone().children().remove().end().text().trim();
            });
        }

        function updateHidden() {
            const vals = getCurrentValues();
            $hidden.val(vals.join(','));
        }

        function addTag(value) {
            value = String(value || '').trim();
            if (!value) return;
            const current = getCurrentValues();
            if (current.indexOf(value) !== -1) return;
            const $tag = jQuery('<span/>', { class: 'tag', 'data-value': value }).text(value + ' ');
            const $remove = jQuery('<span/>', { class: 'remove-tag' }).text('×');
            $remove.on('click', function(e) {
                e.preventDefault();
                $tag.remove();
                updateHidden();
            });
            $tag.append($remove);
            $list.append($tag);
            updateHidden();
        }

        // Initialize from DOM or hidden field
        if ($list.find('.tag').length > 0) {
            $list.find('.tag').each(function() {
                const $t = jQuery(this);
                if (!$t.attr('data-value')) {
                    const text = $t.clone().children().remove().end().text().trim();
                    $t.attr('data-value', text);
                }
            });
            updateHidden();
        } else {
            const raw = ($hidden.val() || '').toString();
            const arr = raw.split(',').map(t => t.trim()).filter(t => t.length > 0);
            $list.empty();
            $hidden.val('');
            arr.forEach(v => addTag(v));
        }

        // Autocomplete
        if (opts.sourceType === 'ajax' && opts.ajaxAction && jQuery.ui && jQuery.ui.autocomplete) {
            $input.autocomplete({
                source: function(request, response) {
                    jQuery.ajax({
                        url: '<?= admin_url("admin-ajax.php"); ?>',
                        dataType: 'json',
                        data:{
                            action: opts.ajaxAction,
                            term: request.term
                        },
                        success: function(data) { response(data || []); },
                        error: function() { response([]); }
                    });
                },
                focus: function() { return false; },
                select: function(event, ui) {
                    const val = (ui && ui.item && (ui.item.value || ui.item.label)) || ui.item || '';
                    addTag(val);
                    $input.val('');
                    return false;
                }
            });
        } else if (opts.sourceType === 'array' && Array.isArray(opts.sourceArray) && jQuery.ui && jQuery.ui.autocomplete) {
            $input.autocomplete({
                source: function(request, response) {
                    const term = (request.term || '').toLowerCase();
                    const results = opts.sourceArray.filter(item =>
                        String(item).toLowerCase().indexOf(term) !== -1
                    );
                    response(results);
                },
                focus: function() { return false; },
                select: function(event, ui) {
                    const val = (ui && ui.item && (ui.item.value || ui.item.label)) || ui.item || '';
                    addTag(val);
                    $input.val('');
                    return false;
                }
            });
        }

        // Manual entry
        if (opts.allowManualEntry) {
            $input.on('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ',') {
                    e.preventDefault();
                    const v = $input.val().trim();
                    if (v) addTag(v);
                    $input.val('');
                }
            });
        }
    }
}

jQuery(document).ready(function($) {
    // Certifications — AJAX from CPT (same as profile)
    setupTagWidget({
        inputSelector: '#_certifications_input',
        listSelector: '#_certifications_list',
        hiddenSelector: "input[name='_certifications']",
        sourceType: 'ajax',
        ajaxAction: 'get_certifications',
        allowManualEntry: false
    });

    // Incoterms
    const incoterms = <?= json_encode(array_map(function($code, $desc) {
        return "$code — $desc";
    }, array_keys(get_intoterms()), get_intoterms()), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    setupTagWidget({
        inputSelector: '#_incoterms_input',
        listSelector: '#_incoterms_list',
        hiddenSelector: "input[name='_incoterms']",
        sourceType: 'array',
        sourceArray: incoterms,
        allowManualEntry: true
    });

    // Packaging
    setupTagWidget({
        inputSelector: '#_packaging_details_input',
        listSelector: '#_packaging_details_list',
        hiddenSelector: "input[name='_packaging_details']",
        sourceType: 'array',
        sourceArray: <?= json_encode(get_packaging_options(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
        allowManualEntry: true
    });

    // Shipping
    setupTagWidget({
        inputSelector: '#_shipping_options_input',
        listSelector: '#_shipping_options_list',
        hiddenSelector: "input[name='_shipping_options']",
        sourceType: 'array',
        sourceArray: <?= json_encode(get_shipping_methods(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
        allowManualEntry: true
    });

    // Payment Terms
    setupTagWidget({
        inputSelector: '#_payment_terms_input',
        listSelector: '#_payment_terms_list',
        hiddenSelector: "input[name='_payment_terms']",
        sourceType: 'array',
        sourceArray: <?= json_encode(get_payment_terms(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
        allowManualEntry: true
    });

    // Sample Policies
    setupTagWidget({
        inputSelector: '#_sample_policies_input',
        listSelector: '#_sample_policies_list',
        hiddenSelector: "input[name='_sample_policies']",
        sourceType: 'array',
        sourceArray: <?= json_encode(get_sample_policies(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
        allowManualEntry: true
    });

    // Key Benefits
    setupTagWidget({
        inputSelector: '#_key_benefits_input',
        listSelector: '#_key_benefits_list',
        hiddenSelector: "input[name='_key_benefits']",
        sourceType: 'array',
        sourceArray: <?= json_encode(get_key_benefits_options(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
        allowManualEntry: true
    });

    // Quality Control
    setupTagWidget({
        inputSelector: '#_quality_control_input',
        listSelector: '#_quality_control_list',
        hiddenSelector: "input[name='_quality_control']",
        sourceType: 'array',
        sourceArray: <?= json_encode(get_quality_control_options(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
        allowManualEntry: true
    });

    // Product Categories
    const categories = <?php
        $cats = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false, 'fields' => 'names']);
        echo json_encode($cats, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    ?>;
    setupTagWidget({
        inputSelector: '#product_categories_input',
        listSelector: '#product_categories_list',
        hiddenSelector: "#product_categories",
        sourceType: 'array',
        sourceArray: categories,
        allowManualEntry: true
    });

});
</script>

















<script>
jQuery(document).ready(function($) {
    // Reuse the same frame for featured image
    let featuredFrame = null;
    window.uploadFeaturedImage = function(button) {
        // Destroy previous frame if exists
        if (featuredFrame) {
            featuredFrame.dispose();
        }

        featuredFrame = wp.media({
            title: 'Select or Upload Featured Image',
            button: { text: 'Use this image' },
            multiple: false
        });

        featuredFrame.on('select', function() {
            const attachment = featuredFrame.state().get('selection').first().toJSON();
            const container = $(button).closest('.media-upload-container');
            container.html(`
                <img src="${attachment.url}" alt="Featured Image" style="max-width:200px;height:auto;border-radius:4px;">
                <input type="hidden" name="featured_image_id" value="${attachment.id}">
                <button type="button" class="media-remove-btn" onclick="removeFeaturedImage(this)">Remove Image</button>
            `);
        });

        featuredFrame.open();
    };

    // Reuse the same frame for gallery
    let galleryFrame = null;
    window.uploadGalleryImages = function(button) {
        if (galleryFrame) {
            galleryFrame.dispose();
        }

        galleryFrame = wp.media({
            title: 'Select or Upload Gallery Images',
            button: { text: 'Use these images' },
            multiple: true
        });

        galleryFrame.on('select', function() {
            const attachments = galleryFrame.state().get('selection').toJSON();
            const galleryPreview = $('#gallery-preview');
            let galleryIds = [];

            $('input[name="gallery_image_id[]"]').each(function() {
                const id = $(this).val();
                if (id) galleryIds.push(id);
            });

            attachments.forEach(function(attachment) {
                if (!galleryIds.includes(attachment.id.toString())) {
                    galleryIds.push(attachment.id);
                    galleryPreview.append(`
                        <div class="gallery-item">
                            <img src="${attachment.url}" alt="Gallery Image" style="width:100px;height:100px;object-fit:cover;border-radius:4px;border:1px solid #ddd;">
                            <button type="button" class="media-remove-btn" style="width:100%; padding:3px;" onclick="removeGalleryImage(this)">Remove</button>
                            <input type="hidden" name="gallery_image_id[]" value="${attachment.id}">
                        </div>
                    `);
                }
            });

            $('#gallery_image_ids').val(galleryIds.join(','));
        });

        galleryFrame.open();
    };

    // Remove functions (same as before)
    window.removeFeaturedImage = function(button) {
        const container = $(button).closest('.media-upload-container');
        container.html(`
            <button type="button" class="media-upload-btn" onclick="uploadFeaturedImage(this)">Upload Image</button>
            <input type="hidden" name="featured_image_id" value="">
        `);
    };

    window.removeGalleryImage = function(button) {
        $(button).closest('.gallery-item').remove();
        updateGalleryIds();
    };

    function updateGalleryIds() {
        const ids = [];
        $('input[name="gallery_image_id[]"]').each(function() {
            const id = $(this).val();
            if (id) ids.push(id);
        });
        $('#gallery_image_ids').val(ids.join(','));
    }

    updateGalleryIds();
});
</script>
<?php wp_footer(); ?>
