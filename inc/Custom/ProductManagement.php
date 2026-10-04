<?php

namespace Awps\Custom;

/**
 * Handles product-related logic including:
 * - Query modifications for disabled exporters
 * - AJAX actions for frontend product management
 * - Product data initialization
 */
class ProductManagement
{
    /**
     * Register all hooks and actions.
     *
     * @return void
     */
    public function register()
    {
        // Exclude products from disabled companies in main query
        add_action('pre_get_posts', [$this, 'exclude_products_from_disabled_companies']);

        // AJAX: Toggle product visibility (publish/draft)
        add_action('wp_ajax_toggle_product_visibility', [$this, 'toggle_product_visibility']);

        // AJAX: Search product categories for frontend forms
        add_action('wp_ajax_awps_search_product_categories', [$this, 'search_product_categories']);
        add_action('wp_ajax_nopriv_awps_search_product_categories', [$this, 'search_product_categories']);

        add_action('init', [$this, 'enable_product_author_support']);
    }

    /**
     * Enable product author support.
     */
    public function enable_product_author_support() {
        add_post_type_support('product', 'author');
    }
    
    /**
     * Exclude products belonging to disabled exporter accounts
     * from frontend product queries.
     *
     * @param \WP_Query $query
     * @return void
     */
    public function exclude_products_from_disabled_companies($query)
    {
        if (!is_admin() && $query->is_main_query() && $query->get('post_type') === 'product') {
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
                $meta_query = $query->get('meta_query', []);
                $meta_query[] = [
                    'key'     => '_exporter_user_id',
                    'value'   => $disabled_users,
                    'compare' => 'NOT IN'
                ];
                $query->set('meta_query', $meta_query);
            }
        }
    }

    /**
     * AJAX handler: Toggle product visibility (publish ↔ draft)
     */
    public function toggle_product_visibility()
    {
        // Verify nonce
        if (!isset($_POST['visibility_nonce']) || !wp_verify_nonce($_POST['visibility_nonce'], 'toggle_visibility')) {
            wp_send_json_error(['message' => __('Security check failed. Please reload the page.', 'awps')]);
        }

        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        
        if (!$product_id) {
            wp_send_json_error(['message' => __('Invalid product ID.', 'awps')]);
        }

        $user_id = get_current_user_id();

        if (!$user_id) {
            wp_send_json_error(['message' => __('You must be logged in to perform this action.', 'awps')]);
        }

        // Security: ensure current user owns the product
        if (get_post_field('post_author', $product_id) != $user_id) {
            wp_send_json_error(['message' => __('You do not have permission to modify this product.', 'awps')]);
        }

        $current_status = get_post_status($product_id);
        
        if (!$current_status) {
            wp_send_json_error(['message' => __('Product not found.', 'awps')]);
        }

        $new_status = ($current_status === 'publish') ? 'draft' : 'publish';

        $result = wp_update_post([
            'ID'          => $product_id,
            'post_status' => $new_status
        ]);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => __('Failed to update product status.', 'awps')]);
        }

        /**
         * Fires after a supplier toggles a product's visibility from the dashboard.
         *
         * @param int    $product_id The product post ID.
         * @param string $new_status The new post status ('publish' or 'draft').
         * @param string $current_status The previous post status.
         * @param int    $user_id    The user who performed the toggle.
         */
        do_action('awps_product_visibility_changed', $product_id, $new_status, $current_status, $user_id);

        wp_send_json_success([
            'status' => $new_status,
            'message' => sprintf(
                __('Product %s successfully.', 'awps'),
                $new_status === 'publish' ? __('published', 'awps') : __('hidden', 'awps')
            )
        ]);
    }

    /**
     * AJAX handler: Search WooCommerce product categories
     * for use in frontend product submission forms.
     */
    public function search_product_categories()
    {
        // Verify nonce for security
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'search_product_categories')) {
            wp_send_json_error(['message' => __('Security check failed.', 'awps')]);
        }

        $term = isset($_GET['term']) ? sanitize_text_field($_GET['term']) : '';
        
        if (empty($term) || strlen($term) < 2) {
            wp_send_json_success([]);
        }

        $terms = get_terms([
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'search'     => $term,
            'number'     => 10,
        ]);

        if (is_wp_error($terms)) {
            wp_send_json_error(['message' => __('Failed to search categories.', 'awps')]);
        }

        $results = [];
        foreach ($terms as $term_obj) {
            $results[] = [
                'label' => esc_html($term_obj->name),
                'value' => (string) $term_obj->term_id
            ];
        }

        wp_send_json_success($results);
    }

    
}