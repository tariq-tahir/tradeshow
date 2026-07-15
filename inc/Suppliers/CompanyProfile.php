<?php
namespace AWPS\Suppliers;

class CompanyProfile
{
    public function register()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_dashboard_assets']);
    }

    public function enqueue_dashboard_assets()
    {
        // You can enqueue frontend JS/CSS here if needed
    }

        /**
     * Save exporter profile data to CPT post meta + enforce visibility rules + sync company_status
     *
     * @param int $post_id The ID of the exporter CPT post
     */
    public function save_profile($post_id)
    {

    
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Get company name for dynamic file naming
        $company_name = !empty($_POST['company_name'])
            ? sanitize_title($_POST['company_name'])
            : 'company-' . $post_id;

        // Find Dashboard page ID for media attachment parent
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
            $existing_url = get_post_meta($post_id, $keys['meta_key'], true);
            $existing_id = $existing_url ? attachment_url_to_postid($existing_url) : 0;
            $attachment_id = $existing_id;

            // --- STEP 1: UPLOAD NEW IMAGE ---
            if (!empty($_FILES[$keys['file_key']]['name'])) {
                $new_filename = $company_name . '-' . $keys['suffix'];

                $name_filter = function ($file) use ($new_filename) {
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $file['name'] = $new_filename . '.' . ($ext ?: 'png');
                    return $file;
                };
                add_filter('wp_handle_upload_prefilter', $name_filter);

                $uploaded_id = media_handle_upload($keys['file_key'], $parent_id);
                remove_filter('wp_handle_upload_prefilter', $name_filter);

                if (!is_wp_error($uploaded_id)) {
                    if ($existing_id) {
                        wp_delete_attachment($existing_id, true);
                    }
                    $url = wp_get_attachment_url($uploaded_id);
                    update_post_meta($post_id, $keys['meta_key'], esc_url_raw($url));
                    wp_update_post(['ID' => $uploaded_id, 'post_title' => str_replace('-', ' ', $new_filename)]);
                    $attachment_id = $uploaded_id;
                }
            }

            // --- STEP 2: MANUAL REMOVAL ---
            if (!empty($_POST['remove_' . $keys['meta_key']]) && empty($_FILES[$keys['file_key']]['name'])) {
                if ($existing_id) {
                    wp_delete_attachment($existing_id, true);
                }
                delete_post_meta($post_id, $keys['meta_key']);
                $attachment_id = 0;
            }

            // --- STEP 3: ALT TEXT ---
            if ($attachment_id && isset($_POST[$keys['alt_key']])) {
                update_post_meta($attachment_id, '_wp_attachment_image_alt', sanitize_text_field($_POST[$keys['alt_key']]));
            }
        }

        // TEXT FIELDS
        $text_fields = ['company_name', 'address', 'state', 'city', 'phone', 'whatsapp', 'annual_capacity', 'lead_time', 'contact_person', 'designation', 'skype', 'working_hours'];
        foreach ($text_fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }

        // URL FIELDS
        $url_fields = ['website', 'facebook', 'instagram', 'linkedin'];
        foreach ($url_fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, esc_url_raw($_POST[$field]));
            }
        }

        // TEXTAREA
        if (isset($_POST['about_company'])) {
            update_post_meta($post_id, 'about_company', sanitize_textarea_field($_POST['about_company']));
        }

        // ARRAY FIELDS
        $array_fields = ['exports_to', 'languages', 'payment_terms', 'fob_ports', 'packaging'];
        foreach ($array_fields as $field) {
            if (isset($_POST[$field])) {
                $values = array_filter(array_map('trim', explode(',', $_POST[$field])));
                update_post_meta($post_id, $field, implode(',', array_unique($values)));
            }
        }

        
        
        // 1. Calculate completion percentage
        $completion = $this->calculate_profile_completion($post_id);
        update_post_meta($post_id, '_awps_profile_completion', $completion);
        
        // 2. Check frontend requirements
        $has_logo = !empty(get_post_meta($post_id, 'company_logo', true));
        $has_banner = !empty(get_post_meta($post_id, 'banner_image', true));
        $frontend_ready = ($completion >= 40 && $has_logo && $has_banner);
        
        
        // 3. Find linked Supplier CPT post
        $user_id = get_current_user_id();
        
        
        $supplier_post_id = $this->get_supplier_post_id_by_user($user_id);
        
        
        // 4. Sync company_status
        if ($supplier_post_id) {
            $new_status = $frontend_ready ? 'enabled' : 'disabled';
            $current_backend_status = get_post_meta($supplier_post_id, 'company_status', true) ?: 'enabled';
            
        
            
            if ($new_status !== $current_backend_status) {
                $updated = update_post_meta($supplier_post_id, 'company_status', $new_status);
                
                
                if ($updated) {
                
                }
            } else {
                
            }
        } else {
            
            // Try to debug why: check if meta key exists
            $check = get_posts([
                'post_type'   => 'supplier',
                'meta_query'  => [['key' => '_linked_user_id', 'value' => $user_id]],
                'fields'      => 'ids'
            ]);
            
        }
        
        

        // Redirect back with success
        $redirect_url = add_query_arg('updated', '1', wp_get_referer());
        wp_safe_redirect($redirect_url);
        exit;
    }

    /**
     * Calculate profile completion percentage (matches dashboard.php logic)
     * 
     * @param int $post_id Exporter CPT post ID
     * @return int Completion percentage (0-100)
     */
    private function calculate_profile_completion($post_id) {
        $fields = [
            // Images (critical)
            'company_logo'    => 2,
            'banner_image'    => 2,
            
            // Company Info
            'company_name'    => 2,
            'address'         => 1,
            'state'           => 1,
            'city'            => 1,
            'phone'           => 1,
            'website'         => 1,
            'facebook'        => 1,
            'instagram'       => 1,
            'linkedin'        => 1,
            
            // Export Details
            'exports_to'      => 1,  // array field
            'annual_capacity' => 1,
            'payment_terms'   => 1,  // array field
            'languages'       => 1,  // array field
            'lead_time'       => 1,
            'packaging'       => 1,  // array field
            
            // Contact Person
            'contact_person'  => 1,
            'designation'     => 1,
            'whatsapp'        => 1,
            'skype'           => 1,
            'working_hours'   => 1,
            
            // About
            'about_company'   => 2,
        ];
        
        $filled_weight = 0;
        $total_weight = 0;
        
        foreach ($fields as $field => $weight) {
            $total_weight += $weight;
            $value = get_post_meta($post_id, $field, true);
            
            // Ensure $value is a string before processing array fields
            if (is_array($value)) {
                $value = !empty($value) ? implode(',', $value) : '';
            }
            
            // For array/comma-separated fields
            if (in_array($field, ['exports_to', 'payment_terms', 'languages', 'packaging'])) {
                if (!empty($value) && count(array_filter(array_map('trim', explode(',', (string) $value)))) > 0) {
                    $filled_weight += $weight;
                }
            }
            // For text/textarea fields
            elseif (!empty($value) && (string) $value !== '0' && trim((string) $value) !== '') {
                $filled_weight += $weight;
            }
        }
        
        return $total_weight > 0 ? round(($filled_weight / $total_weight) * 100) : 0;
    }

    /**
     * AJAX handler for certification autocomplete (unchanged)
     */
    public function get_certifications()
    {
        $term = sanitize_text_field($_GET['term'] ?? '');
        $certs = get_posts([
            'post_type'      => 'certification',
            'posts_per_page' => 15,
            's'              => $term,
            'orderby'        => 'title',
            'order'          => 'ASC'
        ]);

        $seen = [];
        $results = [];
        foreach ($certs as $cert) {
            if (!isset($seen[$cert->post_title])) {
                $seen[$cert->post_title] = true;
                $results[] = [
                    'label' => $cert->post_title,
                    'value' => $cert->post_title
                ];
            }
        }
        wp_send_json($results);
    }


        /**
     * Get the Supplier CPT post ID linked to a WordPress user
     * 
     * @param int $user_id WordPress user ID
     * @return int Supplier post ID or 0 if not found
     */
    private function get_supplier_post_id_by_user($user_id) {
        $supplier_posts = get_posts([
            'post_type'      => 'supplier',
            'post_status'    => 'any',
            'meta_query'     => [
                [
                    'key'   => '_linked_user_id',
                    'value' => $user_id,
                ]
            ],
            'numberposts'    => 1,
            'fields'         => 'ids'
        ]);
        
        return !empty($supplier_posts) ? (int) $supplier_posts[0] : 0;
    }
}