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
}