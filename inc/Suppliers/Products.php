<?php
/**
 * Supplier Products Form Handler
 * Handles frontend product submissions and auto-publishing.
 * 
 * @package AWPS\Suppliers
 */

namespace AWPS\Suppliers;

class Products {
    
    /**
     * Register hooks for form handling
     */
    public function register() {
        add_action('init', [$this, 'handle_form']);
        add_action('admin_post_awps_save_product', [$this, 'handle_form']);
        add_action('admin_post_nopriv_awps_save_product', '__return_false');
    }

    /**
     * Calculate product completion percentage (for auto-publish logic)
     */
    private function calculate_product_completion($product_id) {
        $fields = [
            'post_title'                    => 2,
            'post_content'                  => 2,
            '_thumbnail_id'                 => 2,
            '_awps_product_hs_code'         => 1,
            '_awps_product_moq'             => 1,
            '_awps_product_lead_time'       => 1,
            '_awps_product_packaging'       => 1,
            '_awps_product_shipping'        => 1,
            '_awps_product_payment_terms'   => 1,
            '_awps_product_incoterms'       => 1,
            '_awps_product_key_benefits'    => 1,
            '_awps_product_quality_control' => 1,
            '_awps_product_sample_policies' => 1,
            '_awps_product_certifications'  => 1,
            '_awps_product_made_in_pakistan'=> 1,
            '_awps_product_factory_video'   => 1,
            'rank_math_title'               => 1,
            'rank_math_description'         => 1,
            'product_categories'            => 1,
            'featured_image_alt'            => 1,
            'gallery_images'                => 1,
        ];
        
        $filled_weight = 0;
        $total_weight = 0;
        
        foreach ($fields as $field => $weight) {
            $total_weight += $weight;
            
            // SPECIAL: Gallery images
            if ($field === 'gallery_images') {
                $gallery_ids = get_post_meta($product_id, '_awps_product_gallery', true);
                $has_gallery = !empty($gallery_ids) && count(array_filter(explode(',', $gallery_ids))) > 0;
                if ($has_gallery) $filled_weight += $weight;
                continue;
            }
            
            // SPECIAL: Featured Image Alt Text (stored on attachment)
            if ($field === 'featured_image_alt') {
                $thumb_id = get_post_thumbnail_id($product_id);
                $value = $thumb_id ? get_post_meta($thumb_id, '_wp_attachment_image_alt', true) : '';
                if (!empty($value) && trim((string) $value) !== '' && (string) $value !== '0') {
                    $filled_weight += $weight;
                }
                continue;
            }
            
            // SPECIAL: Made in Pakistan checkbox
            if ($field === '_awps_product_made_in_pakistan') {
                $value = get_post_meta($product_id, $field, true);
                if ($value === 'yes') {
                    $filled_weight += $weight;
                }
                continue;
            }
            
            // Regular field handling
            if ($field === 'post_title') {
                $value = get_the_title($product_id);
            } elseif ($field === 'post_content') {
                $value = get_post_field('post_content', $product_id);
            } elseif ($field === '_thumbnail_id') {
                $value = get_post_thumbnail_id($product_id) ? '1' : '';
            } else {
                $value = get_post_meta($product_id, $field, true);
                if (is_array($value)) $value = implode(',', $value);
            }
            
            if (!empty($value) && trim((string) $value) !== '' && (string) $value !== '0') {
                $filled_weight += $weight;
            }
        }
        
        return $total_weight > 0 ? round(($filled_weight / $total_weight) * 100) : 0;
    }

