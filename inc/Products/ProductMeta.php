<?php
namespace AWPS\Products;

class ProductMeta {
    public function register() {
        // Admin fields
        add_action('woocommerce_product_options_general_product_data', [$this, 'add_custom_fields']);
        add_action('woocommerce_admin_process_product_object', [$this, 'save_custom_fields'], 10, 1);

        // Enqueue scripts/styles only on product edit pages
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function enqueue_scripts($hook) {
        global $post;
        if ($hook !== 'post.php' && $hook !== 'post-new.php') return;
        if (!$post || $post->post_type !== 'product') return;

        // Ensure jQuery & jQuery UI Autocomplete are loaded
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-autocomplete');

        // Add custom JS for tag widgets
        add_action('admin_footer', [$this, 'custom_fields_js']);
    }

    public function custom_fields_js() {
        // Get data for autocomplete sources
        $packaging_options = get_packaging_options();
        $shipping_methods = get_shipping_methods();
        $payment_terms = get_payment_terms();
        $incoterms = get_intoterms();
        $sample_policies = get_sample_policies();
        $key_benefits = get_key_benefits_options();
        $quality_control_phrases = get_quality_control_options();

        // Format for autocomplete: {value: string, label: string}
        $packaging_json = json_encode(array_map(function($item) {
            return ['value' => $item, 'label' => $item];
        }, $packaging_options), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

        $shipping_json = json_encode(array_map(function($item) {
            return ['value' => $item, 'label' => $item];
        }, $shipping_methods), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

        $payment_json = json_encode(array_map(function($item) {
            return ['value' => $item, 'label' => $item];
        }, $payment_terms), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

        $sample_json = json_encode(array_map(function($item) {
            return ['value' => $item, 'label' => $item];
        }, $sample_policies), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

        $benefits_json = json_encode(array_map(function($item) {
            return ['value' => $item, 'label' => $item];
        }, $key_benefits), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

        $quality_json = json_encode(array_map(function($item) {
            return ['value' => $item, 'label' => $item];
        }, $quality_control_phrases), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

        // For Incoterms: value = code (e.g., "FOB"), label = "FOB — Free On Board"
        $incoterms_json = json_encode(array_map(function($code, $desc) {
            return ['value' => $code, 'label' => "$code — $desc"];
        }, array_keys($incoterms), $incoterms), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Generic Tag Widget (adapted from ProfileFields)
            function setupTagWidget(opts) {
                const $input = $(opts.inputSelector);
                const $list = $(opts.listSelector);
                const $hidden = $(opts.hiddenSelector);
                if ($input.length === 0 || $list.length === 0 || $hidden.length === 0) return;

                function getCurrentValues() {
                    return $list.find('.tag').toArray().map(function(el) {
                        return $(el).attr('data-value') ? $(el).attr('data-value').toString() : $(el).clone().children().remove().end().text().trim();
                    });
                }

                function updateHidden() {
                    const vals = getCurrentValues();
                    $hidden.val(vals.join(','));
                }

                function addTag(value, displayText = null) {
                    value = String(value || '').trim();
                    if (!value) return;
                    const current = getCurrentValues();
                    if (current.indexOf(value) !== -1) return;

                    // For Incoterms, show only code in tag — full label only in dropdown
                    const display = (opts.inputSelector === '#_incoterms_input') ? value : (displayText || value);
                    const $tag = $('<span/>', { class: 'tag', 'data-value': value }).html(display + ' <span class="remove-tag">×</span>');
                    $tag.find('.remove-tag').on('click', function(e) {
                        e.preventDefault();
                        $tag.remove();
                        updateHidden();
                    });
                    $list.append($tag);
                    updateHidden();
                }

                // Initialize from hidden field
                const raw = ($hidden.val() || '').toString();
                const arr = raw.split(',').map(function(t) { return t.trim(); }).filter(function(t) { return t.length > 0; });
                $list.empty();
                $hidden.val('');
                arr.forEach(function(v) {
                    let display = v;
                    const $existing = $list.find(`.tag[data-value="${v}"]`);
                    if ($existing.length) {
                        display = $existing.clone().children().remove().end().text().replace(' ×', '').trim();
                    }
                    addTag(v, display);
                });

                // Allow manual entry with Enter/Comma (even with autocomplete)
                $input.on('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ',') {
                        e.preventDefault();
                        const v = $input.val().trim();
                        if (v) addTag(v);
                        $input.val('');
                    }
                });

                // Remove tag on click
                $list.on('click', '.remove-tag', function() {
                    $(this).parent().remove();
                    updateHidden();
                });

                // Setup autocomplete if specified
                if (opts.useAutocomplete && $.ui && $.ui.autocomplete) {
                    if (opts.sourceType === 'ajax' && opts.ajaxAction) {
                        $input.autocomplete({
                            source: function(request, response) {
                                $.ajax({
                                    url: '<?= esc_js(admin_url("admin-ajax.php")); ?>',
                                    dataType: 'json',
                                    data: {  // ← FIXED: was missing "data:" key
                                        action: opts.ajaxAction,
                                        term: request.term
                                    },
                                    success: function(data) { response(data || []); },
                                    error: function() { response([]); }
                                });
                            },
                            focus: function() { return false; },
                            select: function(event, ui) {
                                const val = ui.item.value;
                                const label = ui.item.label || val;
                                addTag(val, label);
                                $input.val('');
                                return false;
                            }
                        });
                    } else if (opts.sourceType === 'array' && Array.isArray(opts.sourceArray)) {
                        $input.autocomplete({
                            source: function(request, response) {
                                const term = (request.term || '').toLowerCase();
                                const results = opts.sourceArray.filter(function(item) {
                                    return (item.label || item.value || '').toLowerCase().indexOf(term) !== -1;
                                });
                                response(results);
                            },
                            focus: function() { return false; },
                            select: function(event, ui) {
                                const val = ui.item.value;
                                const label = ui.item.label || val;
                                addTag(val, label);
                                $input.val('');
                                return false;
                            }
                        });
                    }
                }
            }

            // Initialize all tag widgets

            // Certifications — AJAX from CPT
            setupTagWidget({
                inputSelector: '#_certifications_input',
                listSelector: '#_certifications_list',
                hiddenSelector: "input[name='_certifications']",
                useAutocomplete: true,
                sourceType: 'ajax',
                ajaxAction: 'get_certifications'
            });

            // Incoterms — Array from PHP function (store code only)
            const incotermsList = <?= $incoterms_json ?>;
            setupTagWidget({
                inputSelector: '#_incoterms_input',
                listSelector: '#_incoterms_list',
                hiddenSelector: "input[name='_incoterms']",
                useAutocomplete: true,
                sourceType: 'array',
                sourceArray: incotermsList
            });

            // Packaging — Array from PHP function
            const packagingList = <?= $packaging_json ?>;
            setupTagWidget({
                inputSelector: '#_packaging_details_input',
                listSelector: '#_packaging_details_list',
                hiddenSelector: "input[name='_packaging_details']",
                useAutocomplete: true,
                sourceType: 'array',
                sourceArray: packagingList
            });

            // Shipping Methods — Array from PHP function
            const shippingList = <?= $shipping_json ?>;
            setupTagWidget({
                inputSelector: '#_shipping_options_input',
                listSelector: '#_shipping_options_list',
                hiddenSelector: "input[name='_shipping_options']",
                useAutocomplete: true,
                sourceType: 'array',
                sourceArray: shippingList
            });

            // Payment Terms — Array from PHP function
            const paymentList = <?= $payment_json ?>;
            setupTagWidget({
                inputSelector: '#_payment_terms_input',
                listSelector: '#_payment_terms_list',
                hiddenSelector: "input[name='_payment_terms']",
                useAutocomplete: true,
                sourceType: 'array',
                sourceArray: paymentList
            });

            // Sample Policies — Array from PHP function
            const sampleList = <?= $sample_json ?>;
            setupTagWidget({
                inputSelector: '#_sample_policies_input',
                listSelector: '#_sample_policies_list',
                hiddenSelector: "input[name='_sample_policies']",
                useAutocomplete: true,
                sourceType: 'array',
                sourceArray: sampleList
            });

            // Key Benefits — Array from PHP function + manual entry
            const benefitsList = <?= $benefits_json ?>;
            setupTagWidget({
                inputSelector: '#_key_benefits_input',
                listSelector: '#_key_benefits_list',
                hiddenSelector: "input[name='_key_benefits']",
                useAutocomplete: true,
                sourceType: 'array',
                sourceArray: benefitsList
            });

            // Quality Control — Array from PHP function + manual entry
            const qualityList = <?= $quality_json ?>;
            setupTagWidget({
                inputSelector: '#_quality_control_input',
                listSelector: '#_quality_control_list',
                hiddenSelector: "input[name='_quality_control']",
                useAutocomplete: true,
                sourceType: 'array',
                sourceArray: qualityList
            });
        });
        </script>
        <style>
        .awps-tag-container { margin: 15px 0; padding: 10px; background: #fafafa; border: 1px solid #eee; border-radius: 6px; }
        .awps-tag-container label { display: block; margin-bottom: 8px; font-weight: 600; }
        .awps-tag-input { width: 100%; padding: 8px 12px; margin-bottom: 8px; box-sizing: border-box; border: 1px solid #ddd; border-radius: 4px; }
        .awps-tag-list { min-height: 40px; border: 1px solid #ddd; padding: 8px; border-radius: 4px; background: #fff; margin-bottom: 5px; }
        .awps-tag-list .tag { display: inline-block; background: #e8f0fe; color: #1a73e8; padding: 5px 12px; margin: 4px 4px 4px 0; border-radius: 16px; font-size: 13px; line-height: 1.4; }
        .awps-tag-list .tag .remove-tag { color: #d93025; cursor: pointer; margin-left: 8px; font-weight: bold; }
        .ui-autocomplete { max-width: 400px !important; width: auto !important; background: #fff !important; border: 1px solid #ddd !important; border-radius: 4px !important; box-shadow: 0 2px 8px rgba(0,0,0,0.1) !important; z-index: 9999 !important; }
        .ui-menu .ui-menu-item { padding: 6px 12px; font-size: 14px; }
        .ui-menu .ui-menu-item:hover { background: #f0f0f0; }
        .ui-helper-hidden-accessible { display: none !important; }
        </style>
        <?php
    }


    /**
     * Add custom export/product fields in WooCommerce product edit screen
     */
    public function add_custom_fields() {
        global $post;

        echo '<div class="options_group" id="awps-export-details">';
        echo '<h3>🇵🇰 AWPS Export & Product Details</h3>';

        // Made in Pakistan Toggle
        woocommerce_wp_checkbox([
            'id'            => '_made_in_pakistan',
            'label'         => '🏷️ Made in Pakistan',
            'description'   => 'Display "Made in Pakistan" origin badge. Uncheck for re-exports.',
            'desc_tip'      => true,
            'cbvalue'       => 'yes',
            'value'         => get_post_meta($post->ID, '_made_in_pakistan', true) !== 'no' ? 'yes' : 'no',
        ]);

        // HS Code
        woocommerce_wp_text_input([
            'id'          => '_hs_code',
            'label'       => '🔢 HS Code',
            'desc_tip'    => true,
            'description' => 'Harmonized System Code for customs and tariffs.',
            'placeholder' => 'e.g., 1006.30',
            'value'       => get_post_meta($post->ID, '_hs_code', true),
        ]);

        // MOQ
        woocommerce_wp_text_input([
            'id'          => '_moq',
            'label'       => '📦 Minimum Order Quantity (MOQ)',
            'desc_tip'    => true,
            'description' => 'e.g., 1 Ton, 500 Units',
            'placeholder' => 'e.g., 1 Ton',
            'value'       => get_post_meta($post->ID, '_moq', true),
        ]);

        // Packaging Details — AUTOCOMPLETE
        $packaging_value = get_post_meta($post->ID, '_packaging_details', true);
        echo '<p class="form-field awps-tag-container">';
        echo '<label for="_packaging_details">🧷 Packaging Options</label>';
        echo '<input type="text" id="_packaging_details_input" class="awps-tag-input" placeholder="Start typing packaging option...">';
        echo '<div id="_packaging_details_list" class="awps-tag-list"></div>';
        echo '<input type="hidden" name="_packaging_details" value="' . esc_attr($packaging_value) . '">';
        echo '<span class="description">Select from standard packaging options.</span>';
        echo '</p>';

        // Lead Time
        woocommerce_wp_text_input([
            'id'          => '_lead_time',
            'label'       => '⏱️ Lead Time',
            'desc_tip'    => true,
            'description' => 'Production + preparation time after order confirmation.',
            'placeholder' => 'e.g., 15–20 Days After Order Confirmation',
            'value'       => get_post_meta($post->ID, '_lead_time', true),
        ]);

        // Shipping Options — AUTOCOMPLETE
        $shipping_value = get_post_meta($post->ID, '_shipping_options', true);
        echo '<p class="form-field awps-tag-container">';
        echo '<label for="_shipping_options">🚢 Shipping Methods</label>';
        echo '<input type="text" id="_shipping_options_input" class="awps-tag-input" placeholder="Start typing shipping method...">';
        echo '<div id="_shipping_options_list" class="awps-tag-list"></div>';
        echo '<input type="hidden" name="_shipping_options" value="' . esc_attr($shipping_value) . '">';
        echo '<span class="description">e.g., FCL, LCL, Express Air Cargo.</span>';
        echo '</p>';

        // Payment Terms — AUTOCOMPLETE
        $payment_value = get_post_meta($post->ID, '_payment_terms', true);
        echo '<p class="form-field awps-tag-container">';
        echo '<label for="_payment_terms">💵 Payment Terms</label>';
        echo '<input type="text" id="_payment_terms_input" class="awps-tag-input" placeholder="Start typing payment term...">';
        echo '<div id="_payment_terms_list" class="awps-tag-list"></div>';
        echo '<input type="hidden" name="_payment_terms" value="' . esc_attr($payment_value) . '">';
        echo '<span class="description">e.g., TT, LC at Sight, Open Account 30 Days.</span>';
        echo '</p>';

        // Incoterms — AUTOCOMPLETE (store code only)
        $incoterms_value = get_post_meta($post->ID, '_incoterms', true);
        echo '<p class="form-field awps-tag-container">';
        echo '<label for="_incoterms">🧭 Incoterms</label>';
        echo '<input type="text" id="_incoterms_input" class="awps-tag-input" placeholder="Start typing incoterm code or description...">';
        echo '<div id="_incoterms_list" class="awps-tag-list"></div>';
        echo '<input type="hidden" name="_incoterms" value="' . esc_attr($incoterms_value) . '">';
        echo '<span class="description">e.g., FOB, CIF, DDP — stores code only. Full description used for tooltip later.</span>';
        echo '</p>';

        // Key Benefits — AUTOCOMPLETE + MANUAL
        $benefits_value = get_post_meta($post->ID, '_key_benefits', true);
        echo '<p class="form-field awps-tag-container">';
        echo '<label for="_key_benefits">✅ Key Benefits</label>';
        echo '<input type="text" id="_key_benefits_input" class="awps-tag-input" placeholder="Start typing benefit or press Enter/Comma">';
        echo '<div id="_key_benefits_list" class="awps-tag-list"></div>';
        echo '<input type="hidden" name="_key_benefits" value="' . esc_attr($benefits_value) . '">';
        echo '<span class="description">Press Enter or Comma to add custom benefits. Common ones auto-suggested.</span>';
        echo '</p>';

        // Quality Control — AUTOCOMPLETE + MANUAL
        $quality_value = get_post_meta($post->ID, '_quality_control', true);
        echo '<p class="form-field awps-tag-container">';
        echo '<label for="_quality_control">🧪 Quality Control & Sample Info</label>';
        echo '<input type="text" id="_quality_control_input" class="awps-tag-input" placeholder="Start typing QC phrase or press Enter/Comma">';
        echo '<div id="_quality_control_list" class="awps-tag-list"></div>';
        echo '<input type="hidden" name="_quality_control" value="' . esc_attr($quality_value) . '">';
        echo '<span class="description">Includes sample policies. Press Enter or Comma to add custom phrases.</span>';
        echo '</p>';

        // Sample Policies — AUTOCOMPLETE
        $sample_value = get_post_meta($post->ID, '_sample_policies', true);
        echo '<p class="form-field awps-tag-container">';
        echo '<label for="_sample_policies">📦 Sample Policies</label>';
        echo '<input type="text" id="_sample_policies_input" class="awps-tag-input" placeholder="Start typing sample policy...">';
        echo '<div id="_sample_policies_list" class="awps-tag-list"></div>';
        echo '<input type="hidden" name="_sample_policies" value="' . esc_attr($sample_value) . '">';
        echo '<span class="description">Standardized sample availability terms.</span>';
        echo '</p>';

        // Certifications — AUTOCOMPLETE (AJAX)
        $certifications_value = get_post_meta($post->ID, '_certifications', true);
        echo '<p class="form-field awps-tag-container">';
        echo '<label for="_certifications">🏅 Certifications</label>';
        echo '<input type="text" id="_certifications_input" class="awps-tag-input" placeholder="Start typing certification name...">';
        echo '<div id="_certifications_list" class="awps-tag-list"></div>';
        echo '<input type="hidden" name="_certifications" value="' . esc_attr($certifications_value) . '">';
        echo '<span class="description">Select from verified certifications. Press Enter or select to add.</span>';
        echo '</p>';

        // Factory/Processing Video
        woocommerce_wp_text_input([
            'id'          => '_factory_video_url',
            'label'       => '🎥 Factory/Processing Video (Optional)',
            'desc_tip'    => true,
            'description' => 'YouTube, Vimeo, or direct video URL.',
            'placeholder' => 'https://youtube.com/watch?v=...',
            'type'        => 'url',
            'value'       => get_post_meta($post->ID, '_factory_video_url', true),
        ]);

        echo '</div>';
    }

    /**
     * Save product meta fields using WooCommerce CRUD (modern approach)
     */
    public function save_custom_fields($product) {
        // Checkbox fields
        $product->update_meta_data('_made_in_pakistan', isset($_POST['_made_in_pakistan']) ? 'yes' : 'no');

        // Text fields (single value)
        $single_text_fields = [
            '_hs_code',
            '_moq',
            '_lead_time',
            '_factory_video_url',
        ];

        foreach ($single_text_fields as $field) {
            if (isset($_POST[$field])) {
                $value = ($_POST[$field] !== '') ? sanitize_text_field($_POST[$field]) : '';
                $product->update_meta_data($field, $value);
            }
        }

        // Tag-based fields (comma-separated strings)
        $tag_fields = [
            '_packaging_details',
            '_shipping_options',
            '_payment_terms',
            '_incoterms',
            '_key_benefits',
            '_quality_control',
            '_sample_policies',
            '_certifications',
        ];

        foreach ($tag_fields as $field) {
            if (isset($_POST[$field])) {
                $values = array_filter(array_map('trim', explode(',', $_POST[$field])));
                $product->update_meta_data($field, implode(',', array_values($values)));
            }
        }

        // Save all at once
        $product->save_meta_data();
    }
}