<?php

namespace Awps\Custom;

/**
 * Handles small global site tweaks and UI enhancements.
 */
class SiteTweaks
{
    /**
     * Register all hooks and actions.
     *
     * @return void
     */
    public function register()
    {
        // Set custom excerpt length
        add_filter('excerpt_length', [$this, 'set_excerpt_length']);

        // Show "Password changed" notice on My Account page
        add_action('woocommerce_before_customer_login_form', [$this, 'show_password_changed_notice']);

        // Hide admin toolbar for Supplier role users
        add_filter('show_admin_bar', [$this, 'hide_admin_bar_for_suppliers'], 999);

        // Exclude certain content types/pages from front-end search results
        add_action('pre_get_posts', [$this, 'filter_search_results']);
    }


    /**
     * Set excerpt length to 22 words.
     *
     * @return int
     */
    public function set_excerpt_length()
    {
        return 22;
    }

    /**
     * Display success message when password is changed.
     */
    public function show_password_changed_notice()
    {
        if (isset($_GET['password_changed']) && $_GET['password_changed'] === '1') {
            echo '<div class="woocommerce-message" style="margin-bottom: 20px;">'
                . '✅ Your password has been updated successfully. Please log in again.'
                . '</div>';
        }
    }

    /**
     * Hide admin toolbar for users with 'supplier' role.
     * 
     * @param bool $show Current admin bar visibility state.
     * @return bool Modified visibility state.
     */
    public function hide_admin_bar_for_suppliers($show)
    {
        $user = wp_get_current_user();
        
        // Hide admin bar for users with 'supplier' role
        if (in_array('supplier', (array) $user->roles, true)) {
            return false;
        }
        
        return $show;
    }

    /**
     * Exclude specific post types and pages from front-end search results.
     *
     * - Excludes the 'certification' CPT entirely.
     * - Excludes specific admin/account pages by slug (dashboard, my-account, etc.)
     *   so private/utility pages never surface in public search.
     *
     * @param \WP_Query $query
     * @return void
     */
    public function filter_search_results($query)
    {
        if (is_admin() || !$query->is_search() || !$query->is_main_query()) {
            return;
        }

        // 1. Exclude the 'certification' CPT from search results
        $post_types = $query->get('post_type');

        if (empty($post_types) || $post_types === 'any') {
            // No explicit post_type set, so build the default searchable list
            // (all public, search-enabled post types) minus 'certification'.
            $post_types = get_post_types([
                'public'              => true,
                'exclude_from_search' => false,
            ]);
        } else {
            $post_types = (array) $post_types;
        }

        $post_types = array_diff($post_types, ['certification']);
        $query->set('post_type', array_values($post_types));

        // 2. Exclude specific pages by slug (dashboard, my-account, etc.)
        $excluded_slugs = [
            'dashboard',
            'my-account',
            'home-page',
            'suppliers',
            'products',
            'join-tradeshow',
            'terms-conditions',
            'privacy-policy',
            'about-us',
            'faqs'
        ];

        $excluded_ids = [];
        foreach ($excluded_slugs as $slug) {
            $page = get_page_by_path($slug);
            if ($page) {
                $excluded_ids[] = $page->ID;
            }
        }

        if (!empty($excluded_ids)) {
            $existing_excludes = (array) $query->get('post__not_in');
            $query->set('post__not_in', array_merge($existing_excludes, $excluded_ids));
        }
    }
}