<?php
/**
 * Supplier Products Custom Post Type Handler
 * 
 * @package AWPS\Suppliers
 */

namespace AWPS\Suppliers;

use WP_Query;

class SupplierProducts
{
    const POST_TYPE = 'product';
    const META_PREFIX = '_awps_product_';

    public function __construct()
    {
        add_action('init', [$this, 'register_hooks']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post_' . self::POST_TYPE, [$this, 'save_product_meta'], 10, 2);
        add_filter('manage_' . self::POST_TYPE . '_posts_columns', [$this, 'add_admin_columns']);
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', [$this, 'render_admin_columns'], 10, 2);
        add_action('rest_api_init', [$this, 'register_rest_fields']);
        add_action('show_user_profile', [$this, 'show_supplier_product_count']);
        add_action('edit_user_profile', [$this, 'show_supplier_product_count']);
    }

    public function register_hooks()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_filter('body_class', [$this, 'add_body_class']);
        add_filter('post_type_labels_' . self::POST_TYPE, [$this, 'modify_admin_labels']);
    }

    public function modify_admin_labels($labels)
    {
        $labels->name = __('Supplier Products', 'awps');
        $labels->singular_name = __('Supplier Product', 'awps');
        $labels->add_new = __('Add Product', 'awps');
        $labels->add_new_item = __('Add New Product', 'awps');
        $labels->edit_item = __('Edit Product', 'awps');
        $labels->new_item = __('New Product', 'awps');
        $labels->view_item = __('View Product', 'awps');
        $labels->search_items = __('Search Products', 'awps');
        $labels->not_found = __('No products found', 'awps');
        $labels->not_found_in_trash = __('No products found in trash', 'awps');
        return $labels;
    }

    public function add_meta_boxes()
    {
        add_meta_box(
            'awps_product_tabbed_meta',
            __('Product Details', 'awps'),
            [$this, 'render_tabbed_meta_box'],
            self::POST_TYPE,
            'normal',
            'high'
        );
    }

