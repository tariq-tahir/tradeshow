<?php
namespace AWPS\Suppliers;

if (!defined('ABSPATH')) exit;

/**
 * Unified Inquiry Manager - Backend Only
 * Handles AJAX submissions, admin UI, filtering, and CSV export
 * No external JS enqueue - forms use inline JavaScript
 */
class InquiryManager {

    /**
     * Register all hooks
     */
    public function register() {
        // AJAX handlers for both form sources
        add_action('wp_ajax_send_exporter_inquiry', [$this, 'handle_profile_inquiry']);
        add_action('wp_ajax_nopriv_send_exporter_inquiry', [$this, 'handle_profile_inquiry']);
        add_action('wp_ajax_awps_send_inquiry', [$this, 'handle_product_inquiry']);
        add_action('wp_ajax_nopriv_awps_send_inquiry', [$this, 'handle_product_inquiry']);

        // Admin columns & metabox
        add_filter('manage_inquiry_posts_columns', [$this, 'admin_columns']);
        add_action('manage_inquiry_posts_custom_column', [$this, 'admin_column_content'], 10, 2);
        add_action('add_meta_boxes', [$this, 'add_inquiry_metabox']);
        add_filter('post_row_actions', [$this, 'remove_quick_edit'], 10, 2);
        
        // Admin filtering
        add_action('restrict_manage_posts', [$this, 'add_admin_filters']);
        add_action('pre_get_posts', [$this, 'filter_query_by_meta']);
        
        // CSV Export
        add_action('admin_init', [$this, 'handle_export_csv']);
        add_action('manage_posts_extra_tablenav', [$this, 'add_export_button']);
    }

    /**
     * Generate professional inquiry code: TS26-04-X9K2L
     */
    protected function generate_inquiry_code() {
        $code = "TS" . date('y') . "-" . date('m') . "-" . strtoupper(substr(wp_generate_password(10, false, false), 0, 5));

        /**
         * Filters the generated inquiry reference code.
         *
         * @param string $code The newly generated inquiry code (e.g. "TS26-04-X9K2L").
         */
        return apply_filters('awps_inquiry_code', $code);
    }


    /**
     * ─────────────────────────────────────────────────────────────
     * PROFILE INQUIRY HANDLER (Supplier Profile Page)
     * ─────────────────────────────────────────────────────────────
     */
    public function handle_profile_inquiry() {
        if (!check_ajax_referer('exporter_inquiry_nonce', 'security', false)) {
            wp_send_json_error(['message' => __('Security check failed. Please reload.', 'awps')]);
        }
        
        $name        = sanitize_text_field($_POST['name'] ?? '');
        $email       = sanitize_email($_POST['email'] ?? '');
        $whatsapp    = sanitize_text_field($_POST['whatsapp'] ?? '');
        $message     = sanitize_textarea_field($_POST['message'] ?? '');
        $exporter_id = intval($_POST['exporter_id'] ?? 0);

        if (!$name || !$email || !$message || !$exporter_id) {
            wp_send_json_error(['message' => __('Please fill Name, Email, and Message.', 'awps')]);
        }

        $inquiry_id = wp_insert_post([
            'post_type'    => 'inquiry',
            'post_status'  => 'publish',
            'post_title'   => sanitize_text_field("{$name} — {$email}"),
            'post_content' => $message,
        ]);

        if (!$inquiry_id || is_wp_error($inquiry_id)) {
            wp_send_json_error(['message' => __('Failed to save inquiry.', 'awps')]);
        }


        $code = $this->generate_inquiry_code();

        $meta = [
            'inquiry_code'   => $code,
            'source'         => 'exporter_profile',
            'buyer_name'     => $name,
            'buyer_email'    => $email,
            'buyer_whatsapp' => $whatsapp,
            'buyer_message'  => $message,
            'exporter_id'    => $exporter_id,
            'product_id'     => 0,
        ];

        /**
         * Filters the post meta saved for a profile-page inquiry.
         *
         * @param array  $meta        Key => value pairs of inquiry meta.
         * @param int    $inquiry_id  The newly created inquiry post ID.
         * @param string $source      Source identifier ('exporter_profile').
         */
        $meta = apply_filters('awps_inquiry_meta', $meta, $inquiry_id, 'exporter_profile');

        foreach ($meta as $key => $value) {
            update_post_meta($inquiry_id, $key, $value);
        }

        /**
         * Fires after a profile inquiry post has been created and its meta saved.
         *
         * @param int   $inquiry_id  The inquiry post ID.
         * @param array $meta        The saved inquiry meta.
         */
        do_action('awps_inquiry_created', $inquiry_id, $meta);

        $this->send_emails($inquiry_id, 'profile');

        /**
         * Fires after all profile inquiry processing (including emails) completes.
         *
         * @param int   $inquiry_id The inquiry post ID.
         * @param array $meta       The saved inquiry meta.
         */
        do_action('awps_inquiry_processed', $inquiry_id, $meta);

        wp_send_json_success(['message' => __('✅ Inquiry sent successfully!', 'awps')]);
    }

