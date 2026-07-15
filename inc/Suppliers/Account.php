<?php
/**
 * Supplier Account Handler - MANUAL FLOW ONLY
 * Handles login, registration, password reset (NO payment gateway)
 * 
 * @package AWPS\Suppliers
 */

namespace AWPS\Suppliers;

class Account
{
    /**
     * Register all hooks
     */
    public function register()
    {
        // Register custom query vars
        add_filter('query_vars', [$this, 'register_account_query_vars'], 1);
        
        // Allow dashboard URL in safe redirects
        add_filter('allowed_redirect_hosts', [$this, 'allow_dashboard_redirect']);
        
        // Override WordPress default login redirect for suppliers
        add_filter('login_redirect', [$this, 'custom_login_redirect'], 999, 3);
        
        // Register shortcode
        add_shortcode('awps_my_account', [$this, 'render_account_page']);
        
        // Handle form submissions
        add_action('template_redirect', [$this, 'handle_login'], 1);
        add_action('template_redirect', [$this, 'handle_registration'], 1);
        //add_action('template_redirect', [$this, 'handle_password_reset'], 1);
        add_action('template_redirect', [$this, 'handle_logout'], 1);
        
        // AJAX handlers
        add_action('wp_ajax_awps_ajax_login', [$this, 'ajax_login']);
        add_action('wp_ajax_nopriv_awps_ajax_login', [$this, 'ajax_login']);
        add_action('wp_ajax_awps_ajax_register', [$this, 'ajax_register']);
        add_action('wp_ajax_nopriv_awps_ajax_register', [$this, 'ajax_register']);
        
        // AJAX handler for dynamic city loading
        add_action('wp_ajax_awps_get_cities', [$this, 'ajax_get_cities']);
        add_action('wp_ajax_nopriv_awps_get_cities', [$this, 'ajax_get_cities']);
        
        // Enqueue assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);


        // Password reset via admin-post.php (bypasses frontend security blocks)
        add_action('admin_post_awps_reset_password', [$this, 'handle_password_reset_via_admin_post']);
        add_action('admin_post_nopriv_awps_reset_password', [$this, 'handle_password_reset_via_admin_post']);

    }

   /**
 * Handle password reset via admin-post.php (bypasses frontend security blocks)
 */
public function handle_password_reset_via_admin_post()
{
    // Verify nonce (same action + name as form)
    if (!isset($_POST['awps_reset_nonce']) || !wp_verify_nonce($_POST['awps_reset_nonce'], 'awps_reset')) {
        wp_safe_redirect(add_query_arg(['awps_error' => 'security_failed'], home_url('/my-account/?awps_action=reset')));
        exit;
    }
    
    $email = sanitize_email($_POST['email'] ?? '');
    
    if (empty($email) || !is_email($email)) {
        wp_safe_redirect(add_query_arg(['awps_error' => 'invalid_email'], home_url('/my-account/?awps_action=reset')));
        exit;
    }
    
    // ✅ Security best practice: Always show "sent" message (prevents user enumeration)
    $user = get_user_by('email', $email);
    if ($user) {
        retrieve_password($user->user_login); // WordPress core function
    }
    
    wp_safe_redirect(add_query_arg(['awps_message' => 'reset_sent'], home_url('/my-account/?awps_action=reset')));
    exit;
}

    /**
     * Register custom query vars for account page
     */
    public function register_account_query_vars($vars) {
        $vars[] = 'error';
        $vars[] = 'message';
        $vars[] = 'awps_action';
        return $vars;
    }

    /**
     * Override WordPress default login redirect for suppliers
     */
    public function custom_login_redirect($redirect_to, $requested_redirect_to, $user)
    {
        if ($user && !is_wp_error($user) && in_array('supplier', (array) $user->roles)) {
            return home_url('/dashboard/');
        }
        return $redirect_to;
    }

    /**
     * Allow dashboard URL in safe redirects
     */
    public function allow_dashboard_redirect($hosts)
    {
        $hosts[] = home_url();
        return $hosts;
    }

