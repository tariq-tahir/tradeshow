<?php
namespace AWPS\Suppliers;

class SupplierSync
{
    public function register()
    {
        add_action('set_user_role', [$this, 'on_user_role_change'], 10, 3);
        add_action('delete_user', [$this, 'on_user_delete']);
        add_action('profile_update', [$this, 'on_profile_update'], 10, 2);
    }

    public function on_user_role_change($user_id, $new_role, $old_roles)
    {
        $was_supplier = in_array('supplier', (array) $old_roles);
        $is_supplier  = ($new_role === 'supplier');

        if ($is_supplier && !$was_supplier) {
            $this->create_supplier_post($user_id);
        } elseif ($was_supplier && !$is_supplier) {
            $this->trash_supplier_post($user_id);
        }
    }

    public function on_profile_update($user_id, $old_user_data)
    {
        $old_roles = (array) $old_user_data->roles;
        $new_user  = get_user_by('ID', $user_id);
        $new_roles = (array) $new_user->roles;

        $was_supplier = in_array('supplier', $old_roles);
        $is_supplier  = in_array('supplier', $new_roles);

        if ($is_supplier && !$was_supplier) {
            $this->create_supplier_post($user_id);
        } elseif ($was_supplier && !$is_supplier) {
            $this->trash_supplier_post($user_id);
        }
    }

    public function on_user_delete($user_id)
    {
        $user = get_user_by('ID', $user_id);
        if ($user && in_array('supplier', (array) $user->roles)) {
            $this->trash_supplier_post($user_id);
        }
    }

    private function create_supplier_post($user_id)
    {
        // Prevent duplicates
        if ($this->get_supplier_post_by_user($user_id)) {
            return;
        }

        $user = get_user_by('ID', $user_id);
        if (!$user) {
            return;
        }

        $company_name = get_user_meta($user_id, 'company_name', true);
        if (!$company_name) {
            $company_name = $user->display_name ?: $user->user_login;
        }

        $post_id = wp_insert_post([
            'post_title'   => $company_name,
            'post_type'    => 'supplier',
            'post_status'  => 'publish',
            'post_author'  => $user_id,
            'post_content' => '',
        ]);

        if (!is_wp_error($post_id) && $post_id) {
            update_post_meta($post_id, '_linked_user_id', $user_id);

            // ✅ NEW: Copy existing User Meta to Supplier CPT Post Meta
            $this->sync_user_meta_to_cpt($user_id, $post_id);
        }
    }

    /**
     * Copy all supplier-related user meta to CPT post meta
     */
    private function sync_user_meta_to_cpt($user_id, $post_id)
    {
        $fields_to_sync = [
            'company_logo', 'banner_image', 'company_name', 'address', 'state', 'city',
            'phone', 'website', 'facebook', 'instagram', 'linkedin', 'about_company',
            'contact_person', 'designation', 'whatsapp', 'skype', 'working_hours',
            'annual_capacity', 'lead_time', 'company_status', 'verified_supplier',
            'exports_to', 'languages', 'certifications', 'payment_terms', 'fob_ports', 'packaging'
        ];

        foreach ($fields_to_sync as $field) {
            $value = get_user_meta($user_id, $field, true);

            if (!empty($value)) {
                // Convert arrays to comma-separated strings for post meta consistency
                if (is_array($value)) {
                    $value = implode(',', $value);
                }

                update_post_meta($post_id, $field, $value);
            }
        }
    }

    private function trash_supplier_post($user_id)
    {
        $post = $this->get_supplier_post_by_user($user_id);
        if ($post && $post->post_status !== 'trash') {
            wp_trash_post($post->ID);
        }
    }

    public function get_supplier_post_by_user($user_id)
    {
        $posts = get_posts([
            'post_type'      => 'supplier',
            'post_status'    => 'any',
            'meta_query'     => [
                [
                    'key'   => '_linked_user_id',
                    'value' => $user_id,
                ]
            ],
            'numberposts'    => 1,
            'suppress_filters' => false,
        ]);

        return $posts ? $posts[0] : null;
    }


}