    /**
     * ─────────────────────────────────────────────────────────────
     * PRODUCT INQUIRY HANDLER (Single Product Page)
     * ─────────────────────────────────────────────────────────────
     */
        public function handle_product_inquiry() {
        if (!check_ajax_referer('awps_inquiry_nonce', 'security', false)) {
            wp_send_json_error(['message' => __('Security check failed. Please reload.', 'awps')]);
        }
        
        // Sanitize all fields (ADD WHATSAPP LINE)
        $company           = sanitize_text_field($_POST['inq_company'] ?? '');
        $name              = sanitize_text_field($_POST['inq_name'] ?? '');
        $email             = sanitize_email($_POST['inq_email'] ?? '');
        $whatsapp          = sanitize_text_field($_POST['inq_whatsapp'] ?? '');
        $country           = sanitize_text_field($_POST['inq_country'] ?? '');
        $destination_port  = sanitize_text_field($_POST['inq_destination_port'] ?? '');
        $shipment_type     = sanitize_text_field($_POST['inq_shipment_type'] ?? '');
        $qty_value         = sanitize_text_field($_POST['inq_qty_value'] ?? '');
        $qty_unit          = sanitize_text_field($_POST['inq_qty_unit'] ?? '');
        $message           = sanitize_textarea_field($_POST['inq_msg'] ?? '');
        $product_id        = intval($_POST['awps_product_id'] ?? 0);
        $exporter_id       = intval($_POST['awps_exporter_id'] ?? 0);

        // Validate required fields (WhatsApp is optional, so not in validation)
        if (!$company || !$name || !$email || !$country || !$destination_port || !$shipment_type || !$qty_value || !$qty_unit || !$message || !$product_id) {
            wp_send_json_error(['message' => __('Please fill all required fields.', 'awps')]);
        }
        if (!is_email($email)) {
            wp_send_json_error(['message' => __('❌ Please enter a valid email address.', 'awps')]);
        }

        $inquiry_id = wp_insert_post([
            'post_type'    => 'inquiry',
            'post_status'  => 'publish',
            'post_title'   => sanitize_text_field("{$company} — {$name}"),
            'post_content' => $message,
        ]);

        if (!$inquiry_id || is_wp_error($inquiry_id)) {
            wp_send_json_error(['message' => __('Failed to save inquiry.', 'awps')]);
        }

        $code = $this->generate_inquiry_code();
        $qty_formatted = "{$qty_value} {$qty_unit}";
        
        // All inquiry meta (WhatsApp included)
        $meta = [
            'inquiry_code'           => $code,
            'source'                 => 'product_page',
            'company_name'           => $company,
            'buyer_name'             => $name,
            'buyer_email'            => $email,
            'buyer_whatsapp'         => $whatsapp,
            'buyer_country'          => $country,
            'buyer_destination_port' => $destination_port,
            'buyer_shipment_type'    => $shipment_type,
            'buyer_qty_value'        => $qty_value,
            'buyer_qty_unit'         => $qty_unit,
            'buyer_qty_formatted'    => $qty_formatted,
            'buyer_message'          => $message,
            'product_id'             => $product_id,
            'exporter_id'            => $exporter_id,
        ];

        /** This filter is documented in inc/Suppliers/InquiryManager.php */
        $meta = apply_filters('awps_inquiry_meta', $meta, $inquiry_id, 'product_page');

        foreach ($meta as $key => $value) {
            update_post_meta($inquiry_id, $key, $value);
        }

        /** This action is documented in inc/Suppliers/InquiryManager.php */
        do_action('awps_inquiry_created', $inquiry_id, $meta);

        $this->send_emails($inquiry_id, 'product');

        /** This action is documented in inc/Suppliers/InquiryManager.php */
        do_action('awps_inquiry_processed', $inquiry_id, $meta);

        wp_send_json_success(['message' => sprintf(__('✅ Inquiry sent successfully! Reference: %s', 'awps'), $code)]);
    }

   
    /**
     * Send email notifications to exporter & buyer (with conditional fields + WhatsApp)
     */
    protected function send_emails($inquiry_id, $source) {

        $ctx = [
            'id'      => $inquiry_id,
            'source'  => $source,
            'code'    => get_post_meta($inquiry_id, 'inquiry_code', true),
            'buyer'   => [
                'email'    => get_post_meta($inquiry_id, 'buyer_email', true),
                'name'     => get_post_meta($inquiry_id, 'buyer_name', true),
                'company'  => get_post_meta($inquiry_id, 'company_name', true),
                'whatsapp' => get_post_meta($inquiry_id, 'buyer_whatsapp', true),
                'country'  => get_post_meta($inquiry_id, 'buyer_country', true),
                'port'     => get_post_meta($inquiry_id, 'buyer_destination_port', true),
                'shipment' => get_post_meta($inquiry_id, 'buyer_shipment_type', true),
                'qty'      => get_post_meta($inquiry_id, 'buyer_qty_formatted', true),
                'message'  => get_post_meta($inquiry_id, 'buyer_message', true),
            ],
            'site_name' => get_bloginfo('name'),
            'site_url'  => home_url(),
            'http_host' => isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost',
            'time'      => current_time('F j, Y g:i A'),
        ];

        // Format WhatsApp for link
        $whatsapp_clean = $ctx['buyer']['whatsapp'] ? preg_replace('/[^\d+]/', '', trim($ctx['buyer']['whatsapp'])) : '';
        $ctx['whatsapp_link'] = $whatsapp_clean ? 'https://wa.me/' . ltrim($whatsapp_clean, '+') : '';

        // Resolve exporter contact (User ID or Supplier Post ID)
        $exporter = $this->resolve_exporter_contact(get_post_meta($inquiry_id, 'exporter_id', true));
        $ctx['exporter_email'] = $exporter['email'];
        $ctx['exporter_name']  = $exporter['name'];

        // ─────────────────────────────────────────────────────────────
        // SUPPLIER EMAIL
        // ─────────────────────────────────────────────────────────────
        $subj_exp = "📩 New {$source} Inquiry — Ref: {$ctx['code']}";
        $body_exp = $this->build_supplier_email_body($ctx);

        $headers_exp = [
            'Content-Type: text/html; charset=UTF-8',
            'Reply-To: ' . $ctx['buyer']['email'],
            'From: ' . $ctx['site_name'] . ' <no-reply@' . $ctx['http_host'] . '>',
            'X-Priority: 2'
        ];

        /**
         * Filters the email headers used for the exporter (supplier) notification.
         *
         * @param array  $headers_exp Email headers.
         * @param array  $ctx         Inquiry context data.
         * @param string $source      Inquiry source ('profile' or 'product').
         */
        $headers_exp = apply_filters('awps_inquiry_email_headers', $headers_exp, $ctx, 'exporter');

        wp_mail($ctx['exporter_email'], $subj_exp, $body_exp, $headers_exp);

        // ─────────────────────────────────────────────────────────────
        // BUYER CONFIRMATION EMAIL
        // ─────────────────────────────────────────────────────────────
        $subj_buy = "✅ Inquiry Received — Ref: {$ctx['code']}";
        $body_buy = $this->build_buyer_email_body($ctx);

        $headers_buy = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $ctx['site_name'] . ' <no-reply@' . $ctx['http_host'] . '>'
        ];

