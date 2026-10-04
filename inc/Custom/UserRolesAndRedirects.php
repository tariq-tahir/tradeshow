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
        if (!$this->is_dashboard_request()) {
            return;
        }

        $current_user = wp_get_current_user();

        // Logged-out visitors are sent to My Account
        if (!$current_user->ID) {
            wp_redirect($this->my_account_url());
            exit;
        }

        // Disabled exporters are logged out and redirected
        if ($this->is_disabled_exporter($current_user)) {
            if (ob_get_level()) {
                ob_end_clean();
            }
            wp_logout();
            wp_redirect(add_query_arg('login', 'disabled', $this->my_account_url()));
            exit;
        }
    }

    /**
     * Whether the current request targets a dashboard page.
     *
     * @return bool
     */
    protected function is_dashboard_request()
    {
        if (is_page('dashboard')) {
            return true;
        }

        $uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';

        return strpos($uri, '/dashboard/') !== false;
    }

    /**
     * Whether the given user is an exporter with a disabled company account.
     *
     * @param \WP_User $current_user
     * @return bool
     */
    protected function is_disabled_exporter($current_user)
    {
        if (!in_array('exporter', (array) $current_user->roles, true)) {
            return false;
        }

        return get_user_meta($current_user->ID, 'company_status', true) === 'disabled';
    }

    /**
     * Resolve the My Account URL, with a safe fallback when WooCommerce is inactive.
     *
     * @return string
     */
    protected function my_account_url()
    {
        if (function_exists('wc_get_page_permalink')) {
            $url = wc_get_page_permalink('myaccount');
            if ($url) {
                return $url;
            }
        }

        return home_url('/my-account/');
    }

    /**
     * Show disabled account message on frontend My Account login form
     */
    public function show_disabled_account_message_on_frontend()
    {
        if (isset($_GET['login']) && $_GET['login'] === 'disabled') {
            wc_print_notice(
                esc_html__( 'Your company account has been disabled. Please contact the administrator for assistance.', 'awps' ),
                'error'
            );
        }
    }
}