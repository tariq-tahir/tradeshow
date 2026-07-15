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
        if (!wp_verify_nonce($_POST['visibility_nonce'] ?? '', 'toggle_visibility')) {
            wp_send_json_error();
        }

        $product_id = intval($_POST['product_id']);
        $user_id = get_current_user_id();

        // Security: ensure current user owns the product
        if (get_post_field('post_author', $product_id) != $user_id) {
            wp_send_json_error();
        }

        $current_status = get_post_status($product_id);
        $new_status = ($current_status === 'publish') ? 'draft' : 'publish';

        wp_update_post([
            'ID'          => $product_id,
            'post_status' => $new_status
        ]);

        wp_send_json_success(['status' => $new_status]);
    }

    /**
     * AJAX handler: Search WooCommerce product categories
     * for use in frontend product submission forms.
     */
    public function search_product_categories()
    {
        $term = sanitize_text_field($_GET['term'] ?? '');
        if (strlen($term) < 2) {
            wp_send_json([]);
        }

        $terms = get_terms( array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'search'     => $term,
            'number'     => 10,
        ) );

        $results = [];
        foreach ($terms as $term_obj) {
            $results[] = [
                'label' => $term_obj->name,
                'value' => (string) $term_obj->term_id
            ];
        }

        wp_send_json($results);
    }

    
}