        /** This filter is documented in inc/Suppliers/InquiryManager.php */
        $headers_buy = apply_filters('awps_inquiry_email_headers', $headers_buy, $ctx, 'buyer');

        wp_mail($ctx['buyer']['email'], $subj_buy, $body_buy, $headers_buy);
    }

    /**
     * Resolve exporter email/name from either a WP User ID or a Supplier Post ID.
     *
     * @param mixed $exporter_id
     * @return array{email:string,name:string}
     */
    protected function resolve_exporter_contact($exporter_id) {
        $email = '';
        $name  = '';

        if ($exporter_id) {
            // Check if this is a WordPress User ID
            $user = get_userdata(intval($exporter_id));

            if ($user && !is_wp_error($user)) {
                // It's a User ID (from Product forms)
                $email = $user->user_email;
                $name  = get_user_meta($exporter_id, 'company_name', true) ?: $user->display_name;
            } else {
                // It's a Supplier Post ID (from Profile forms)
                $supplier_post = get_post(intval($exporter_id));

                if ($supplier_post) {
                    // Option 1: Try to get email from post meta
                    $email = get_post_meta($exporter_id, 'contact_email', true);
                    $name  = get_post_meta($exporter_id, 'company_name', true);

                    // Option 2: If no email in post meta, get from post author (WordPress User)
                    if (empty($email) && $supplier_post->post_author) {
                        $author_user = get_userdata($supplier_post->post_author);
                        if ($author_user) {
                            $email = $author_user->user_email;
                            $name  = $name ?: get_user_meta($supplier_post->post_author, 'company_name', true) ?: $author_user->display_name;
                        }
                    }

                    // Option 3: Fallback to admin email
                    if (empty($email)) {
                        $email = get_option('admin_email');
                        $name  = $name ?: $supplier_post->post_title;
                    }
                }
            }
        }

        // Final safety fallback
        if (empty($email)) {
            $email = get_option('admin_email');
            $name  = 'Site Administrator';
        }

        return ['email' => $email, 'name' => $name];
    }

    /**
     * Shared inline CSS for inquiry notification emails.
     */
    protected function email_styles($grad_from, $grad_to) {
        return <<<CSS
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; }
                .header { background: linear-gradient(135deg, {$grad_from} 0%, {$grad_to} 100%); color: white; padding: 25px; text-align: center; border-radius: 8px 8px 0 0; }
                .header h1 { margin: 0; font-size: 22px; }
                .header .ref { background: rgba(255,255,255,0.2); padding: 5px 15px; border-radius: 20px; font-size: 13px; margin-top: 8px; display: inline-block; }
                .content { background: #fff; padding: 25px; border: 1px solid #e0e0e0; border-top: none; border-radius: 0 0 8px 8px; }
                .section { margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px dashed #eee; }
                .section:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
                .section h3 { margin: 0 0 12px 0; color: #007cba; font-size: 16px; }
                .field { display: flex; margin-bottom: 8px; }
                .field-label { font-weight: 600; min-width: 140px; color: #555; }
                .field-value { color: #222; }
CSS;
    }

    /**
     * Build the HTML body of the supplier (exporter) notification email.
     *
     * @param array $ctx Inquiry context data built by send_emails().
     * @return string
     */
    protected function build_supplier_email_body($ctx) {
        $b            = $ctx['buyer'];
        $source       = $ctx['source'];
        $inquiry_id   = $ctx['id'];
        $product_id   = get_post_meta($inquiry_id, 'product_id', true);

        $extra_css = '
                .message { background: #f8f9fa; padding: 15px; border-left: 4px solid #007cba; border-radius: 0 4px 4px 0; margin: 10px 0; white-space: pre-wrap; }
                .cta { text-align: center; margin-top: 25px; }
                .cta a { background: #007cba; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: 600; display: inline-block; }
                .cta a.whatsapp { background: #25D366; margin-left: 10px; }
                .footer { text-align: center; padding: 20px; color: #888; font-size: 12px; border-top: 1px solid #eee; margin-top: 25px; }';

        $shipping_section = '';
        if ($source === 'product') {
            $shipping_section = '
                <div class="section">
                    <h3>🚢 Shipping Requirements</h3>
                    <div class="field"><span class="field-label">Destination Port:</span><span class="field-value">' . esc_html($b['port']) . '</span></div>
                    <div class="field"><span class="field-label">Shipment Type:</span><span class="field-value">' . esc_html($b['shipment']) . '</span></div>
                    <div class="field"><span class="field-label">Quantity:</span><span class="field-value">' . esc_html($b['qty']) . '</span></div>
                </div>';
        }

        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>' . $this->email_styles('#007cba', '#005a87') . $extra_css . '
            </style>
        </head>
        <body>
            <div class="header">
                <h1>📦 New Inquiry Received</h1>
                <div class="ref">' . ($source === 'product' ? '📦 Product' : '🏢 Profile') . ' • Ref: ' . esc_html($ctx['code']) . '</div>
            </div>
            <div class="content">
                <div class="section">
                    <h3>🔍 Inquiry Summary</h3>
                    <div class="field"><span class="field-label">Source:</span><span class="field-value">' . ($source === 'profile' ? 'Supplier Profile' : 'Product Page') . '</span></div>
                    <div class="field"><span class="field-label">Received:</span><span class="field-value">' . $ctx['time'] . '</span></div>
                    ' . ($source === 'product' ? '
                    <div class="field"><span class="field-label">Product:</span><span class="field-value"><a href="' . esc_url(get_permalink($product_id)) . '" style="color:#007cba;text-decoration:none;font-weight:600;">' . esc_html(get_the_title($product_id)) . '</a></span></div>
                    ' : '') . '
                </div>

                <div class="section">
                    <h3>👤 Buyer Details</h3>
                    <div class="field"><span class="field-label">Contact:</span><span class="field-value">' . esc_html($b['name']) . '</span></div>
                    <div class="field"><span class="field-label">Email:</span><span class="field-value"><a href="mailto:' . esc_attr($b['email']) . '">' . esc_html($b['email']) . '</a></span></div>
                    ' . ($b['company'] ? '<div class="field"><span class="field-label">Company:</span><span class="field-value">' . esc_html($b['company']) . '</span></div>' : '') . '
                    ' . ($b['whatsapp'] ? '<div class="field"><span class="field-label">WhatsApp:</span><span class="field-value"><a href="' . esc_url($ctx['whatsapp_link']) . '" style="color:#25D366;text-decoration:none;" target="_blank">' . esc_html($b['whatsapp']) . ' 💬</a></span></div>' : '') . '
                    ' . ($b['country'] ? '<div class="field"><span class="field-label">Country:</span><span class="field-value">' . esc_html($b['country']) . '</span></div>' : '') . '
                </div>

                ' . $shipping_section . '

                <div class="section">
                    <h3>💬 Message</h3>
                    <div class="message">' . nl2br(esc_html($b['message'])) . '</div>
                </div>

                <div class="cta">
                    <a href="mailto:' . esc_attr($b['email']) . '?subject=Re: Inquiry ' . esc_attr($ctx['code']) . ' - ' . urlencode($b['company'] ?: $b['name']) . '">✉️ Reply via Email</a>
                    ' . ($ctx['whatsapp_link'] ? '<a href="' . esc_url($ctx['whatsapp_link']) . '" class="whatsapp" target="_blank">💬 WhatsApp</a>' : '') . '
                </div>
            </div>
            <div class="footer">
                <p>This inquiry was submitted via <strong>' . esc_html($ctx['site_name']) . '</strong><br>
                Reference: ' . esc_html($ctx['code']) . '</p>
            </div>
        </body>
        </html>';
    }

    /**
     * Build the HTML body of the buyer confirmation email.
     *
     * @param array $ctx Inquiry context data built by send_emails().
     * @return string
     */
    protected function build_buyer_email_body($ctx) {
        $b          = $ctx['buyer'];
        $source     = $ctx['source'];
        $inquiry_id = $ctx['id'];
        $product_id = get_post_meta($inquiry_id, 'product_id', true);

        $extra_css = '
                .success-box { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 6px; margin-bottom: 20px; }
                .summary-box { background: #f8f9fa; padding: 15px; border-radius: 6px; margin: 10px 0; }
                .summary-box strong { color: #333; }
                .footer { text-align: center; padding: 20px; color: #888; font-size: 12px; border-top: 1px solid #eee; margin-top: 25px; }
                .note { background: #e7f3ff; border-left: 4px solid #007cba; padding: 12px 15px; margin: 15px 0; font-size: 13px; }';

        $shipping_section = '';
        if ($source === 'product') {
            $shipping_section = '
                <div class="section">
                    <h3>🚢 Shipping Requirements</h3>
                    <div class="summary-box">
                        <div class="field"><span class="field-label">Destination Port:</span><span class="field-value">' . esc_html($b['port']) . '</span></div>
                        <div class="field"><span class="field-label">Shipment Type:</span><span class="field-value">' . esc_html($b['shipment']) . '</span></div>
                        <div class="field"><span class="field-label">Quantity:</span><span class="field-value">' . esc_html($b['qty']) . '</span></div>
                    </div>
                </div>';
        }

        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>' . $this->email_styles('#28a745', '#20c997') . $extra_css . '
            </style>
        </head>
        <body>
            <div class="header">
                <h1>✅ Inquiry Successfully Sent</h1>
                <div class="ref">' . ($source === 'product' ? '📦 Product' : '🏢 Profile') . ' • Ref: ' . esc_html($ctx['code']) . '</div>
            </div>
            <div class="content">
                <div class="success-box">
                    <strong>Thank you, ' . esc_html($b['name']) . '!</strong><br>
                    Your inquiry has been sent to <strong>' . esc_html($ctx['exporter_name']) . '</strong>. They will contact you shortly via email or WhatsApp.
                </div>

                <div class="section">
                    <h3>📋 Your Inquiry Summary</h3>
                    <div class="summary-box">
                        <div class="field"><span class="field-label">Reference:</span><span class="field-value"><strong>' . esc_html($ctx['code']) . '</strong></span></div>
                        <div class="field"><span class="field-label">Submitted:</span><span class="field-value">' . $ctx['time'] . '</span></div>
                        ' . ($source === 'product' ? '<div class="field"><span class="field-label">Product:</span><span class="field-value">' . esc_html(get_the_title($product_id)) . '</span></div>' : '') . '
                    </div>
                </div>

                <div class="section">
                    <h3>👤 Your Details</h3>
                    <div class="summary-box">
                        <div class="field"><span class="field-label">Contact:</span><span class="field-value">' . esc_html($b['name']) . '</span></div>
                        <div class="field"><span class="field-label">Email:</span><span class="field-value">' . esc_html($b['email']) . '</span></div>
                        ' . ($b['company'] ? '<div class="field"><span class="field-label">Company:</span><span class="field-value">' . esc_html($b['company']) . '</span></div>' : '') . '
                        ' . ($b['whatsapp'] ? '<div class="field"><span class="field-label">WhatsApp:</span><span class="field-value">' . esc_html($b['whatsapp']) . '</span></div>' : '') . '
                        ' . ($b['country'] ? '<div class="field"><span class="field-label">Country:</span><span class="field-value">' . esc_html($b['country']) . '</span></div>' : '') . '
                    </div>
                </div>

                ' . $shipping_section . '

                <div class="section">
                    <h3>💬 Your Message</h3>
                    <div class="summary-box" style="white-space: pre-wrap;">' . esc_html($b['message']) . '</div>
                </div>

                <div class="note">
                    <strong>💡 Pro Tip:</strong> Save this email! You can reference inquiry <strong>' . esc_html($ctx['code']) . '</strong> in future communications with ' . esc_html($ctx['exporter_name']) . '.
                </div>
            </div>
            <div class="footer">
                <p>Submitted via <strong>' . esc_html($ctx['site_name']) . '</strong><br>
                <a href="' . esc_url($ctx['site_url']) . '" style="color:#007cba;">' . esc_html($ctx['site_url']) . '</a></p>
                <p style="margin-top:10px;font-size:11px;color:#aaa;">Your information is only shared with ' . esc_html($ctx['exporter_name']) . ' to respond to your inquiry. We do not sell or distribute your data.</p>
            </div>
        </body>
        </html>';
    }

    /**
     * ─────────────────────────────────────────────────────────────
     * ADMIN: COLUMNS
     * ─────────────────────────────────────────────────────────────
     */
    public function admin_columns($columns) {
        return [
            'cb'       => '<input type="checkbox" />',
            'ref'      => '🔖 Ref',
            'source'   => '📍 Source',
            'buyer'    => '👤 Buyer',
            'contact'  => '📧 Contact',
            'exporter' => '🏢 Supplier',
            'date'     => '📅 Date'
        ];
    }

        /**
         * Admin column content.
         *
         * @param mixed $column The column.
         * @param int $post_id The post id.
         */
        public function admin_column_content($column, $post_id) {
        switch ($column) {
            case 'ref':
                echo '<strong><a href="/wp-admin/post.php?post='. $post_id .'&action=edit' . esc_attr($email) . '">' . esc_html(get_post_meta($post_id, 'inquiry_code', true)) . '</a></strong>';
                break;
            case 'source':
                $src = get_post_meta($post_id, 'source', true);
                if ($src === 'exporter_profile') {
                    echo '<span style="background:#e3f2fd;color:#1976d2;padding:3px 8px;border-radius:3px;font-size:11px;">🏢 Profile</span>';
                } elseif ($src === 'product_page') {
                    echo '<span style="background:#e8f5e9;color:#388e3c;padding:3px 8px;border-radius:3px;font-size:11px;">📦 Product</span>';
                } 
                // ✅ ADD THIS NEW CASE:
                elseif ($src === 'contact_form') {
                    echo '<span style="background:#fff3e0;color:#ef6c00;padding:3px 8px;border-radius:3px;font-size:11px;">✉️ Contact</span>';
                } 
                else {
                    echo '—';
                }
                break;
            case 'buyer':
                $company = get_post_meta($post_id, 'company_name', true);
                $name = get_post_meta($post_id, 'buyer_name', true);
                echo $company ? esc_html($company) . '<br><small>' . esc_html($name) . '</small>' : esc_html($name);
                break;
            case 'contact':
                $email = get_post_meta($post_id, 'buyer_email', true);
                $country = get_post_meta($post_id, 'buyer_country', true);
                echo '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
                if ($country) echo '<br><small>🌍 ' . esc_html($country) . '</small>';
                break;
                
            // ✅ FIXED: Auto-detects User ID vs Post ID
            case 'exporter':
                $eid = get_post_meta($post_id, 'exporter_id', true);
                if ($eid) {
                    $name = '';
                    $link = '';

                    // 1. Try as WordPress User ID (from product forms)
                    $user = get_userdata($eid);
                    if ($user) {
                        $name = get_user_meta($eid, 'company_name', true) ?: $user->display_name;
                        $link = admin_url('user-edit.php?user_id=' . $eid);
                    } 
                    // 2. Fallback: Try as Supplier Post ID (from profile forms)
                    else {
                        $post = get_post($eid);
                        if ($post) {
                            $name = get_post_meta($eid, 'company_name', true) ?: $post->post_title;
                            $link = get_edit_post_link($eid);
                        }
                    }

                    if ($name && $link) {
                        echo esc_html($name);
                    } else {
                        echo 'N/A';
                    }
                } else {
                    echo 'N/A';
                }
                break;
        }
    }

    /**
     * ─────────────────────────────────────────────────────────────
     * ADMIN: METABOX
     * ─────────────────────────────────────────────────────────────
     */
    public function add_inquiry_metabox() {
        add_meta_box('awps_inquiry_details', '🔍 Full Inquiry Details', [$this, 'render_metabox'], 'inquiry', 'normal', 'high');
    }

        /**
         * Render metabox.
         *
         * @param WP_Post $post The post.
         */
        public function render_metabox($post) {
        $source = get_post_meta($post->ID, 'source', true);
        $exporter_id = get_post_meta($post->ID, 'exporter_id', true);
        
        
        // Auto-detect exporter name (User ID or Post ID)
        $exporter_name = 'N/A';
        $exporter_link = '#';
        if ($exporter_id) {
            // Try as WordPress User ID
            $user = get_userdata($exporter_id);
            if ($user) {
                $exporter_name = get_user_meta($exporter_id, 'company_name', true) ?: $user->display_name;
                $exporter_link = admin_url('user-edit.php?user_id=' . $exporter_id);
            } 
            // Fallback: Try as Supplier Post ID
            else {
                $supplier_post = get_post($exporter_id);
                if ($supplier_post) {
                    $exporter_name = get_post_meta($exporter_id, 'company_name', true) ?: $supplier_post->post_title;
                    $exporter_link = get_edit_post_link($exporter_id);
                }
            }
        }
        ?>
        <style>
            .inq-grid { display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:20px; }
            .inq-item { background:#f9f9f9; padding:12px; border-left:4px solid #007cba; border-radius:0 4px 4px 0; }
            .inq-label { font-weight:600; color:#555; font-size:12px; text-transform:uppercase; margin-bottom:4px; }
            .inq-section { margin:25px 0; padding-top:15px; border-top:1px solid #ddd; }
            .inq-section h4 { margin:0 0 10px 0; color:#007cba; }
        </style>
        
        <div class="inq-grid">
            <div class="inq-item"><div class="inq-label">Ref Code</div><div><?php echo esc_html(get_post_meta($post->ID, 'inquiry_code', true)); ?></div></div>
            <div class="inq-item">
    <div class="inq-label">Source</div>
    <div>
        <?php 
        if ($source === 'exporter_profile') {
            echo '<span style="background:#e3f2fd;color:#1976d2;padding:3px 8px;border-radius:3px;font-size:11px;">🏢 Profile</span>';
        } elseif ($source === 'product_page') {
            echo '<span style="background:#e8f5e9;color:#388e3c;padding:3px 8px;border-radius:3px;font-size:11px;">📦 Product</span>';
        } elseif ($source === 'contact_form') {
            echo '<span style="background:#fff3e0;color:#ef6c00;padding:3px 8px;border-radius:3px;font-size:11px;">✉️ Contact</span>';
        } else {
            echo '<span style="background:#f5f5f5;color:#666;padding:3px 8px;border-radius:3px;font-size:11px;">—</span>';
        }
        ?>
    </div>
</div>
            <div class="inq-item"><div class="inq-label">Buyer Name</div><div><?php echo esc_html(get_post_meta($post->ID, 'buyer_name', true)); ?></div></div>
            <div class="inq-item"><div class="inq-label">Buyer Email</div><div><a href="mailto:<?php echo esc_attr(get_post_meta($post->ID, 'buyer_email', true)); ?>"><?php echo esc_html(get_post_meta($post->ID, 'buyer_email', true)); ?></a></div></div>
            <div class="inq-item"><div class="inq-label">Buyer WhatsApp</div><div><?php 
                    $wa = get_post_meta($post->ID, 'buyer_whatsapp', true);
                    echo $wa ? '<a href="https://wa.me/' . esc_attr(preg_replace('/[^0-9]/', '', $wa)) . '" style="color:#25D366;" target="_blank">' . esc_html($wa) . ' 💬</a>' : '—';
                ?></div></div>
            <div class="inq-item"><div class="inq-label">Supplier</div><div><a href="<?php echo esc_url($exporter_link); ?>" style="color:#007cba;font-weight:600;"><?php echo esc_html($exporter_name); ?></a></div></div>
            <?php if ($source === 'product_page'): ?>
            <div class="inq-item"><div class="inq-label">Product</div><div><?php 
                $pid = get_post_meta($post->ID, 'product_id', true);
                echo $pid ? '<a href="' . esc_url(get_edit_post_link($pid)) . '" style="color:#007cba;font-weight:600;">' . esc_html(get_the_title($pid)) . '</a>' : '—';
            ?></div></div>
            <?php endif; ?>
        </div>
        <?php if ($source === 'product_page'): ?>
        <div class="inq-section">
            <h4>📦 Product & Shipping Details</h4>
            <div class="inq-grid">
                <div class="inq-item"><div class="inq-label">Company</div><div><?php echo esc_html(get_post_meta($post->ID, 'company_name', true)); ?></div></div>
                <div class="inq-item"><div class="inq-label">Country</div><div><?php echo esc_html(get_post_meta($post->ID, 'buyer_country', true)); ?></div></div>
                <div class="inq-item"><div class="inq-label">Destination Port</div><div><?php echo esc_html(get_post_meta($post->ID, 'buyer_destination_port', true)); ?></div></div>
                <div class="inq-item"><div class="inq-label">Shipment Type</div><div><?php echo esc_html(get_post_meta($post->ID, 'buyer_shipment_type', true)); ?></div></div>
                <div class="inq-item"><div class="inq-label">Quantity</div><div><?php echo esc_html(get_post_meta($post->ID, 'buyer_qty_formatted', true)); ?></div></div>
                
            </div>
        </div>
        <?php endif; ?>
        <div class="inq-section">
            <h4>💬 Message</h4>
            <div style="background:#fff;padding:15px;border:1px solid #ddd;border-radius:4px;">
                <?php echo wp_kses_post(wpautop(get_post_field('post_content', $post->ID))); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Remove quick edit.
     *
     * @param mixed $actions The actions.
     * @param WP_Post $post The post.
     */
    public function remove_quick_edit($actions, $post) {
        if ($post->post_type === 'inquiry') unset($actions['inline hide-if-no-js']);
        return $actions;
    }

    /**
     * ─────────────────────────────────────────────────────────────
     * ADMIN: FILTERS (Source + Supplier)
     * ─────────────────────────────────────────────────────────────
     */
    public function add_admin_filters($post_type) {
        if ($post_type !== 'inquiry') return;
        
        // Source Filter
        $current_source = isset($_GET['source']) ? sanitize_text_field($_GET['source']) : '';
        ?>
        <select name="source">
            <option value="">All Sources</option>
            <option value="exporter_profile" <?php selected($current_source, 'exporter_profile'); ?>>Supplier Profile</option>
            <option value="product_page" <?php selected($current_source, 'product_page'); ?>>Product Page</option>
        </select>
        <?php

        // Supplier/Supplier Filter
        $current_exporter = isset($_GET['exporter_id']) ? intval($_GET['exporter_id']) : 0;
        ?>
        <select name="exporter_id">
            <option value="">All Suppliers</option>
            <?php
            $exporters = get_posts([
                'post_type'      => 'supplier',
                'posts_per_page' => -1,
                'orderby'        => 'title',
                'order'          => 'ASC',
                'fields'         => 'ids'
            ]);
            foreach ($exporters as $eid) {
                $company_name = get_post_meta($eid, 'company_name', true) ?: get_the_title($eid);
                printf(
                    '<option value="%d" %s>%s</option>',
                    $eid,
                    selected($current_exporter, $eid, false),
                    esc_html($company_name)
                );
            }
            ?>
        </select>
        <?php
    }

    /**
     * Filter query by meta.
     *
     * @param WP_Query $query The query.
     */
    public function filter_query_by_meta($query) {
        if (!is_admin() || !$query->is_main_query()) return;
        if (!isset($_GET['post_type']) || $_GET['post_type'] !== 'inquiry') return;
        
        $meta_query = [];
        if (isset($_GET['source']) && $_GET['source'] !== '') {
            $meta_query[] = ['key' => 'source', 'value' => sanitize_text_field($_GET['source'])];
        }
        if (isset($_GET['exporter_id']) && $_GET['exporter_id'] > 0) {
            $meta_query[] = ['key' => 'exporter_id', 'value' => intval($_GET['exporter_id'])];
        }

        if (!empty($meta_query)) {
            $query->set('meta_query', $meta_query);
        }
    }

    /**
     * ─────────────────────────────────────────────────────────────
     * CSV EXPORT
     * ─────────────────────────────────────────────────────────────
     */
    public function add_export_button($which) {
        if ($which !== 'top') return;
        if (!isset($_GET['post_type']) || $_GET['post_type'] !== 'inquiry') return;
        
        $source = isset($_GET['source']) ? sanitize_text_field($_GET['source']) : '';
        $exporter_id = isset($_GET['exporter_id']) ? intval($_GET['exporter_id']) : 0;
        
        $url = add_query_arg([
            'export_inquiries' => '1',
            'source' => $source,
            'exporter_id' => $exporter_id,
            '_wpnonce' => wp_create_nonce('export_inquiries_nonce')
        ]);
        
        echo '<a href="' . esc_url($url) . '" class="button button-primary" style="margin-left:10px;">📥 Export CSV</a>';
    }

    /**
     * Handle export csv.
     */
    public function handle_export_csv() {
        if (!isset($_GET['export_inquiries'])) return;
        if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'export_inquiries_nonce')) {
            wp_die( esc_html__( 'Security check failed.', 'awps' ) );
        }
        if (!current_user_can('edit_posts')) {
            wp_die( esc_html__( 'Insufficient permissions.', 'awps' ) );
        }

        $source = isset($_GET['source']) ? sanitize_text_field($_GET['source']) : '';
        $exporter_id = isset($_GET['exporter_id']) ? intval($_GET['exporter_id']) : 0;

        $filename = 'inquiries-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, [
            __( 'Ref Code', 'awps' ), __( 'Source', 'awps' ), __( 'Company', 'awps' ), __( 'Name', 'awps' ),
            __( 'Email', 'awps' ), __( 'WhatsApp', 'awps' ), __( 'Country', 'awps' ), __( 'Port', 'awps' ),
            __( 'Shipment', 'awps' ), __( 'Quantity', 'awps' ), __( 'Message', 'awps' ), __( 'Supplier', 'awps' ),
            __( 'Date', 'awps' )
        ]);

        
        $args = [
            'post_type'      => 'inquiry',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => []
        ];
        if ($source) $args['meta_query'][] = ['key' => 'source', 'value' => $source];
        if ($exporter_id) $args['meta_query'][] = ['key' => 'exporter_id', 'value' => $exporter_id];
        
        $query = new \WP_Query($args);
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $id = get_the_ID();
                $eid = get_post_meta($id, 'exporter_id', true);
                $exporter_name = $eid ? get_post_meta($eid, 'company_name', true) : '';
                
                fputcsv($output, [
                    get_post_meta($id, 'inquiry_code', true),
                    get_post_meta($id, 'source', true),
                    get_post_meta($id, 'company_name', true),
                    get_post_meta($id, 'buyer_name', true),
                    get_post_meta($id, 'buyer_email', true),
                    get_post_meta($id, 'buyer_whatsapp', true), // ✅ ADD THIS
                    get_post_meta($id, 'buyer_country', true),
                    get_post_meta($id, 'buyer_destination_port', true),
                    get_post_meta($id, 'buyer_shipment_type', true),
                    get_post_meta($id, 'buyer_qty_formatted', true),
                    strip_tags(get_post_field('post_content', $id)),
                    // Supplier name logic...
                    (function() use ($id) {
                        $eid = get_post_meta($id, 'exporter_id', true);
                        if (!$eid) return '';
                        $user = get_userdata($eid);
                        if ($user) return get_user_meta($eid, 'company_name', true) ?: $user->display_name;
                        $post = get_post($eid);
                        return $post ? get_post_meta($eid, 'company_name', true) ?: $post->post_title : '';
                    })(),
                    get_the_date('Y-m-d H:i')
                ]);
            }
            wp_reset_postdata();
        }
        
        fclose($output);
        exit;
    }
}