    public function render_tabbed_meta_box($post)
    {
        wp_nonce_field('awps_product_tabbed_meta', 'awps_product_tabbed_meta_nonce');
        
        $values = [
            'made_in_pakistan' => get_post_meta($post->ID, self::META_PREFIX . 'made_in_pakistan', true) ?: 'yes',
            'hs_code'          => get_post_meta($post->ID, self::META_PREFIX . 'hs_code', true),
            'moq'              => get_post_meta($post->ID, self::META_PREFIX . 'moq', true),
            'packaging'        => get_post_meta($post->ID, self::META_PREFIX . 'packaging', true),
            'lead_time'        => get_post_meta($post->ID, self::META_PREFIX . 'lead_time', true),
            'shipping'         => get_post_meta($post->ID, self::META_PREFIX . 'shipping', true),
            'payment_terms'    => get_post_meta($post->ID, self::META_PREFIX . 'payment_terms', true),
            'incoterms'        => get_post_meta($post->ID, self::META_PREFIX . 'incoterms', true),
            'key_benefits'     => get_post_meta($post->ID, self::META_PREFIX . 'key_benefits', true),
            'quality_control'  => get_post_meta($post->ID, self::META_PREFIX . 'quality_control', true),
            'sample_policies'  => get_post_meta($post->ID, self::META_PREFIX . 'sample_policies', true),
            'certifications'   => get_post_meta($post->ID, self::META_PREFIX . 'certifications', true),
            'factory_video'    => get_post_meta($post->ID, self::META_PREFIX . 'factory_video', true),
        ];
        
        // Parse certifications into array of IDs
        $selected_certs = $values['certifications'] ? array_filter(array_map('intval', explode(',', $values['certifications']))) : [];
        ?>
        
        <div class="awps-product-tabs">
            <ul class="awps-tabs-nav">
                <li data-tab="basic"><i class="dashicons dashicons-admin-generic"></i> <?php _e('Basic Details', 'awps'); ?></li>
                <li data-tab="specs"><i class="dashicons dashicons-analytics"></i> <?php _e('Technical Specs', 'awps'); ?></li>
                <li data-tab="business"><i class="dashicons dashicons-money"></i> <?php _e('Business Terms', 'awps'); ?></li>
                <li data-tab="quality"><i class="dashicons dashicons-awards"></i> <?php _e('Quality & Benefits', 'awps'); ?></li>
                <li data-tab="media"><i class="dashicons dashicons-format-video"></i> <?php _e('Media', 'awps'); ?></li>
            </ul>
            
            <div class="awps-tabs-content">
                <!-- Basic Details Tab -->
                <div id="tab-basic" class="awps-tab-pane">
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><label for="awps_made_in_pakistan"><?php _e('Made in Pakistan', 'awps'); ?></label></th>
                            <td>
                                <input type="checkbox" id="awps_made_in_pakistan" name="awps_made_in_pakistan" value="yes" 
                                       <?php checked($values['made_in_pakistan'], 'yes'); ?>>
                                <label for="awps_made_in_pakistan"><?php _e('This product is made in Pakistan', 'awps'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="awps_hs_code"><?php _e('HS Code', 'awps'); ?></label></th>
                            <td>
                                <input type="text" id="awps_hs_code" name="awps_hs_code" 
                                       value="<?php echo esc_attr($values['hs_code']); ?>" 
                                       class="regular-text" placeholder="e.g., 1006.30">
                                <p class="description"><?php _e('Harmonized System code for customs classification', 'awps'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="awps_moq"><?php _e('MOQ', 'awps'); ?></label></th>
                            <td>
                                <input type="text" id="awps_moq" name="awps_moq" 
                                       value="<?php echo esc_attr($values['moq']); ?>" 
                                       class="regular-text" placeholder="e.g., 1 Ton, 1000 pcs">
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Technical Specs Tab -->
                <div id="tab-specs" class="awps-tab-pane">
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><label for="awps_packaging_input"><?php _e('Packaging Options', 'awps'); ?></label></th>
                            <td>
                                <div class="awps-tag-container">
                                    <input type="text" id="awps_packaging_input" class="awps-tag-input" 
                                           placeholder="<?php _e('Start typing packaging option...', 'awps'); ?>">
                                    <div id="awps_packaging_list" class="awps-tag-list">
                                        <?php foreach (self::explode_tags($values['packaging']) as $tag): ?>
                                            <span class="tag" data-value="<?php echo esc_attr($tag); ?>">
                                                <?php echo esc_html($tag); ?> <span class="remove-tag">×</span>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                    <input type="hidden" name="awps_packaging" id="awps_packaging_hidden" 
                                           value="<?php echo esc_attr($values['packaging']); ?>">
                                </div>
                                <p class="description"><?php _e('Press Enter/Comma to add tags. Matches frontend behavior.', 'awps'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="awps_lead_time"><?php _e('Lead Time', 'awps'); ?></label></th>
                            <td>
                                <input type="text" id="awps_lead_time" name="awps_lead_time" 
                                       value="<?php echo esc_attr($values['lead_time']); ?>" 
                                       class="regular-text" placeholder="e.g., 15–20 Days After Order Confirmation">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="awps_shipping_input"><?php _e('Shipping Methods', 'awps'); ?></label></th>
                            <td>
                                <div class="awps-tag-container">
                                    <input type="text" id="awps_shipping_input" class="awps-tag-input" 
                                           placeholder="<?php _e('Start typing shipping method...', 'awps'); ?>">
                                    <div id="awps_shipping_list" class="awps-tag-list">
                                        <?php foreach (self::explode_tags($values['shipping']) as $tag): ?>
                                            <span class="tag" data-value="<?php echo esc_attr($tag); ?>">
                                                <?php echo esc_html($tag); ?> <span class="remove-tag">×</span>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                    <input type="hidden" name="awps_shipping" id="awps_shipping_hidden" 
                                           value="<?php echo esc_attr($values['shipping']); ?>">
                                </div>
                                <p class="description"><?php _e('Press Enter/Comma to add tags', 'awps'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Business Terms Tab -->
                <div id="tab-business" class="awps-tab-pane">
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><label for="awps_payment_terms_input"><?php _e('Payment Terms', 'awps'); ?></label></th>
                            <td>
                                <div class="awps-tag-container">
                                    <input type="text" id="awps_payment_terms_input" class="awps-tag-input" 
                                           placeholder="<?php _e('Start typing payment term...', 'awps'); ?>">
                                    <div id="awps_payment_terms_list" class="awps-tag-list">
                                        <?php foreach (self::explode_tags($values['payment_terms']) as $tag): ?>
                                            <span class="tag" data-value="<?php echo esc_attr($tag); ?>">
                                                <?php echo esc_html($tag); ?> <span class="remove-tag">×</span>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                    <input type="hidden" name="awps_payment_terms" id="awps_payment_terms_hidden" 
                                           value="<?php echo esc_attr($values['payment_terms']); ?>">
                                </div>
                                <p class="description"><?php _e('Press Enter/Comma to add tags', 'awps'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="awps_incoterms_input"><?php _e('Incoterms', 'awps'); ?></label></th>
                            <td>
                                <div class="awps-tag-container">
                                    <input type="text" id="awps_incoterms_input" class="awps-tag-input" 
                                        placeholder="<?php _e('Start typing incoterm code or description...', 'awps'); ?>">
                                    <div id="awps_incoterms_list" class="awps-tag-list">
                                        <?php foreach (self::explode_tags($values['incoterms']) as $code): ?>
                                            <?php $incoterms_list = get_intoterms(); ?>
                                            <span class="tag" data-value="<?php echo esc_attr($code); ?>">
                                                <?php echo esc_html($code . ' - ' . ($incoterms_list[$code] ?? $code)); ?> 
                                                <span class="remove-tag">×</span>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                    <input type="hidden" name="awps_incoterms" id="awps_incoterms_hidden" 
                                        value="<?php echo esc_attr($values['incoterms']); ?>">
                                </div>
                                <p class="description"><?php _e('Select multiple incoterms applicable to this product (e.g., FOB and CIF).', 'awps'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Quality & Benefits Tab -->
                <div id="tab-quality" class="awps-tab-pane">
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><label for="awps_key_benefits_input"><?php _e('Key Benefits', 'awps'); ?></label></th>
                            <td>
                                <div class="awps-tag-container">
                                    <input type="text" id="awps_key_benefits_input" class="awps-tag-input" 
                                           placeholder="<?php _e('Start typing benefit...', 'awps'); ?>">
                                    <div id="awps_key_benefits_list" class="awps-tag-list">
                                        <?php foreach (self::explode_tags($values['key_benefits']) as $tag): ?>
                                            <span class="tag" data-value="<?php echo esc_attr($tag); ?>">
                                                <?php echo esc_html($tag); ?> <span class="remove-tag">×</span>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                    <input type="hidden" name="awps_key_benefits" id="awps_key_benefits_hidden" 
                                           value="<?php echo esc_attr($values['key_benefits']); ?>">
                                </div>
                                <p class="description"><?php _e('Press Enter/Comma to add tags', 'awps'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="awps_quality_control_input"><?php _e('Quality Control', 'awps'); ?></label></th>
                            <td>
                                <div class="awps-tag-container">
                                    <input type="text" id="awps_quality_control_input" class="awps-tag-input" 
                                           placeholder="<?php _e('Start typing QC phrase...', 'awps'); ?>">
                                    <div id="awps_quality_control_list" class="awps-tag-list">
                                        <?php foreach (self::explode_tags($values['quality_control']) as $tag): ?>
                                            <span class="tag" data-value="<?php echo esc_attr($tag); ?>">
                                                <?php echo esc_html($tag); ?> <span class="remove-tag">×</span>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                    <input type="hidden" name="awps_quality_control" id="awps_quality_control_hidden" 
                                           value="<?php echo esc_attr($values['quality_control']); ?>">
                                </div>
                                <p class="description"><?php _e('Press Enter/Comma to add tags', 'awps'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="awps_sample_policies_input"><?php _e('Sample Policies', 'awps'); ?></label></th>
                            <td>
                                <div class="awps-tag-container">
                                    <input type="text" id="awps_sample_policies_input" class="awps-tag-input" 
                                           placeholder="<?php _e('Start typing sample policy...', 'awps'); ?>">
                                    <div id="awps_sample_policies_list" class="awps-tag-list">
                                        <?php foreach (self::explode_tags($values['sample_policies']) as $tag): ?>
                                            <span class="tag" data-value="<?php echo esc_attr($tag); ?>">
                                                <?php echo esc_html($tag); ?> <span class="remove-tag">×</span>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                    <input type="hidden" name="awps_sample_policies" id="awps_sample_policies_hidden" 
                                           value="<?php echo esc_attr($values['sample_policies']); ?>">
                                </div>
                                <p class="description"><?php _e('Press Enter/Comma to add tags', 'awps'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="awps_certifications_input"><?php _e('Certifications', 'awps'); ?></label></th>
                            <td>
                                <div class="awps-tag-container">
                                    <input type="text" id="awps_certifications_input" class="awps-tag-input" 
                                           placeholder="<?php _e('Start typing certification name...', 'awps'); ?>">
                                    <div id="awps_certifications_list" class="awps-tag-list">
                                        <?php
                                        foreach ($selected_certs as $cert_id) {
                                            $cert = get_post($cert_id);
                                            if ($cert && $cert->post_type === 'certification' && $cert->post_status === 'publish') {
                                                echo '<span class="tag" data-value="' . esc_attr($cert_id) . '">' . 
                                                     esc_html($cert->post_title) . ' <span class="remove-tag">×</span></span>';
                                            }
                                        }
                                        ?>
                                    </div>
                                    <input type="hidden" name="awps_certifications" id="awps_certifications_hidden" 
                                           value="<?php echo esc_attr($values['certifications']); ?>">
                                </div>
                                <p class="description">
                                    <?php _e('Type to search certifications. Press Enter to add.', 'awps'); ?><br>
                                    <?php if (current_user_can('publish_posts')): ?>
                                        <a href="<?php echo admin_url('edit.php?post_type=certification'); ?>" target="_blank" style="margin-right:10px;">
                                            <span class="dashicons dashicons-list-view" style="font-size:14px;vertical-align:middle;"></span> 
                                            <?php _e('Manage Certifications', 'awps'); ?>
                                        </a>
                                        <a href="<?php echo admin_url('post-new.php?post_type=certification'); ?>" target="_blank" class="button button-small">
                                            <span class="dashicons dashicons-plus-alt" style="font-size:14px;vertical-align:middle;margin-right:3px;"></span>
                                            <?php _e('Add New', 'awps'); ?>
                                        </a>
                                    <?php endif; ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Media Tab -->
                <div id="tab-media" class="awps-tab-pane">
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><label for="awps_factory_video"><?php _e('Factory Video URL', 'awps'); ?></label></th>
                            <td>
                                <input type="url" id="awps_factory_video" name="awps_factory_video" 
                                       value="<?php echo esc_url($values['factory_video']); ?>" 
                                       class="large-text" placeholder="https://youtube.com/watch?v=...">
                                <p class="description"><?php _e('YouTube URL or direct video file URL', 'awps'); ?></p>
                            </td>
                        </tr>
                        <!-- Product Gallery -->
<tr>
    <th scope="row"><label for="awps_product_gallery"><?php _e('Product Gallery', 'awps'); ?></label></th>
    <td>
        <div id="awps-product-gallery-container">
            <div class="awps-gallery-preview" style="display:flex;flex-wrap:wrap;gap:10px;margin:10px 0;">
                <?php
                $gallery_ids = get_post_meta($post->ID, self::META_PREFIX . 'gallery', true);
                $gallery_ids_array = $gallery_ids ? array_filter(array_map('intval', explode(',', $gallery_ids))) : [];
                
                foreach ($gallery_ids_array as $attachment_id) {
                    $thumb_url = wp_get_attachment_image_src($attachment_id, 'thumbnail');
                    if (!$thumb_url) continue;
                    ?>
                    <div class="awps-gallery-item" data-id="<?php echo esc_attr($attachment_id); ?>" style="position:relative;display:inline-block;width:80px;height:80px;border:2px solid #ddd;border-radius:4px;overflow:hidden;cursor:move;">
                        <img src="<?php echo esc_url($thumb_url[0]); ?>" style="width:100%;height:100%;object-fit:cover;">
                        <button type="button" class="awps-gallery-remove" data-id="<?php echo esc_attr($attachment_id); ?>" style="position:absolute;top:-8px;right:-8px;background:#d63638;color:#fff;border:2px solid #fff;border-radius:50%;width:20px;height:20px;font-size:14px;line-height:16px;cursor:pointer;">×</button>
                    </div>
                    <?php
                }
                ?>
            </div>
            
            <input type="hidden" name="awps_product_gallery" id="awps_product_gallery" 
                   value="<?php echo esc_attr($gallery_ids); ?>">
            
            <button type="button" id="awps-add-gallery-images" class="button" style="margin-top:10px;">
                <span class="dashicons dashicons-format-gallery" style="margin-right:5px;"></span>
                <?php _e('Add Gallery Images', 'awps'); ?>
            </button>
            
            <p class="description">
                <?php _e('Upload multiple images for your product gallery. Drag to reorder. Featured image is separate.', 'awps'); ?>
            </p>
        </div>
    </td>
</tr>
                    </table>
                </div>
            </div>
        </div>
        
        <style>
        .awps-product-tabs { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif; margin-top: 15px; }
        .awps-tabs-nav { display: flex; border-bottom: 1px solid #ccd0d4; margin: 0 0 15px; list-style: none; padding: 0; background: #f8f9f9; border-radius: 4px 4px 0 0; margin-bottom: 0px; }
        .awps-tabs-nav li { padding: 12px 16px; cursor: pointer; border-right: 1px solid #ddd; font-weight: 500; display: flex; align-items: center; gap: 6px; color: #555; }
        .awps-tabs-nav li:hover { background: #f0f0f0; color: #007cba; }
        .awps-tabs-nav li.active { background: #fff; border-bottom: 2px solid #007cba; position: relative; bottom: -1px; color: #007cba; font-weight: 600; }
        .awps-tabs-nav li:last-child { border-right: none; }
        .awps-tab-pane { display: none; padding: 15px 0; }
        .awps-tab-pane.active { display: block; }
        .awps-tabs-content { padding: 0; background: #fff; border: 1px solid #ccd0d4; border-top: none; border-radius: 0 0 4px 4px; }
        .awps-tabs-content .form-table th{ padding-left: 15px; }
        .awps-tag-container{ margin-top: 0px !important; }
        
        /* Tag container styles matching frontend */
        .awps-tag-input { width: 100%; padding: 8px 12px; border: 1px solid #8c8f94; border-radius: 4px; font-size: 14px; }
        .awps-tag-list { margin-top: 8px; display: flex; flex-wrap: wrap; gap: 6px; min-height: 32px; }
        .awps-tag-list .tag { 
            background: #e0e0e0; 
            border: 1px solid #a0a0a0; 
            border-radius: 3px; 
            padding: 2px 8px; 
            font-size: 13px; 
            display: flex; 
            align-items: center; 
            gap: 4px; 
            position: relative;
        }
        .awps-tag-list .tag .remove-tag { 
            cursor: pointer; 
            font-weight: bold; 
            margin-left: 4px; 
            color: #d63638; 
            position: relative; 
            top: -1px;
        }
        .awps-tag-list .tag .remove-tag:hover { color: #000; }

        .ui-autocomplete{
            padding-left: 0px;
        }
        .ui-autocomplete li{
            width: unset;
        }
        
        @media (max-width: 782px) {
            .awps-tabs-nav { flex-wrap: wrap; }
            .awps-tabs-nav li { width: 50%; box-sizing: border-box; }
        }
        </style>
        
        <script>
jQuery(document).ready(function($) {
    // Initialize ALL tag fields (DEDUPLICATED - certifications appears only once)
    const fields = [
        { id: 'packaging', options: <?php echo json_encode(get_packaging_options()); ?> },
        { id: 'shipping', options: <?php echo json_encode(get_shipping_methods()); ?> },
        { id: 'payment_terms', options: <?php echo json_encode(get_payment_terms()); ?> },
        { id: 'key_benefits', options: <?php echo json_encode(get_key_benefits_options()); ?> },
        { id: 'quality_control', options: <?php echo json_encode(get_quality_control_options()); ?> },
        { id: 'sample_policies', options: <?php echo json_encode(get_sample_policies()); ?> },
        { 
            id: 'incoterms', 
            options: (function() {
                const incoterms = <?php echo json_encode(get_intoterms()); ?>;
                return Object.keys(incoterms).map(code => ({
                    label: code + ' - ' + incoterms[code],
                    value: code
                }));
            })()
        },
        { 
            id: 'certifications',
            // PRELOADED CERTIFICATIONS (title for display, ID for storage)
            options: (function() {
                const certs = [];
                <?php
                $all_certs_js = [];
                if (post_type_exists('certification')) {
                    $cert_query = new WP_Query([
                        'post_type'      => 'certification',
                        'posts_per_page' => -1,
                        'post_status'    => 'publish',
                        'orderby'        => 'title',
                        'order'          => 'ASC',
                        'no_found_rows'  => true,
                        'fields'         => 'ids'
                    ]);
                    foreach ($cert_query->posts as $cert_id) {
                        $all_certs_js[] = [
                            'label' => get_the_title($cert_id),
                            'value' => $cert_id
                        ];
                    }
                    wp_reset_postdata();
                }
                echo 'const phpCerts = ' . json_encode($all_certs_js) . ';';
                echo 'phpCerts.forEach(cert => certs.push(cert));';
                ?>
                return certs;
            })()
        }
    ];

    fields.forEach(config => {
        const $input = $('#awps_' + config.id + '_input');
        const $list = $('#awps_' + config.id + '_list');
        const $hidden = $('#awps_' + config.id + '_hidden');
        if (!$input.length) return;

        // Helper: Check if value already exists in tag list (handles objects/strings)
        function isDuplicate(value) {
            const checkVal = typeof value === 'object' && value !== null ? 
                (value.id || value.value || value) : value;
            return $list.find(`[data-value="${String(checkVal).replace(/"/g, '\\"')}"]`).length > 0;
        }

        // Unified autocomplete handler for ALL fields
        $input.autocomplete({
            source: config.options,
            select: function(event, ui) {
                let displayText = ui.item.label || ui.item.value;
                let storedValue = ui.item.value !== undefined ? ui.item.value : ui.item;
                
                // Certifications: display title, store ID
                if (config.id === 'certifications') {
                    displayText = ui.item.label;  // "Halal Certification"
                    storedValue = ui.item.value;   // 8052
                }
                
                if (!isDuplicate(storedValue)) {
                    addTag(displayText, config.id, storedValue, $list, $hidden);
                }
                $(this).val('');
                return false;
            },
            minLength: config.id === 'certifications' ? 0 : 1
        }).focus(function() {
            // Show all certifications on focus
            if (config.id === 'certifications') {
                $(this).autocomplete("search", "");
            }
        });

        // Manual entry via Enter/Comma (non-cert fields only)
        if (config.id !== 'certifications') {
            $input.on('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ',') {
                    e.preventDefault();
                    const val = $(this).val().trim();
                    if (val && !isDuplicate(val)) {
                        addTag(val, config.id, null, $list, $hidden);
                        $(this).val('');
                    }
                }
            });
        }

        // Remove tag handler (all fields)
        $list.on('click', '.remove-tag', function() {
            $(this).closest('.tag').remove();
            updateHidden($list, $hidden);
        });
    });

    // TAB SWITCHING + Gallery Sortable Initialization
    $('.awps-tabs-nav li').on('click', function() {
        const tabId = $(this).data('tab');
        $('.awps-tabs-nav li, .awps-tab-pane').removeClass('active');
        $(this).addClass('active');
        const $pane = $('#tab-' + tabId).addClass('active');
        
        // ✅ Initialize sortable ONLY when Media tab becomes visible (fixes hidden element issue)
        if (tabId === 'media' && !$pane.data('sortable-initialized')) {
            if ($.fn.sortable) {
                $('.awps-gallery-preview').sortable({
                    items: '.awps-gallery-item',
                    cursor: 'move',
                    opacity: 0.8,
                    tolerance: 'pointer',
                    update: function() {
                        updateGalleryHiddenField();
                    }
                });
                $pane.data('sortable-initialized', true);
            }
        }
    });
    $('.awps-tabs-nav li:first').trigger('click');

    // HELPER FUNCTIONS
    function addTag(display, fieldId, storedVal, $list, $hidden) {
        const val = storedVal !== null && storedVal !== undefined ? storedVal : display;
        const safeValue = String(val).replace(/"/g, '&quot;');
        const safeDisplay = String(display).replace(/"/g, '&quot;');
        
        $list.append(
            `<span class="tag" data-value="${safeValue}">
                ${safeDisplay} 
                <span class="remove-tag">×</span>
            </span>`
        );
        updateHidden($list, $hidden);
    }

    function updateHidden($list, $hidden) {
        const vals = $list.find('.tag').map((i, el) => $(el).data('value')).get();
        $hidden.val(vals.join(','));
    }

    // PRODUCT GALLERY MANAGEMENT (sortable handled in tab switcher above)
    $('#awps-add-gallery-images').on('click', function(e) {
        e.preventDefault();
        
        // Create media frame only once
        if (typeof wp.media.frames.awps_product_gallery === 'undefined') {
            wp.media.frames.awps_product_gallery = wp.media({
                title: 'Select Gallery Images',
                button: { text: 'Add to Gallery' },
                multiple: true,
                library: { type: 'image' }
            });
        }

        // Handle selection
        wp.media.frames.awps_product_gallery.on('select', function() {
            const selection = wp.media.frames.awps_product_gallery.state().get('selection');
            const $preview = $('.awps-gallery-preview');
            const existingIds = $preview.find('.awps-gallery-item').map(function() {
                return parseInt($(this).data('id'));
            }).get();
            
            selection.each(function(attachment) {
                const id = attachment.get('id');
                if (existingIds.includes(id)) return; // Skip duplicates
                
                const thumbUrl = attachment.get('sizes').thumbnail?.url || attachment.get('url');
                $preview.append(`
                    <div class="awps-gallery-item" data-id="${id}" style="position:relative;display:inline-block;width:80px;height:80px;border:2px solid #ddd;border-radius:4px;overflow:hidden;cursor:move;">
                        <img src="${thumbUrl}" style="width:100%;height:100%;object-fit:cover;">
                        <button type="button" class="awps-gallery-remove" data-id="${id}" style="position:absolute;top:-8px;right:-8px;background:#d63638;color:#fff;border:2px solid #fff;border-radius:50%;width:20px;height:20px;font-size:14px;line-height:16px;cursor:pointer;">×</button>
                    </div>
                `);
            });
            updateGalleryHiddenField();
        });

        wp.media.frames.awps_product_gallery.open();
    });

    // Remove gallery item
    $(document).on('click', '.awps-gallery-remove', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).closest('.awps-gallery-item').remove();
        updateGalleryHiddenField();
    });

    // Update gallery hidden field
    function updateGalleryHiddenField() {
        const ids = $('.awps-gallery-item').map(function() {
            return $(this).data('id');
        }).get().filter(id => id > 0);
        $('#awps_product_gallery').val(ids.join(','));
    }
});
</script>
        <?php
    }

    public function save_product_meta($post_id, $post)
    {
        if (!isset($_POST['awps_product_tabbed_meta_nonce']) ||
            !wp_verify_nonce($_POST['awps_product_tabbed_meta_nonce'], 'awps_product_tabbed_meta')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        // Save all fields (hidden inputs contain comma-separated values)
        $fields = [
            'made_in_pakistan', 'hs_code', 'moq', 'packaging', 'lead_time',
            'shipping', 'payment_terms', 'incoterms', 'key_benefits',
            'quality_control', 'sample_policies', 'certifications', 'factory_video'
        ];

        foreach ($fields as $field) {
            $meta_key = self::META_PREFIX . $field;
            
            // ✅ SPECIAL HANDLING FOR CHECKBOX: made_in_pakistan
            if ($field === 'made_in_pakistan') {
                // Checkbox sends 'yes' when checked, nothing when unchecked
                $value = (!empty($_POST['awps_' . $field]) && $_POST['awps_' . $field] === 'yes') ? 'yes' : 'no';
                update_post_meta($post_id, $meta_key, $value);
            } 
            // ✅ SPECIAL HANDLING FOR COMMA-SEPARATED FIELDS
            elseif ($field === 'certifications') {
                $raw = $_POST['awps_' . $field] ?? '';
                $ids = array_filter(array_map('intval', explode(',', $raw)));
                $clean = implode(',', $ids);
                update_post_meta($post_id, $meta_key, $clean);
            } 
            // ✅ REGULAR TEXT/TAG FIELDS
            else {
                if (isset($_POST['awps_' . $field])) {
                    $value = is_string($_POST['awps_' . $field]) ? sanitize_text_field($_POST['awps_' . $field]) : '';
                    update_post_meta($post_id, $meta_key, $value);
                }
            }
        }

        // Save Product Gallery
        $gallery_value = '';
        if (!empty($_POST['awps_product_gallery'])) {
            $gallery_ids = array_filter(array_map('intval', explode(',', sanitize_text_field($_POST['awps_product_gallery']))));
            $gallery_value = implode(',', $gallery_ids);
        }
        update_post_meta($post_id, self::META_PREFIX . 'gallery', $gallery_value);
    }


    public function add_admin_columns($columns)
    {
        $new_columns = [];
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['supplier'] = __('Supplier', 'awps');
                $new_columns['hs_code'] = __('HS Code', 'awps');
                $new_columns['moq'] = __('MOQ', 'awps');
            }
        }
        return $new_columns;
    }

    public function render_admin_columns($column, $post_id)
    {
        switch ($column) {
            case 'supplier':
                $supplier = get_user_by('id', get_post($post_id)->post_author);
                echo $supplier ? '<a href="' . esc_url(get_edit_user_link($supplier->ID)) . '">' . esc_html($supplier->display_name) . '</a>' : '—';
                break;
            case 'hs_code':
                echo esc_html(get_post_meta($post_id, self::META_PREFIX . 'hs_code', true) ?: '—');
                break;
            case 'moq':
                echo esc_html(get_post_meta($post_id, self::META_PREFIX . 'moq', true) ?: '—');
                break;
        }
    }

    public function register_rest_fields()
    {
        $fields = [
            'made_in_pakistan', 'hs_code', 'moq', 'packaging', 'lead_time',
            'shipping', 'payment_terms', 'incoterms', 'key_benefits',
            'quality_control', 'sample_policies', 'certifications', 'factory_video'
        ];

        foreach ($fields as $field) {
            register_rest_field(self::POST_TYPE, $field, [
                'get_callback' => function($post_arr) use ($field) {
                    return get_post_meta($post_arr['id'], self::META_PREFIX . $field, true);
                },
                'update_callback' => function($value, $post) use ($field) {
                    if (!current_user_can('edit_post', $post->ID)) return;
                    update_post_meta($post->ID, self::META_PREFIX . $field, sanitize_text_field($value));
                },
                'schema' => [
                    'type' => 'string',
                    'description' => sprintf('AWPS Product %s', $field),
                    'context' => ['view', 'edit'],
                ],
            ]);
        }
    }

    public function show_supplier_product_count($user)
    {
        if (!in_array('supplier', $user->roles)) return;
        
        $products = self::get_supplier_products($user->ID);
        $count = $products->found_posts;
        ?>
        <table class="form-table">
            <tr>
                <th><strong><?php _e('Product Statistics', 'awps'); ?></strong></th>
                <td>
                    <p><strong><?php echo esc_html($count); ?></strong> <?php _e('products published', 'awps'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }


    public function enqueue_admin_scripts($hook) {
        global $post_type, $pagenow;
        
        // ONLY load on product edit screen
        if ($post_type !== self::POST_TYPE || $pagenow !== 'post.php') {
            return;
        }

// WordPress core already includes jQuery UI Sortable - just enqueue it
        wp_enqueue_script('jquery-ui-sortable');
        
        
        
        // Localize ALL autocomplete data (matches your frontend)
        wp_localize_script('awps-product-tags-admin', 'awpsTagData', [
            'packaging' => get_packaging_options(),
            'shipping' => get_shipping_methods(),
            'payment_terms' => get_payment_terms(),
            'key_benefits' => get_key_benefits_options(),
            'quality_control' => get_quality_control_options(),
            'sample_policies' => get_sample_policies(),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('awps-cert-search'),
            'is_admin' => true, // Flag for JS to adjust behavior if needed
            'cert_search_endpoint' => admin_url('admin-ajax.php?action=awps_search_certifications')
        ]);
    }



    public function add_body_class($classes)
    {
        if (is_singular(self::POST_TYPE)) {
            $classes[] = 'awps-product-page';
        }
        return $classes;
    }

    public static function get_meta($post_id, $key, $single = true)
    {
        return get_post_meta($post_id, self::META_PREFIX . $key, $single);
    }

    public static function get_all_meta($post_id)
    {
        $meta = [];
        $keys = [
            'made_in_pakistan', 'hs_code', 'moq', 'packaging', 'lead_time',
            'shipping', 'payment_terms', 'incoterms', 'key_benefits',
            'quality_control', 'sample_policies', 'certifications', 'factory_video'
        ];
        
        foreach ($keys as $key) {
            $meta[$key] = self::get_meta($post_id, $key);
        }
        return $meta;
    }

    public static function get_supplier_products($supplier_id, $args = [])
    {
        $defaults = [
            'post_type' => self::POST_TYPE,
            'posts_per_page' => -1,
            'author' => $supplier_id,
            'post_status' => ['publish', 'draft'],
        ];
        return new WP_Query(wp_parse_args($args, $defaults));
    }

    public static function get_all_products($args = [])
    {
        $defaults = [
            'post_type' => self::POST_TYPE,
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ];
        return new WP_Query(wp_parse_args($args, $defaults));
    }

    public static function explode_tags($str)
    {
        return $str ? array_filter(array_map('trim', explode(',', $str))) : [];
    }

    public static function get_supplier_data($post_id)
    {
        $post = get_post($post_id);
        if (!$post) return null;
        
        $supplier = get_user_by('id', $post->post_author);
        if (!$supplier) return null;
        
        return [
            'id' => $supplier->ID,
            'name' => get_user_meta($supplier->ID, 'company_name', true) ?: $supplier->display_name,
            'logo' => get_user_meta($supplier->ID, 'company_logo', true),
            'city' => get_user_meta($supplier->ID, 'city', true),
            'state' => get_user_meta($supplier->ID, 'state', true),
            'whatsapp' => get_user_meta($supplier->ID, 'whatsapp', true),
            'verified' => (bool) get_user_meta($supplier->ID, 'verified_supplier', true),
            'profile_url' => home_url('/supplier/' . sanitize_title(get_user_meta($supplier->ID, 'company_name', true) ?: $supplier->display_name)),
        ];
    }

    public static function get_certifications($post_id)
    {
        $cert_ids_str = self::get_meta($post_id, 'certifications');
        if (!$cert_ids_str) return [];
        
        $cert_ids = array_filter(array_map('intval', explode(',', $cert_ids_str)));
        $cert_titles = [];
        
        foreach ($cert_ids as $id) {
            $cert = get_post($id);
            if ($cert && $cert->post_type === 'certification' && $cert->post_status === 'publish') {
                $cert_titles[] = $cert->post_title;
            }
        }
        
        return $cert_titles;
    }

    /**
     * Get gallery image IDs from product meta
     * 
     * @param int $post_id Product ID
     * @return array Array of attachment IDs
     */
    public static function get_gallery_ids($post_id) {
        $gallery_ids = get_post_meta($post_id, self::META_PREFIX . 'gallery', true);
        if (!$gallery_ids) return [];
        
        return array_filter(array_map('intval', explode(',', $gallery_ids)));
    }

    /**
     * Get all product images (featured + gallery) with full metadata
     * 
     * @param int $post_id Product ID
     * @param string $size Image size for thumbnails (default: 'thumbnail')
     * @return array Array of image data [id, url, alt, srcset, full_url, is_featured]
     */
    public static function get_all_product_images($post_id, $size = 'thumbnail') {
        $images = [];
        
        // Featured image first
        $featured_id = get_post_thumbnail_id($post_id);
        if ($featured_id) {
            $images[] = [
                'id' => $featured_id,
                'url' => get_the_post_thumbnail_url($post_id, $size),
                'alt' => get_post_meta($featured_id, '_wp_attachment_image_alt', true),
                'srcset' => wp_get_attachment_image_srcset($featured_id, $size),
                'full_url' => get_the_post_thumbnail_url($post_id, 'full'),
                'is_featured' => true
            ];
        }
        
        // Gallery images
        $gallery_ids = self::get_gallery_ids($post_id);
        foreach ($gallery_ids as $id) {
            $url = wp_get_attachment_image_url($id, $size);
            if (!$url) continue;
            
            $images[] = [
                'id' => $id,
                'url' => $url,
                'alt' => get_post_meta($id, '_wp_attachment_image_alt', true),
                'srcset' => wp_get_attachment_image_srcset($id, $size),
                'full_url' => wp_get_attachment_image_url($id, 'full'),
                'is_featured' => false
            ];
        }
        
        return $images;
    }
}