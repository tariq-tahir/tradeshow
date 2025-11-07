<?php
namespace AWPS\Exporters;

class Dashboard {
    public function register() {
        add_action('init', [$this, 'handle_profile_save']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_dashboard_assets']);

        
        add_action('wp_ajax_get_certifications', [$this, 'get_certifications']);
        add_action('wp_ajax_nopriv_get_certifications', [$this, 'get_certifications']);
    }

    public function enqueue_dashboard_assets() {
        if (is_page('dashboard')) {
            wp_enqueue_media();
            wp_enqueue_script('jquery-ui-autocomplete'); // ← Make sure this is here
            wp_enqueue_script(
                'awps-frontend-scripts',
                get_template_directory_uri() . '/assets/dist/js/app.js',
                ['jquery'],
                wp_get_theme()->get('Version'),
                true
            );
        }
    }

    public function handle_profile_save() {

        if (
            isset($_POST['awps_exporter_profile_nonce']) &&
            wp_verify_nonce($_POST['awps_exporter_profile_nonce'], 'awps_exporter_profile')
        ) {
            $u = get_current_user_id();

            // Handle image uploads
            if (!empty($_FILES['company_logo_file']['name'])) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/media.php';
                require_once ABSPATH . 'wp-admin/includes/image.php';

                $attachment_id = media_handle_upload('company_logo_file', 0);
                if (!is_wp_error($attachment_id)) {
                    $url = wp_get_attachment_url($attachment_id);
                    update_user_meta($u, 'company_logo', esc_url_raw($url));
                }
            }

            if (!empty($_FILES['banner_image_file']['name'])) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/media.php';
                require_once ABSPATH . 'wp-admin/includes/image.php';

                $attachment_id = media_handle_upload('banner_image_file', 0);
                if (!is_wp_error($attachment_id)) {
                    $url = wp_get_attachment_url($attachment_id);
                    update_user_meta($u, 'banner_image', esc_url_raw($url));
                }
            }

            // Remove images if requested
            if (!empty($_POST['remove_company_logo'])) {
                delete_user_meta($u, 'company_logo');
            }
            if (!empty($_POST['remove_banner_image'])) {
                delete_user_meta($u, 'banner_image');
            }

            // Text fields (including new ones)
            $text_fields = [
                'company_name', 'address', 'state', 'city', 'phone', 'whatsapp',
                'annual_capacity', 'lead_time', 'contact_person', 'designation', 'skype', 'working_hours'
            ];
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
            $array_fields = ['exports_to', 'languages', 'certifications'];
            foreach ($array_fields as $field) {
                if (isset($_POST[$field])) {
                    $values = array_map('sanitize_text_field', (array)$_POST[$field]);
                    update_user_meta($u, $field, $values);
                }
            }

            // Custom tag fields (comma-separated)
            $tag_fields = ['payment_terms', 'fob_ports', 'packaging'];
            foreach ($tag_fields as $field) {
                if (isset($_POST[$field])) {
                    $values = array_filter(array_map('trim', explode(',', $_POST[$field])));
                    update_user_meta($u, $field, array_values($values));
                }
            }

            // In handle_profile_save()
            if (isset($_POST['certifications'])) {
                $values = array_filter(array_map('trim', explode(',', $_POST['certifications'])));
                // Remove duplicates
                $values = array_unique($values);
                update_user_meta($u, 'certifications', array_values($values));
            } else {
                delete_user_meta($u, 'certifications');
            }

            // ⚠️ DO NOT SAVE company_status or verified_supplier — admin only

            // Redirect with success
            wp_safe_redirect(add_query_arg('updated', '1', wp_get_referer()));
            exit;
        }
    }






    public function get_certifications() {
        $term = sanitize_text_field($_GET['term'] ?? '');
        $certs = get_posts([
            'post_type' => 'certification',
            'posts_per_page' => 10,
            's' => $term,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);

        // Remove duplicates by name
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
}