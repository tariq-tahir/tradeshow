<?php
/**
 * Mobile App REST API additions.
 *
 * 1. Exposes the supplier profile fields (already synced onto the `supplier`
 *    CPT by SupplierSync.php) through wp-json/wp/v2/supplier.
 * 2. Adds a nonce-free REST endpoint the Flutter app can POST inquiries/RFQs
 *    to directly, without needing a WordPress session or admin-ajax nonce.
 *
 * @package Awps\Api
 */

namespace Awps\Api;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if (!defined('ABSPATH')) exit;

class MobileApi
{
    const SUPPLIER_POST_TYPE = 'supplier';

    // Same field list SupplierSync.php copies from user meta onto the
    // supplier CPT's post meta. Keep this list in sync if new fields are added.
    const SUPPLIER_FIELDS = [
        'company_logo', 'banner_image', 'company_name', 'address', 'state', 'city',
        'phone', 'website', 'facebook', 'instagram', 'linkedin', 'about_company',
        'contact_person', 'designation', 'whatsapp', 'skype', 'working_hours',
        'annual_capacity', 'lead_time', 'company_status', 'verified_supplier',
        'exports_to', 'languages', 'certifications', 'payment_terms', 'fob_ports', 'packaging',
    ];

    public function register()
    {
        add_action('rest_api_init', [$this, 'register_supplier_rest_fields']);
        add_action('rest_api_init', [$this, 'register_inquiry_route']);
    }

    /**
     * ────────────────────────────────────────────────────────
     * 1. Expose supplier post meta to wp-json/wp/v2/supplier
     * ────────────────────────────────────────────────────────
     */
    public function register_supplier_rest_fields()
    {
        foreach (self::SUPPLIER_FIELDS as $field) {
            register_rest_field(self::SUPPLIER_POST_TYPE, $field, [
                'get_callback' => function ($post_arr) use ($field) {
                    $value = get_post_meta($post_arr['id'], $field, true);

                    // Booleans stored as 1/0 (e.g. verified_supplier)
                    if ($field === 'verified_supplier') {
                        return (bool) $value;
                    }

                    // Fields that were originally arrays get stored comma-separated —
                    // hand them back to the app as a clean array.
                    $comma_separated_fields = [
                        'exports_to', 'languages', 'certifications',
                        'payment_terms', 'fob_ports', 'packaging',
                    ];
                    if (in_array($field, $comma_separated_fields, true) && $value !== '') {
                        return array_values(array_filter(array_map('trim', explode(',', $value))));
                    }

                    return $value;
                },
                'schema' => [
                    'description' => sprintf('AWPS Supplier %s', $field),
                    'context'     => ['view', 'edit'],
                ],
            ]);
        }
    }

    /**
     * ────────────────────────────────────────────────────────
     * 2. POST /wp-json/awps/v1/inquiry
     *    Public, no nonce required — safe for the mobile app.
     * ────────────────────────────────────────────────────────
     */
    public function register_inquiry_route()
    {
        register_rest_route('awps/v1', '/inquiry', [
            'methods'             => 'POST',
            'callback'            => [$this, 'handle_inquiry'],
            'permission_callback' => '__return_true',
            'args'                => [
                'exporter_id' => ['required' => true],
                'name'        => ['required' => true],
                'email'       => ['required' => true],
                'message'     => ['required' => true],
            ],
        ]);
    }

    public function handle_inquiry(WP_REST_Request $request)
    {
        $params = $request->get_json_params();
        if (empty($params)) {
            $params = $request->get_params();
        }

        $exporter_id = intval($params['exporter_id'] ?? 0);
        $name        = sanitize_text_field($params['name'] ?? '');
        $email       = sanitize_email($params['email'] ?? '');
        $message     = sanitize_textarea_field($params['message'] ?? '');

        if (!$exporter_id || !$name || !$email || !$message) {
            return new WP_Error(
                'awps_missing_fields',
                'Please provide exporter_id, name, email, and message.',
                ['status' => 400]
            );
        }
        if (!is_email($email)) {
            return new WP_Error('awps_invalid_email', 'Please enter a valid email address.', ['status' => 400]);
        }

        // Optional fields — present for product-page RFQs, absent for a simple profile inquiry.
        $company          = sanitize_text_field($params['company'] ?? '');
        $whatsapp         = sanitize_text_field($params['whatsapp'] ?? '');
        $country          = sanitize_text_field($params['country'] ?? '');
        $destination_port = sanitize_text_field($params['destination_port'] ?? '');
        $shipment_type    = sanitize_text_field($params['shipment_type'] ?? '');
        $qty_value        = sanitize_text_field($params['qty_value'] ?? '');
        $qty_unit         = sanitize_text_field($params['qty_unit'] ?? '');
        $product_id       = intval($params['product_id'] ?? 0);

        $source = $product_id ? 'product_page' : 'exporter_profile';
        $title  = $company ? "{$company} — {$name}" : "{$name} — {$email}";

        $inquiry_id = wp_insert_post([
            'post_type'    => 'inquiry',
            'post_status'  => 'publish',
            'post_title'   => sanitize_text_field($title),
            'post_content' => $message,
        ]);

        if (!$inquiry_id || is_wp_error($inquiry_id)) {
            return new WP_Error('awps_save_failed', 'Failed to save inquiry.', ['status' => 500]);
        }

        $code = "TS" . date('y') . "-" . date('m') . "-" . strtoupper(substr(wp_generate_password(10, false, false), 0, 5));

        update_post_meta($inquiry_id, 'inquiry_code', $code);
        update_post_meta($inquiry_id, 'source', $source);
        update_post_meta($inquiry_id, 'company_name', $company);
        update_post_meta($inquiry_id, 'buyer_name', $name);
        update_post_meta($inquiry_id, 'buyer_email', $email);
        update_post_meta($inquiry_id, 'buyer_whatsapp', $whatsapp);
        update_post_meta($inquiry_id, 'buyer_country', $country);
        update_post_meta($inquiry_id, 'buyer_destination_port', $destination_port);
        update_post_meta($inquiry_id, 'buyer_shipment_type', $shipment_type);
        update_post_meta($inquiry_id, 'buyer_qty_value', $qty_value);
        update_post_meta($inquiry_id, 'buyer_qty_unit', $qty_unit);
        update_post_meta($inquiry_id, 'buyer_qty_formatted', trim("{$qty_value} {$qty_unit}"));
        update_post_meta($inquiry_id, 'buyer_message', $message);
        update_post_meta($inquiry_id, 'product_id', $product_id);
        update_post_meta($inquiry_id, 'exporter_id', $exporter_id);

        // Reuse the existing email logic in InquiryManager (see note below —
        // requires changing `protected function send_emails` to `public`).
        if (class_exists('\AWPS\Suppliers\InquiryManager')) {
            $manager = new \AWPS\Suppliers\InquiryManager();
            if (method_exists($manager, 'send_emails')) {
                $manager->send_emails($inquiry_id, $source === 'product_page' ? 'product' : 'profile');
            }
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Inquiry sent successfully!',
            'code'    => $code,
        ], 200);
    }
}