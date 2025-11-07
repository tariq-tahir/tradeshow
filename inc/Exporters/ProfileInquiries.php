<?php
namespace AWPS\Exporters;

if (!defined('ABSPATH')) exit;

class ProfileInquiries {

    public function register() {
        // AJAX handlers
        add_action('wp_ajax_send_exporter_inquiry', [$this, 'handle_inquiry']);
        add_action('wp_ajax_nopriv_send_exporter_inquiry', [$this, 'handle_inquiry']);
    }

    public function handle_inquiry() {
        // Verify nonce
        if (!check_ajax_referer('exporter_inquiry_nonce', 'security', false)) {
            wp_send_json_error(['message' => 'Security check failed.']);
        }

        // Get and sanitize fields
        $name        = sanitize_text_field($_POST['name'] ?? '');
        $email       = sanitize_email($_POST['email'] ?? '');
        $whatsapp    = sanitize_text_field($_POST['whatsapp'] ?? '');
        $message     = sanitize_textarea_field($_POST['message'] ?? '');
        $exporter_id = intval($_POST['exporter_id'] ?? 0);

        // Validate required fields
        if (!$name || !$email || !$message) {
            wp_send_json_error(['message' => 'Please fill Name, Email, and Message.']);
        }

        if (!is_email($email)) {
            wp_send_json_error(['message' => 'Please enter a valid email.']);
        }

        if (!$exporter_id) {
            wp_send_json_error(['message' => 'Exporter not found.']);
        }

        // Create inquiry post
        $title = $name . ' — ' . $email;
        $postarr = [
            'post_type'   => 'inquiry',
            'post_status' => 'publish',
            'post_title'  => wp_strip_all_tags($title),
            'post_content'=> $message,
        ];

        $inquiry_id = wp_insert_post($postarr);

        if (!$inquiry_id) {
            wp_send_json_error(['message' => 'Failed to save inquiry.']);
        }

        // Generate inquiry code
        $inquiry_code = 'TS' . date('y') . '-' . date('m') . '-' . strtoupper(substr(md5(rand()), 0, 5));

        // Save meta
        update_post_meta($inquiry_id, 'inquiry_code', $inquiry_code);
        update_post_meta($inquiry_id, 'buyer_name', $name);
        update_post_meta($inquiry_id, 'buyer_email', $email);
        update_post_meta($inquiry_id, 'buyer_whatsapp', $whatsapp);
        update_post_meta($inquiry_id, 'buyer_message', $message);
        update_post_meta($inquiry_id, 'exporter_id', $exporter_id);
        update_post_meta($inquiry_id, 'source', 'exporter_profile');

        // Send emails
        $this->email_exporter($inquiry_id, $name, $email, $whatsapp, $message, $exporter_id, $inquiry_code);
        $this->email_buyer($inquiry_id, $name, $email, $whatsapp, $message, $exporter_id, $inquiry_code);

        wp_send_json_success(['message' => '✅ Thank you! Your inquiry has been sent to the exporter.']);
    }

    protected function email_exporter($inquiry_id, $name, $email, $whatsapp, $message, $exporter_id, $inquiry_code) {
        $exporter_name = get_user_meta($exporter_id, 'company_name', true) ?: get_userdata($exporter_id)->display_name;
        $exporter_email = get_user_meta($exporter_id, 'contact_email', true) ?: get_option('admin_email');

        $subject = '📩 New Inquiry from ' . $name . ' — Ref ' . $inquiry_code;
        $body = "
            <p><strong>New Inquiry from:</strong> {$name}</p>
            <p><strong>Email:</strong> <a href='mailto:{$email}'>{$email}</a></p>
            " . ($whatsapp ? "<p><strong>WhatsApp:</strong> {$whatsapp}</p>" : "") . "
            <p><strong>Message:</strong><br>" . nl2br(esc_html($message)) . "</p>
            <p><strong>Inquiry Code:</strong> {$inquiry_code}</p>
            <p><small>Sent via TradeShow.pk exporter profile.</small></p>
        ";

        $headers = ['Content-Type: text/html; charset=UTF-8'];
        wp_mail($exporter_email, $subject, $body, $headers);
    }

    protected function email_buyer($inquiry_id, $name, $email, $whatsapp, $message, $exporter_id, $inquiry_code) {
        $exporter_name = get_user_meta($exporter_id, 'company_name', true) ?: get_userdata($exporter_id)->display_name;

        $subject = '✅ Your Inquiry to ' . $exporter_name . ' — Ref ' . $inquiry_code;
        $body = "
            <p>Dear " . esc_html($name) . ",</p>
            <p>Thank you! Your inquiry has been sent to <strong>" . esc_html($exporter_name) . "</strong>.</p>
            <p><strong>Inquiry Code:</strong> " . esc_html($inquiry_code) . "</p>
            <p><strong>Your Message:</strong><br>" . nl2br(esc_html($message)) . "</p>
            " . ($whatsapp ? "<p><strong>WhatsApp:</strong> " . esc_html($whatsapp) . "</p>" : "") . "
            <p>They will contact you soon via email or WhatsApp.</p>
            <p><small>— TradeShow.pk</small></p>
        ";

        $headers = ['Content-Type: text/html; charset=UTF-8'];
        wp_mail($email, $subject, $body, $headers);
    }
}