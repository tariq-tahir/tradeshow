<?php
namespace AWPS\Suppliers;

class Dashboard {

    public function register() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_dashboard_assets']);
        
        // Profile: text-based certifications (for user meta)
        add_action('wp_ajax_get_certifications', [$this, 'get_certifications']);
        add_action('wp_ajax_nopriv_get_certifications', [$this, 'get_certifications']);

        // Product form: ID-based certifications (for post meta)
        add_action('wp_ajax_awps_search_certifications_by_id', [$this, 'search_certifications_by_id']);
    }

    public function enqueue_dashboard_assets() {
        // Only load on the actual Dashboard page (by slug)
        if (!is_page()) {
            return;
        }

        $dashboard_page = get_page_by_path('dashboard');
        if (!$dashboard_page || !is_page($dashboard_page->ID)) {
            return;
        }

        // Enqueue media uploader
        wp_enqueue_media();

        // jQuery UI Autocomplete
        wp_enqueue_script('jquery-ui-autocomplete');

        // Cropper.js
        wp_enqueue_script('cropper-js', 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js', [], '1.5.13', true);
        wp_enqueue_style('cropper-css', 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css', [], '1.5.13');

        // Main frontend script
        wp_enqueue_script(
            'awps-frontend-scripts',
            get_template_directory_uri() . '/assets/dist/js/app.js',
            ['jquery'],
            wp_get_theme()->get('Version'),
            true
        );

        // Prepare data for localization
        $user = wp_get_current_user();

        $safe_call = function($callable, $default = []) {
            return is_callable($callable) ? call_user_func($callable) : $default;
        };

        $exporter_data = [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'stateCities' => $safe_call('get_state_cities', []),
            'savedCity' => (string) get_user_meta($user->ID, 'city', true),
            'countries' => $safe_call('get_countries', []),
            'languages' => $safe_call('get_languages', []),
            'incoterms' => array_map(function($code, $desc) {
                return "$code — $desc";
            }, array_keys($safe_call('get_intoterms', [])), $safe_call('get_intoterms', [])),
            'packaging' => $safe_call('get_packaging_options', []),
            'shipping' => $safe_call('get_shipping_methods', []),
            'paymentTerms' => $safe_call('get_payment_terms', []),
            'samplePolicies' => $safe_call('get_sample_policies', []),
            'keyBenefits' => $safe_call('get_key_benefits_options', []),
            'qualityControl' => $safe_call('get_quality_control_options', []),
        ];

        wp_localize_script('awps-frontend-scripts', 'wpExporterData', $exporter_data);
    }

    /**
     * Save profile data (user meta)
     */
    public function save_profile($user_id) {
        $u = $user_id;
        $company_name = !empty($_POST['company_name']) ? sanitize_title($_POST['company_name']) : 'company-' . $u;
        $page = get_page_by_path('dashboard');
        $parent_id = $page ? $page->ID : 0;

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $image_map = [
            'logo' => [
                'file_key' => 'company_logo_file',
                'meta_key' => 'company_logo',
                'alt_key'  => 'company_logo_alt',
                'suffix'   => 'logo'
            ],
            'banner' => [
                'file_key' => 'banner_image_file',
                'meta_key' => 'banner_image',
                'alt_key'  => 'banner_image_alt',
                'suffix'   => 'banner'
            ]
        ];

        foreach ($image_map as $type => $keys) {
            $existing_url = get_user_meta($u, $keys['meta_key'], true);
            $existing_id = $existing_url ? attachment_url_to_postid($existing_url) : 0;
            $attachment_id = $existing_id;

            // Upload new image
            if (!empty($_FILES[$keys['file_key']]['name'])) {
                $new_filename = $company_name . '-' . $keys['suffix'];
                $name_filter = function($file) use ($new_filename) {
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $file['name'] = $new_filename . '.' . ($ext ? $ext : 'png');
                    return $file;
                };
                add_filter('wp_handle_upload_prefilter', $name_filter);

                $uploaded_id = media_handle_upload($keys['file_key'], $parent_id);
                remove_filter('wp_handle_upload_prefilter', $name_filter);

                if (!is_wp_error($uploaded_id)) {
                    if ($existing_id) {
                        wp_delete_attachment($existing_id, true);
                    }
                    $attachment_id = $uploaded_id;
                    $url = wp_get_attachment_url($attachment_id);
                    update_user_meta($u, $keys['meta_key'], esc_url_raw($url));

                    wp_update_post([
                        'ID'         => $attachment_id,
                        'post_title' => str_replace('-', ' ', $new_filename),
                    ]);
                }
            }

            // Manual removal (only if no new upload)
            if (!empty($_POST['remove_' . $keys['meta_key']]) && empty($_FILES[$keys['file_key']]['name'])) {
                if ($existing_id) {
                    wp_delete_attachment($existing_id, true);
                }
                delete_user_meta($u, $keys['meta_key']);
                $attachment_id = 0;
            }

            // Alt text
            if ($attachment_id && isset($_POST[$keys['alt_key']])) {
                update_post_meta($attachment_id, '_wp_attachment_image_alt', sanitize_text_field($_POST[$keys['alt_key']]));
            }
        }

        // Text fields
        $text_fields = ['company_name', 'address', 'state', 'city', 'phone', 'whatsapp', 'annual_capacity', 'lead_time', 'contact_person', 'designation', 'skype', 'working_hours'];
        foreach ($text_fields as $field) {
            if (isset($_POST[$field])) {
                update_user_meta($u, $field, sanitize_text_field($_POST[$field]));
            }
        }

        // URL fields
        $url_fields = ['website', 'facebook', 'instagram', 'linkedin'];
        foreach ($url_fields as $field) {
            if (isset($_POST[$field])) {
                update_user_meta($u, $field, esc_url_raw($_POST[$field]));
            }
        }

        // Textarea
        if (isset($_POST['about_company'])) {
            update_user_meta($u, 'about_company', sanitize_textarea_field($_POST['about_company']));
        }

        // Multi-select arrays
        $array_fields = ['exports_to', 'languages'];
        foreach ($array_fields as $field) {
            if (isset($_POST[$field])) {
                $values = array_map('sanitize_text_field', (array)$_POST[$field]);
                update_user_meta($u, $field, array_values(array_filter($values)));
            }
        }

        // Custom tag fields
        $tag_fields = ['payment_terms', 'fob_ports', 'packaging'];
        foreach ($tag_fields as $field) {
            if (isset($_POST[$field])) {
                $values = array_filter(array_map('trim', explode(',', $_POST[$field])));
                update_user_meta($u, $field, array_values(array_unique($values)));
            }
        }

        // Certifications (stored as text list in user meta)
        if (isset($_POST['certifications'])) {
            $certs = array_filter(array_map('trim', explode(',', $_POST['certifications'])));
            update_user_meta($u, 'certifications', array_values(array_unique($certs)));
        } else {
            delete_user_meta($u, 'certifications');
        }

        $redirect_url = add_query_arg('updated', '1', wp_get_referer());
        wp_safe_redirect($redirect_url);
        exit;
    }

    /**
     * For PROFILE: returns certification titles only (text-based autocomplete)
     */
    public function get_certifications() {
        $term = sanitize_text_field($_GET['term'] ?? '');
        $certs = get_posts([
            'post_type'      => 'certification',
            'posts_per_page' => 15,
            's'              => $term,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'post_status'    => 'publish'
        ]);

        $seen = [];
        $results = [];
        foreach ($certs as $cert) {
            if (!isset($seen[$cert->post_title])) {
                $seen[$cert->post_title] = true;
                $results[] = [
                    'label' => $cert->post_title,
                    'value' => $cert->post_title // ← intentional: text value for user meta
                ];
            }
        }
        wp_send_json($results);
    }

    /**
     * For PRODUCT FORMS: returns { value: ID, label: Title } for proper post meta linking
     */
    public function search_certifications_by_id() {
        $term = sanitize_text_field($_GET['term'] ?? '');
        $certs = get_posts([
            'post_type'      => 'certification',
            'posts_per_page' => 15,
            's'              => $term,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'post_status'    => 'publish'
        ]);

        $results = [];
        foreach ($certs as $cert) {
            $results[] = [
                'value' => (string) $cert->ID,      // Must be string ID
                'label' => $cert->post_title
            ];
        }
        wp_send_json($results);
    }
}