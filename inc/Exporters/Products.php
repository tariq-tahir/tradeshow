<?php
namespace AWPS\Exporters;

class Products {
    public function register() {
        add_action('init', [$this, 'handle_form']);
    }

    public function handle_form() {
        // Verify nonce and user role
        if (empty($_POST['awps_product_nonce']) || !wp_verify_nonce($_POST['awps_product_nonce'], 'awps_product')) {
            return;
        }

        $user = wp_get_current_user();
        if (!in_array('exporter', (array) $user->roles)) {
            return;
        }

        // Get product ID and verify ownership
        $product_id = (int) ($_POST['product_id'] ?? 0);
        if ($product_id && get_post_field('post_author', $product_id) != $user->ID) {
            wp_die('You do not have permission to edit this product.');
        }

        // Determine product status
        $status = isset($_POST['_product_visibility']) ? 'publish' : 'draft';

        // Prepare post data
        $post_data = [
            'post_type'    => 'product',
            'post_status'  => $status,
            'post_title'   => sanitize_text_field($_POST['title'] ?? ''),
            'post_content' => wp_kses_post($_POST['description'] ?? ''),
            'post_author'   => $user->ID,
        ];

        if ($product_id) {
            $post_data['ID'] = $product_id;
            $pid = wp_update_post($post_data);
        } else {
            $pid = wp_insert_post($post_data);
        }

        if (!$pid || is_wp_error($pid)) {
            wp_die('Error saving product.');
        }

        // Get or create WC product
        $product = wc_get_product($pid);
        if (!$product) {
            $product = new \WC_Product_Simple($pid);
        }

        // Set WooCommerce product data
        $product->set_regular_price(sanitize_text_field($_POST['price'] ?? ''));
        $product->set_sku(sanitize_text_field($_POST['sku'] ?? ''));
        $product->set_stock_status(isset($_POST['_stock_status']) ? 'instock' : 'outofstock');
        $product->set_catalog_visibility('visible');
        $product->save();

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
            if (isset($_POST[$field])) {
                if ($field === '_made_in_pakistan') {
                    update_post_meta($pid, $field, $_POST[$field] === 'yes' ? 'yes' : 'no');
                } else {
                    update_post_meta($pid, $field, sanitize_textarea_field($_POST[$field]));
                }
            }
        }

        // Save categories
        if (isset($_POST['product_categories']) && !empty($_POST['product_categories'])) {
            $category_ids = array_filter(array_map('intval', explode(',', $_POST['product_categories'])));
            wp_set_object_terms($pid, $category_ids, 'product_cat');
        } else {
            wp_delete_object_term_relationships($pid, 'product_cat');
        }

        // Save Yoast SEO fields
        if (class_exists('WPSEO_Meta')) {
            if (isset($_POST['_yoast_wpseo_title'])) {
                \WPSEO_Meta::set_value('title', sanitize_text_field($_POST['_yoast_wpseo_title']), $pid);
            }
            if (isset($_POST['_yoast_wpseo_metadesc'])) {
                \WPSEO_Meta::set_value('metadesc', sanitize_textarea_field($_POST['_yoast_wpseo_metadesc']), $pid);
            }
        }

        // Handle featured image
        if (isset($_POST['featured_image_id']) && $_POST['featured_image_id'] > 0) {
            set_post_thumbnail($pid, intval($_POST['featured_image_id']));
        } elseif (isset($_POST['remove_featured_image'])) {
            delete_post_thumbnail($pid);
        }

        // Handle gallery images
        if (isset($_POST['gallery_image_ids'])) {
            $gallery_ids = array_filter(array_map('intval', explode(',', $_POST['gallery_image_ids'])));
            update_post_meta($pid, '_product_image_gallery', implode(',', $gallery_ids));
        }

        // Redirect to products list with success message
        wp_safe_redirect(add_query_arg(['tab' => 'products', 'saved' => '1'], wp_get_referer()));
        exit;
    }
}