    /**
     * Enqueue CSS/JS for account page
     */
    public function enqueue_assets()
    {
        if (!is_page('my-account')) {
            return;
        }
        
        wp_localize_script('awps-account-js', 'awpsAccount', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('awps_account_nonce'),
            'messages' => [
                'login_success'    => __('Login successful! Redirecting...', 'awps'),
                'login_error'      => __('Invalid credentials. Please try again.', 'awps'),
                'register_success' => __('Registration received! Check your email.', 'awps'),
                'register_error'   => __('Registration failed. Please try again.', 'awps'),
                'reset_sent'       => __('Password reset link sent to your email.', 'awps'),
            ]
        ]);
    }

    /**
     * Render account page (login/register/reset)
     */
    public function render_account_page()
    {
        if (is_user_logged_in()) {
            wp_safe_redirect(home_url('/dashboard/'));
            exit;
        }
        
        $action  = get_query_var('awps_action') ?: (isset($_GET['awps_action']) ? sanitize_text_field($_GET['awps_action']) : 'login');
        $error   = get_query_var('error') ?: (isset($_GET['error']) ? sanitize_text_field($_GET['error']) : '');
        $message = get_query_var('message') 
            ?: (isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '')
            ?: (isset($_GET['awps_message']) ? sanitize_text_field($_GET['awps_message']) : '');
        
        ob_start();
        
        // Handle registration pending confirmation
        if ($action === 'register' && isset($_GET['awps_message']) && $_GET['awps_message'] === 'registration_pending') {
            $view_path = get_theme_file_path('views/suppliers/registration-pending.php');
            if (file_exists($view_path)) {
                include $view_path;
            } else {
                // Fallback inline view
                ?>
                <div style="max-width:600px; margin:40px auto; padding:30px; text-align:center; font-family:sans-serif;">
                    <div style="font-size:64px; margin-bottom:20px;">✅</div>
                    <h1 style="color:#2e7d32; margin:0 0 15px 0;">Registration Received!</h1>
                    <p style="color:#666; font-size:18px; margin:0 0 30px 0;">
                        Thank you for registering with TradeShow.<br>
                        Our team will contact you within 24-48 hours.
                    </p>
                    <p style="margin:30px 0 0 0;">
                        <a href="<?php echo home_url('/my-account/?awps_action=login'); ?>" 
                           style="display:inline-block; padding:12px 30px; background:#007cba; color:white; text-decoration:none; border-radius:4px; font-weight:500;">
                            Login to Your Account
                        </a>
                    </p>
                </div>
                <?php
            }
        } else {
            // Normal flow: render the view file
            $view_path = get_theme_file_path('views/suppliers/' . $action . '.php');
            if (file_exists($view_path)) {
                include $view_path;
            } else {
                error_log('AWPS: View not found: ' . $view_path);
                include get_theme_file_path('views/suppliers/login.php');
            }
        }
        
        return ob_get_clean();
    }

    /**
     * Handle login form submission
     */
    public function handle_login()
    {
        if (!isset($_POST['awps_login_nonce']) || !wp_verify_nonce($_POST['awps_login_nonce'], 'awps_login')) {
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['awps_action']) || $_POST['awps_action'] !== 'login') {
            return;
        }
        
        $email    = sanitize_email($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            wp_safe_redirect(add_query_arg(['awps_error' => 'missing_fields'], home_url('/my-account/?awps_action=login')));
            exit;
        }
        
        $credentials = [
            'user_login'    => $email,
            'user_password' => $password,
            'remember'      => true
        ];
        
        $user = wp_signon($credentials);

        if (is_wp_error($user)) {
            wp_safe_redirect(add_query_arg(['awps_error' => 'invalid_credentials'], home_url('/my-account/?awps_action=login')));
            exit;
        }

        // Only allow suppliers to login via this form
        if (!in_array('supplier', (array) $user->roles)) {
            wp_logout();
            wp_safe_redirect(add_query_arg(['awps_error' => 'access_denied'], home_url('/my-account/?awps_action=login')));
            exit;
        }

        wp_safe_redirect(home_url('/dashboard/'));
        exit;
    }

    /**
     * Handle registration form submission - EMAIL ONLY FLOW
     * FLOW: Validate -> Email Admin + User -> Show Confirmation (NO user created)
     */
    public function handle_registration()
    {
        if (!isset($_POST['awps_register_nonce']) || !wp_verify_nonce($_POST['awps_register_nonce'], 'awps_register')) {
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['awps_action']) || $_POST['awps_action'] !== 'register') {
            return;
        }
        
        // Sanitize inputs (NO password field, NO user creation)
        $reg_data = [
            'company_name'    => sanitize_text_field($_POST['company_name'] ?? ''),
            'email'           => sanitize_email($_POST['email'] ?? ''),
            'address'         => sanitize_text_field($_POST['address'] ?? ''),
            'state'           => sanitize_text_field($_POST['state'] ?? ''),
            'city'            => sanitize_text_field($_POST['city'] ?? ''),
            'contact_person'  => sanitize_text_field($_POST['contact_person'] ?? ''),
            'designation'     => sanitize_text_field($_POST['designation'] ?? ''),
            'whatsapp'        => sanitize_text_field($_POST['whatsapp'] ?? ''),
            'payment_plan'    => sanitize_text_field($_POST['payment_plan'] ?? '70000'),
            'registered_at'   => current_time('mysql'),
        ];
        
        // Validate required fields
        $errors = [];
        if (empty($reg_data['company_name']) || empty($reg_data['email']) || empty($reg_data['contact_person']) || empty($reg_data['designation']) || empty($reg_data['whatsapp'])) {
            $errors[] = 'missing_fields';
        }
        if (!is_email($reg_data['email'])) $errors[] = 'invalid_email';
        if (email_exists($reg_data['email'])) $errors[] = 'email_exists';
        if (!preg_match('/^\+?[0-9\s\-\(\)]{7,15}$/', $reg_data['whatsapp'])) $errors[] = 'invalid_whatsapp';
        if (empty($reg_data['payment_plan']) || !in_array($reg_data['payment_plan'], ['70000', '150000'])) $errors[] = 'payment_required';
        if (!isset($_POST['terms_accepted'])) $errors[] = 'terms_required';
        
        if (!empty($errors)) {
            wp_safe_redirect(add_query_arg(['awps_action' => 'register', 'awps_error' => implode(',', $errors)], home_url('/my-account/')));
            exit;
        }
        
        // ✅ STEP 1: Email admin with new registration details
        $admin_email = get_option('admin_email');
        $subject = sprintf('🆕 New Supplier Registration: %s', $reg_data['company_name']);
        
        $amount_formatted = number_format((int) $reg_data['payment_plan'], 2);
        $bank_details = "