    /**
     * Handle frontend product form submission
     */
    public function handle_form() {
        // 1. Verify Nonce
        if (empty($_POST['awps_product_nonce']) || !wp_verify_nonce($_POST['awps_product_nonce'], 'awps_product')) {
            return;
        }

        // 2. Verify User Role
        $user = wp_get_current_user();
        if (!in_array('supplier', (array) $user->roles)) {
            return;
        }

        // 3. Verify Product Ownership
        $product_id = (int) ($_POST['product_id'] ?? 0);
        if ($product_id && get_post_field('post_author', $product_id) != $user->ID) {
            wp_die('You do not have permission to edit this product.');
        }

        // 4. Determine Initial Status (from checkbox)
        $status = isset($_POST['_product_visibility']) ? 'publish' : 'draft';

        // 5. Prepare Post Data
        $post_data = [
            'post_type'    => 'product',
            'post_status'  => $status,
            'post_title'   => sanitize_text_field($_POST['title'] ?? ''),
            'post_content' => wp_kses_post($_POST['description'] ?? ''),
            'post_author'  => $user->ID,
        ];

        if ($product_id) {
            $post_data['ID'] = $product_id;
            $pid = wp_update_post($post_data);
        } else {
            $pid = wp_insert_post($post_data);
        }

        if (!$pid || is_wp_error($pid)) {
            wp_die('Error saving product.');
        }
        
        // 6. Save Custom Meta Fields
        $meta_fields = [
            'made_in_pakistan', 'hs_code', 'moq', 'packaging',
            'lead_time', 'shipping', 'payment_terms', 'incoterms',
            'key_benefits', 'quality_control', 'sample_policies',
            'certifications', 'factory_video'
        ];

        foreach ($meta_fields as $field) {
            $meta_key = '_awps_product_' . $field;
            
            if ($field === 'made_in_pakistan') {
                $value = (!empty($_POST[$field]) && $_POST[$field] === 'yes') ? 'yes' : 'no';
                update_post_meta($pid, $meta_key, $value);
            } elseif ($field === 'certifications') {
                $raw = $_POST['_' . $field] ?? '';
                $ids = array_filter(array_map('intval', explode(',', $raw)));
                update_post_meta($pid, $meta_key, implode(',', $ids));
            } else {
                if (isset($_POST['_' . $field])) {
                    update_post_meta($pid, $meta_key, sanitize_textarea_field($_POST['_' . $field]));
                }
            }
        }

        // 7. Save Categories
        if (!empty($_POST['product_categories'])) {
            $category_ids = array_filter(array_map('intval', explode(',', $_POST['product_categories'])));
            $valid_cat_ids = [];
            foreach ($category_ids as $cat_id) {
                $term = get_term($cat_id, 'product_cat');
                if ($term && !is_wp_error($term)) $valid_cat_ids[] = $cat_id;
            }
            if (!empty($valid_cat_ids)) {
                wp_set_object_terms($pid, $valid_cat_ids, 'product_cat');
            }
        }

        // 8. Save Rank Math SEO
        if (defined('RANK_MATH_FILE')) {
            if (isset($_POST['rank_math_title'])) update_post_meta($pid, 'rank_math_title', sanitize_text_field($_POST['rank_math_title']));
            if (isset($_POST['rank_math_description'])) update_post_meta($pid, 'rank_math_description', sanitize_textarea_field($_POST['rank_math_description']));
        }

        // 9. Media Handling
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        // Featured Image
        $current_thumb = get_post_thumbnail_id($pid);
        if (isset($_POST['remove_featured_image']) && $_POST['remove_featured_image'] === '1') {
            if ($current_thumb) wp_delete_attachment($current_thumb, true);
            delete_post_thumbnail($pid);
            $current_thumb = null;
        }
        $featured_id = null;
        if (!empty($_FILES['featured_image_upload']['name'])) {
            $featured_id = media_handle_upload('featured_image_upload', $pid);
            if (is_wp_error($featured_id)) $featured_id = null;
        } elseif (!empty($_POST['featured_image_id'])) {
            $featured_id = intval($_POST['featured_image_id']);
        }
        if ($featured_id && $current_thumb && $featured_id !== $current_thumb) wp_delete_attachment($current_thumb, true);
        if ($featured_id) {
            set_post_thumbnail($pid, $featured_id);
            if (isset($_POST['featured_image_alt'])) update_post_meta($featured_id, '_wp_attachment_image_alt', sanitize_text_field($_POST['featured_image_alt']));
        }

        // Gallery
        $current_gallery_ids = !empty($_POST['gallery_image_id']) ? array_map('intval', $_POST['gallery_image_id']) : [];
        if (!empty($_POST['remove_gallery_ids'])) {
            $removed_ids = array_filter(array_map('intval', explode(',', $_POST['remove_gallery_ids'])));
            $current_gallery_ids = array_diff($current_gallery_ids, $removed_ids);
            foreach ($removed_ids as $rid) if ($rid > 0) wp_delete_attachment($rid, true);
        }
        if (!empty($_POST['gallery_image_alt']) && is_array($_POST['gallery_image_alt'])) {
            foreach ($_POST['gallery_image_alt'] as $img_id => $alt_text) {
                $img_id = intval($img_id);
                if ($img_id > 0) update_post_meta($img_id, '_wp_attachment_image_alt', sanitize_text_field($alt_text));
            }
        }
        if (!empty($_FILES['gallery_files']['name'][0])) {
            $original_files = $_FILES;
            foreach ($original_files['gallery_files']['name'] as $key => $value) {
                if ($original_files['gallery_files']['error'][$key] !== UPLOAD_ERR_OK) continue;
                $_FILES['temp_upload'] = [
                    'name' => $original_files['gallery_files']['name'][$key],
                    'type' => $original_files['gallery_files']['type'][$key],
                    'tmp_name' => $original_files['gallery_files']['tmp_name'][$key],
                    'error' => $original_files['gallery_files']['error'][$key],
                    'size' => $original_files['gallery_files']['size'][$key],
                ];
                $attachment_id = media_handle_upload('temp_upload', $pid);
                if (!is_wp_error($attachment_id)) {
                    $current_gallery_ids[] = $attachment_id;
                    if (isset($_POST['gallery_alt_new'][$key])) update_post_meta($attachment_id, '_wp_attachment_image_alt', sanitize_text_field($_POST['gallery_alt_new'][$key]));
                }
            }
            $_FILES = $original_files;
        }
        update_post_meta($pid, '_awps_product_gallery', implode(',', array_unique(array_filter($current_gallery_ids, fn($id) => $id > 0))));

        // 10. AUTO-PUBLISH LOGIC
        $completion = $this->calculate_product_completion($pid); // ✅ This line was causing the error
        update_post_meta($pid, '_awps_product_completion', $completion);
        
        $has_image = has_post_thumbnail($pid);
        $has_title = !empty(get_the_title($pid));
        $has_desc = !empty(get_post_field('post_content', $pid));
        $is_ready = ($completion >= 50 && $has_image && $has_title && $has_desc);
        
        $final_status = $is_ready ? 'publish' : 'draft';
        $current_status = get_post_status($pid);
        
        if ($final_status !== $current_status) {
            wp_update_post(['ID' => $pid, 'post_status' => $final_status]);
        }

        // 11. Redirect
        $dashboard_url = home_url('/dashboard/');
        $redirect = add_query_arg(['tab' => 'products', 'saved' => '1'], $dashboard_url);
        wp_safe_redirect($redirect);
        exit;
    }
}