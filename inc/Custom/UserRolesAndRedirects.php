<?php

namespace Awps\Custom;

/**
 * Handles user role-based redirects, authentication guards,
 * and access control for exporters.
 */
class UserRolesAndRedirects
{
    /**
     * Register all hooks and actions.
     *
     * @return void
     */
    public function register()
    {
        // Redirect logged-out users from /dashboard
        add_action('template_redirect', [$this, 'redirect_logged_out_from_dashboard']);

        // WooCommerce login redirect for exporters
        add_filter('woocommerce_login_redirect', [$this, 'redirect_exporter_after_login'], 10, 2);

        // Prevent exporters from accessing /my-account/
        add_action('template_redirect', [$this, 'block_exporter_my_account_access']);

        // Prevent disabled exporters from logging in
        add_filter('authenticate', [$this, 'check_exporter_status_on_login'], 30, 3);

        // Display disabled account message on wp-login.php
        add_action('login_form', [$this, 'display_disabled_account_message']);

        // Redirect if exporter account is disabled (on dashboard pages)
        add_action('template_redirect', [$this, 'check_exporter_account_status']);

        // Show disabled message on frontend My Account page
        add_action('woocommerce_before_customer_login_form', [$this, 'show_disabled_account_message_on_frontend']);
    }

    /**
     * Redirect logged-out users away from /dashboard
     */
    public function redirect_logged_out_from_dashboard()
    {
        if (is_page('dashboard') && !is_user_logged_in()) {
            wp_redirect(site_url('/my-account/'));
            exit;
        }
    }

    /**
     * Redirect exporters to /dashboard after login
     */
    public function redirect_exporter_after_login($redirect, $user)
    {
        if (in_array('exporter', (array) $user->roles)) {
            return site_url('/dashboard/');
        }
        return $redirect;
    }

    /**
     * Prevent exporters from accessing /my-account/
     */
    public function block_exporter_my_account_access()
    {
        if (is_account_page() && is_user_logged_in()) {
            $user = wp_get_current_user();
            if (in_array('exporter', (array) $user->roles)) {
                wp_redirect(site_url('/dashboard/'));
                exit;
            }
        }
    }

    /**
     * Prevent disabled exporters from logging in
     */
    public function check_exporter_status_on_login($user, $username, $password)
    {
        // If authentication already failed, don't interfere
        if (is_wp_error($user) || empty($user)) {
            return $user;
        }

        // Only apply to exporters
        if (in_array('exporter', (array) $user->roles)) {
            $company_status = get_user_meta($user->ID, 'company_status', true);
            if ($company_status === 'disabled') {
                return new \WP_Error(
                    'account_disabled',
                    __('Your account has been disabled. Please contact the administrator.', 'awps')
                );
            }
        }

        return $user;
    }

    /**
     * Display custom message on wp-login.php if account is disabled
     */
    public function display_disabled_account_message()
    {
        if (isset($_GET['login']) && $_GET['login'] === 'disabled') {
            echo '<p class="message" style="padding: 12px; margin: 12px 0; background-color: #ffebe8; color: #a00;">' .
                 __('Your account has been disabled. Please contact the administrator.', 'awps') .
                 '</p>';
        }
    }

    /**
     * Redirect user if account is disabled (on dashboard pages)
     */
    public function check_exporter_account_status()
    {
        // Only run on dashboard pages
        if (is_page('dashboard') || strpos($_SERVER['REQUEST_URI'] ?? '', '/dashboard/') !== false) {
            $current_user = wp_get_current_user();

            // If logged in and is an exporter
            if ($current_user->ID && in_array('exporter', (array) $current_user->roles)) {
                $company_status = get_user_meta($current_user->ID, 'company_status', true);
                if ($company_status === 'disabled') {
                    if (ob_get_level()) {
                        ob_end_clean();
                    }
                    wp_logout();
                    wp_redirect(add_query_arg('login', 'disabled', wc_get_page_permalink('myaccount')));
                    exit;
                }
            }

            // If not logged in, redirect to My Account
            if (!$current_user->ID) {
                wp_redirect(wc_get_page_permalink('myaccount'));
                exit;
            }
        }
    }

    /**
     * Show disabled account message on frontend My Account login form
     */
    public function show_disabled_account_message_on_frontend()
    {
        if (isset($_GET['login']) && $_GET['login'] === 'disabled') {
            wc_print_notice(
                'Your company account has been disabled. Please contact the administrator for assistance.',
                'error'
            );
        }
    }
}