Bank Name: Your Bank Name
Account Title: Your Account Title
Account Number: XXX-XXXXXXX-X
IBAN: PKXX XXXX XXXX XXXX XXXX
Reference: {$reg_data['company_name']} - Supplier Registration
        ";
        
        $admin_message = sprintf("
<h2>🆕 New Supplier Registration (Manual Approval Required)</h2>
<p><strong>Company:</strong> %s</p>
<p><strong>Contact:</strong> %s (%s)</p>
<p><strong>WhatsApp:</strong> %s</p>
<p><strong>Location:</strong> %s, %s, %s</p>
<p><strong>Plan:</strong> PKR %s</p>
<p><strong>Registered:</strong> %s</p>

<hr>

<p><strong>Next Steps:</strong></p>
<ol>
    <li>Review this registration</li>
    <li>Contact the applicant to confirm details</li>
    <li>Share bank details if not already sent</li>
    <li>Wait for payment receipt</li>
    <li>Verify payment in your bank account</li>
    <li>Manually create WordPress user: <a href='%s'>Users → Add New</a></li>
    <li>Set role: <code>supplier</code>, Status: <code>active</code></li>
    <li>Email user their login credentials</li>
</ol>
",
            esc_html($reg_data['company_name']),
            esc_html($reg_data['contact_person']),
            esc_html($reg_data['email']),
            esc_html($reg_data['whatsapp']),
            esc_html($reg_data['city']),
            esc_html($reg_data['state']),
            esc_html($reg_data['address']),
            $amount_formatted,
            esc_html($reg_data['registered_at']),
            esc_html($bank_details),
            admin_url('user-new.php')
        );
        
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        wp_mail($admin_email, $subject, $admin_message, $headers);
        
        // ✅ STEP 2: Email user with confirmation + payment instructions
        $user_subject = 'Thank you for registering with TradeShow - Next Steps';


        //<hr>
// <h3>💳 Payment Instructions</h3>
// <p>To complete your registration, please transfer the plan amount via bank transfer:</p>

// <p><strong>Plan:</strong> PKR %s</p>

// <pre style='background:#f9f9f9;padding:15px;border-left:4px solid #007cba;font-family:monospace;'>%s</pre>

// <p><strong>Important:</strong> Please include your company name in the payment reference.</p>

// <p>After transferring, please reply to this email or WhatsApp us at <strong>%s</strong> with:</p>
// <ul>
//     <li>✅ Payment receipt/screenshot</li>
//     <li>✅ Transaction ID / Reference number</li>
//     <li>✅ Date of transfer</li>
// </ul>

// <p>Once we confirm your payment, we will:</p>
// <ol>
//     <li>Manually create your supplier account</li>
//     <li>Email you your login credentials (username & password)</li>
//     <li>Activate your dashboard access</li>
// </ol>

        
        $user_message = sprintf("
<p>Dear %s,</p>

<p>Thank you for registering <strong>%s</strong> with TradeShow!</p>

<p>✅ <strong>Your registration has been received.</strong> Our team will review your details and contact you within 24-48 hours.</p>


<hr>
<p>If you have any questions, simply contact us:</p>
<ul>
    <li>📧 care@tradeshow.pk</li>
    <li>📱 WhatsApp: +92 300 121 8566</li>
</ul>

<p>Welcome to TradeShow! 🚀</p>
<p><em>The TradeShow Team</em></p>
",
            esc_html($reg_data['contact_person']),
            esc_html($reg_data['company_name']),
            $amount_formatted,
            //esc_html($bank_details),
            esc_html($reg_data['whatsapp']),
            esc_html(get_option('admin_email')),
            esc_html($reg_data['whatsapp'])
        );
        
        wp_mail($reg_data['email'], $user_subject, $user_message, $headers);
        
        // ✅ STEP 3: Redirect to confirmation page (NO user created)
        wp_safe_redirect(home_url('/my-account/?awps_action=register&awps_message=registration_pending'));
        exit;
    }

    /**
     * AJAX handler for dynamic city loading
     */
    public function ajax_get_cities()
    {
        check_ajax_referer('awps_account_nonce', 'security', true);
        
        $state = isset($_REQUEST['state']) ? sanitize_text_field($_REQUEST['state']) : '';
        
        if (empty($state) || !function_exists('get_state_cities')) {
            wp_send_json_error(['message' => __('Invalid state or helper missing.', 'awps')]);
        }

        $all_cities = get_state_cities();
        
        if (!is_array($all_cities) || !isset($all_cities[$state])) {
            wp_send_json_error(['message' => __('State not found.', 'awps')]);
        }

        wp_send_json_success(['cities' => $all_cities[$state]]);
    }

    /**
     * Handle password reset request
     */
    public function handle_password_reset()
    {

    
        
        // Only process reset page requests
        if (!isset($_GET['awps_action']) || $_GET['awps_action'] !== 'reset') {
            return;
        }
        
        // Only process POST submissions
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        
        // Verify nonce (action + name must match form exactly)
        if (!isset($_POST['awps_reset_nonce']) || !wp_verify_nonce($_POST['awps_reset_nonce'], 'awps_reset')) {
            // Redirect with error instead of silent fail
            wp_safe_redirect(add_query_arg(['awps_error' => 'security_failed'], home_url('/my-account/?awps_action=reset')));
            exit;
        }
        
        $email = sanitize_email($_POST['email'] ?? '');
        
        if (empty($email) || !is_email($email)) {
            wp_safe_redirect(add_query_arg(['awps_error' => 'invalid_email'], home_url('/my-account/?awps_action=reset')));
            exit;
        }
        
        // Security best practice: Always show "sent" message (prevents user enumeration)
        $user = get_user_by('email', $email);
        if ($user) {
            retrieve_password($user->user_login); // WordPress core function
        }
        
        // Redirect to reset page with success message (NOT wp_get_referer)
        wp_safe_redirect(add_query_arg(['awps_message' => 'reset_sent'], home_url('/my-account/?awps_action=reset')));

        exit;
    }

    /**
     * Handle logout
     */
    public function handle_logout()
    {
        if (isset($_GET['awps_action']) && $_GET['awps_action'] === 'logout') {
            if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'awps_logout')) {
                wp_logout();
                wp_safe_redirect(home_url('/my-account/?awps_message=logged_out'));
                exit;
            }
        }
    }

    /**
     * AJAX login handler
     */
    public function ajax_login()
    {
        check_ajax_referer('awps_account_nonce', 'security');
        
        $email    = sanitize_email($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            wp_send_json_error(['message' => __('Please fill in all fields.', 'awps')]);
        }
        
        $user = wp_authenticate($email, $password);
        
        if (is_wp_error($user)) {
            wp_send_json_error(['message' => __('Invalid email or password.', 'awps')]);
        }
        
        if (!in_array('supplier', (array) $user->roles)) {
            wp_send_json_error(['message' => __('This account is not approved for supplier access.', 'awps')]);
        }
        
        wp_set_auth_cookie($user->ID);
        
        wp_send_json_success([
            'message'   => __('Login successful! Redirecting...', 'awps'),
            'redirect'  => home_url('/dashboard/')
        ]);
    }

    /**
     * AJAX registration handler (optional - can be removed if not used)
     */
    public function ajax_register()
    {
        // This is kept for backward compatibility but not used in manual flow
        wp_send_json_error(['message' => __('Please use the registration form on the website.', 'awps')]);
    }
}