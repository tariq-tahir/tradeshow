<?php

namespace AWPS\Suppliers;

class SupplierProfile
{
    public function register()
    {
        add_action('add_meta_boxes', [$this, 'add_supplier_meta_box']);
        add_action('save_post_supplier', [$this, 'save_supplier_profile'], 10, 2);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Enqueue JS/CSS for supplier profile meta box
     */
    public function enqueue_assets($hook)
    {
        // Only on supplier post edit/add screens
        if (!in_array($hook, ['post-new.php', 'post.php'])) {
            return;
        }

        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== 'supplier') {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_script('jquery-ui-autocomplete');

        wp_enqueue_script(
            'awps-admin-js',
            get_template_directory_uri() . '/assets/dist/js/admin.js',
            ['jquery', 'jquery-ui-autocomplete'],
            null,
            true
        );

        wp_enqueue_style(
            'awps-admin-css',
            get_template_directory_uri() . '/assets/dist/css/admin.css'
        );

        // Localize data for JS
        wp_localize_script('awps-admin-js', 'awpsAdmin', [
            'ajaxUrl'        => admin_url('admin-ajax.php'),
            'savedCity'      => '', // Will be set dynamically in render
            'cities'         => function_exists('get_state_cities') ? get_state_cities() : [],
            'countries'      => function_exists('get_countries') ? get_countries() : [],
            'languages'      => function_exists('get_languages') ? get_languages() : [],
            'paymentTerms'   => function_exists('get_payment_terms') ? get_payment_terms() : [],
            'packaging'      => function_exists('get_packaging_options') ? get_packaging_options() : [],
        ]);
    }

    /**
     * Add meta box to supplier CPT
     */
    public function add_supplier_meta_box()
    {
        add_meta_box(
            'supplier_profile_details',
            __('Supplier Profile Details', 'awps'),
            [$this, 'render_supplier_meta_box'],
            'supplier',
            'normal',
            'high'
        );
    }

    /**
     * Render the supplier profile meta box
     */
    public function render_supplier_meta_box($post)
    {
        wp_nonce_field('save_supplier_profile', 'supplier_profile_nonce');

        // Helper to get post meta
        $get = function ($key) use ($post) {
            return get_post_meta($post->ID, $key, true);
        };

        // Helper to normalize array meta (comma-separated)
        $get_array = function ($key) use ($get) { // ← Use $get, not $meta!
            $val = $get($key);
            return $val ? array_filter(array_map('trim', explode(',', $val))) : [];
        };

        // Basic fields
        $logo           = $get('company_logo');
        $banner         = $get('banner_image');
        $company_status = $get('company_status') ?: 'enabled';
        $verified       = $get('verified_supplier');
        $address        = $get('address');
        $state          = $get('state');
        $city           = $get('city');
        $phone          = $get('phone');
        $website        = $get('website');
        $facebook       = $get('facebook');
        $instagram      = $get('instagram');
        $linkedin       = $get('linkedin');
        $about_company  = $get('about_company');
        $contact_person = $get('contact_person');
        $designation    = $get('designation');
        $whatsapp       = $get('whatsapp');
        $skype          = $get('skype');
        $working_hours  = $get('working_hours') ?: '9AM–6PM PKT (GMT+5)';
        $capacity       = $get('annual_capacity');
        $lead_time      = $get('lead_time');

        // Array fields
        $exports_to     = $get_array('exports_to');
        $languages      = $get_array('languages');
        $certifications = $get_array('certifications');
        $payment_terms  = $get_array('payment_terms');
        $fob_ports      = $get_array('fob_ports');
        $packaging      = $get_array('packaging');

        // Options
        $states = ['Punjab', 'Sindh', 'Khyber Pakhtunkhwa', 'Balochistan', 'Islamabad Capital Territory', 'Gilgit-Baltistan', 'Azad Kashmir'];

        // Certifications (from 'certification' CPT)
        $cert_posts = get_posts([
            'post_type'      => 'certification',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC'
        ]);
        ?>
        <div class="supplier-profile">
            
            <!-- STATUS & VERIFICATION -->
            <p>
                <label>
                    <input type="radio" name="company_status" value="enabled" <?= checked($company_status, 'enabled', false); ?>>
                    <?= __('Enabled', 'awps'); ?>
                </label><br>
                <label>
                    <input type="radio" name="company_status" value="disabled" <?= checked($company_status, 'disabled', false); ?>>
                    <?= __('Disabled', 'awps'); ?>
                </label>
                <span class="description"><?= __('Disabled companies won’t appear in public directories.', 'awps'); ?></span>
            </p>

            <p>
                <label>
                    <input type="checkbox" name="verified_supplier" value="1" <?= checked($verified, '1', false); ?>>
                    <?= __('Verified Supplier', 'awps'); ?>
                </label>
                <span class="description"><?= __('Show “Verified” badge on profile & products.', 'awps'); ?></span>
            </p>
            <hr>

            <!-- LOGO & BANNER -->
            <table class="form-table">
                <tr>
                    <th><label><?= __('Company Logo', 'awps'); ?></label></th>
                    <td>
                        <div class="media-upload-wrap" data-field="company_logo">
                            <input type="hidden" name="company_logo" value="<?= esc_attr($logo); ?>">
                            <div class="preview">
                                <?php if ($logo): ?><img src="<?= esc_url($logo); ?>" style="max-width:150px;height:auto;"><?php endif; ?>
                            </div>
                            <button type="button" class="button media-upload-btn"><?= __('Upload Logo', 'awps'); ?></button>
                            <button type="button" class="button media-remove-btn" <?= $logo ? '' : 'style="display:none;"'; ?>><?= __('Remove', 'awps'); ?></button>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th><label><?= __('Banner Image', 'awps'); ?></label></th>
                    <td>
                        <div class="media-upload-wrap" data-field="banner_image">
                            <input type="hidden" name="banner_image" value="<?= esc_attr($banner); ?>">
                            <div class="preview">
                                <?php if ($banner): ?><img src="<?= esc_url($banner); ?>" style="max-width:300px;height:auto;"><?php endif; ?>
                            </div>
                            <button type="button" class="button media-upload-btn"><?= __('Upload Banner', 'awps'); ?></button>
                            <button type="button" class="button media-remove-btn" <?= $banner ? '' : 'style="display:none;"'; ?>><?= __('Remove', 'awps'); ?></button>
                        </div>
                    </td>
                </tr>
            </table>
            <hr>

            <!-- COMPANY INFO -->
            <p>
                <label><?= __('Address', 'awps'); ?><br>
                    <input type="text" name="address" value="<?= esc_attr($address); ?>" class="regular-text">
                </label>
            </p>
            <p>
                <label><?= __('State', 'awps'); ?><br>
                    <select name="state" id="state" class="regular-text">
                        <option value=""><?= __('— Select State —', 'awps'); ?></option>
                        <?php foreach ($states as $st): ?>
                            <option value="<?= esc_attr($st); ?>" <?= selected($state, $st, false); ?>><?= esc_html($st); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </p>
            <p>
                <label><?= __('City', 'awps'); ?><br>
                    <select name="city" id="city" class="regular-text">
                        <option value=""><?= __('— Select State First —', 'awps'); ?></option>
                    </select>
                </label>
            </p>
            <p>
                <label><?= __('Phone', 'awps'); ?><br>
                    <input type="text" name="phone" value="<?= esc_attr($phone); ?>" class="regular-text">
                </label>
            </p>
            <p>
                <label><?= __('Website', 'awps'); ?><br>
                    <input type="url" name="website" value="<?= esc_url($website); ?>" class="regular-text">
                </label>
            </p>
            <p>
                <label><?= __('Facebook', 'awps'); ?><br>
                    <input type="url" name="facebook" value="<?= esc_url($facebook); ?>" class="regular-text">
                </label>
            </p>
            <p>
                <label><?= __('Instagram', 'awps'); ?><br>
                    <input type="url" name="instagram" value="<?= esc_url($instagram); ?>" class="regular-text">
                </label>
            </p>
            <p>
                <label><?= __('LinkedIn', 'awps'); ?><br>
                    <input type="url" name="linkedin" value="<?= esc_url($linkedin); ?>" class="regular-text">
                </label>
            </p>
            <p>
                <label><?= __('About Company', 'awps'); ?><br>
                    <textarea name="about_company" rows="5" cols="50"><?= esc_textarea($about_company); ?></textarea>
                </label>
            </p>
            <hr>

            <!-- EXPORTS & OPERATIONS -->
            <p>
                <label><?= __('Exports To', 'awps'); ?></label><br>
                <div class="custom-tags-input">
                    <input type="text" id="exports-to-input" class="tag-input" placeholder="<?= esc_attr__('Start typing country name...', 'awps'); ?>">
                    <div id="exports-to-list" class="tag-list">
                        <?php foreach ($exports_to as $country): ?>
                            <span class="tag" data-value="<?= esc_attr($country); ?>"><?= esc_html($country); ?> <span class="remove-tag">×</span></span>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="exports_to" value="<?= esc_attr(implode(',', $exports_to)); ?>">
                </div>
                <span class="description"><?= __('Press Enter or select from dropdown to add.', 'awps'); ?></span>
            </p>

            <p>
                <label><?= __('Annual Capacity', 'awps'); ?><br>
                    <input type="text" name="annual_capacity" value="<?= esc_attr($capacity); ?>" class="regular-text" placeholder="<?= esc_attr__('e.g., 10,000 Tons', 'awps'); ?>">
                </label>
            </p>

            <p>
                <label><?= __('Payment Terms', 'awps'); ?></label><br>
                <div class="custom-tags-input">
                    <input type="text" id="payment-terms-input" class="tag-input" placeholder="<?= esc_attr__('Type payment term and press Enter or Comma', 'awps'); ?>">
                    <div id="payment-terms-list" class="tag-list">
                        <?php foreach ($payment_terms as $term): ?>
                            <span class="tag" data-value="<?= esc_attr($term); ?>"><?= esc_html($term); ?> <span class="remove-tag">×</span></span>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="payment_terms" value="<?= esc_attr(implode(',', $payment_terms)); ?>">
                </div>
                <span class="description"><?= __('Press Enter or Comma after each term.', 'awps'); ?></span>
            </p>

            <p>
                <label><?= __('Languages Spoken', 'awps'); ?></label><br>
                <div class="custom-tags-input">
                    <input type="text" id="languages-input" class="tag-input" placeholder="<?= esc_attr__('Start typing language...', 'awps'); ?>">
                    <div id="languages-list" class="tag-list">
                        <?php foreach ($languages as $lang): ?>
                            <span class="tag" data-value="<?= esc_attr($lang); ?>"><?= esc_html($lang); ?> <span class="remove-tag">×</span></span>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="languages" value="<?= esc_attr(implode(',', $languages)); ?>">
                </div>
            </p>


            <p>
                <label><?= __('Lead Time', 'awps'); ?><br>
                    <input type="text" name="lead_time" value="<?= esc_attr($lead_time); ?>" class="regular-text" placeholder="<?= esc_attr__('e.g., 15–20 Days', 'awps'); ?>">
                </label>
            </p>

            <p>
                <label><?= __('Packaging Options', 'awps'); ?></label><br>
                <div class="custom-tags-input">
                    <input type="text" id="packaging-input" class="tag-input" placeholder="<?= esc_attr__('Type packaging option and press Enter or Comma', 'awps'); ?>">
                    <div id="packaging-list" class="tag-list">
                        <?php foreach ($packaging as $pkg): ?>
                            <span class="tag" data-value="<?= esc_attr($pkg); ?>"><?= esc_html($pkg); ?> <span class="remove-tag">×</span></span>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="packaging" value="<?= esc_attr(implode(',', $packaging)); ?>">
                </div>
            </p>
            <hr>

            <!-- CONTACT PERSON -->
            <p>
                <label><?= __('Contact Person', 'awps'); ?><br>
                    <input type="text" name="contact_person" value="<?= esc_attr($contact_person); ?>" class="regular-text">
                </label>
            </p>
            <p>
                <label><?= __('Designation', 'awps'); ?><br>
                    <input type="text" name="designation" value="<?= esc_attr($designation); ?>" class="regular-text">
                </label>
            </p>
            <p>
                <label><?= __('WhatsApp', 'awps'); ?><br>
                    <input type="text" name="whatsapp" value="<?= esc_attr($whatsapp); ?>" class="regular-text">
                </label>
            </p>
            <p>
                <label><?= __('Skype', 'awps'); ?><br>
                    <input type="text" name="skype" value="<?= esc_attr($skype); ?>" class="regular-text">
                </label>
            </p>
            <p>
                <label><?= __('Working Hours', 'awps'); ?><br>
                    <input type="text" name="working_hours" value="<?= esc_attr($working_hours); ?>" class="regular-text" placeholder="<?= esc_attr__('9AM–6PM PKT (GMT+5)', 'awps'); ?>">
                </label>
            </p>
            

        </div>
        <?php
    }


    /**
 * Save supplier profile meta
 */
public function save_supplier_profile($post_id, $post)
{   

    if (!isset($_POST['supplier_profile_nonce']) || !wp_verify_nonce($_POST['supplier_profile_nonce'], 'save_supplier_profile')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // TEXT FIELDS (single-line inputs - sanitize_text_field strips line breaks)
    $text_fields = [
        'company_logo', 'banner_image', 'address', 'state', 'city', 'phone',
        'website', 'facebook', 'instagram', 'linkedin',
        'contact_person', 'designation', 'whatsapp', 'skype', 'working_hours',
        'annual_capacity', 'lead_time'
    ];

    foreach ($text_fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field(wp_unslash($_POST[$field])));
        }
    }

    // TEXTAREA FIELDS (preserve line breaks with sanitize_textarea_field)
    $textarea_fields = ['about_company'];
    
    foreach ($textarea_fields as $field) {
        if (isset($_POST[$field])) {
            // sanitize_textarea_field() preserves \n characters
            update_post_meta($post_id, $field, sanitize_textarea_field(wp_unslash($_POST[$field])));
        }
    }

    // Array fields (stored as comma-separated strings)
    $array_fields = ['exports_to', 'languages', 'certifications', 'payment_terms', 'fob_ports', 'packaging'];
    foreach ($array_fields as $field) {
        $values = isset($_POST[$field]) ? array_filter(array_map('trim', explode(',', wp_unslash($_POST[$field])))) : [];
        update_post_meta($post_id, $field, implode(',', $values));
    }

    // Special fields
    update_post_meta($post_id, 'company_status', sanitize_text_field(wp_unslash($_POST['company_status'] ?? 'enabled')));
    update_post_meta($post_id, 'verified_supplier', isset($_POST['verified_supplier']) ? '1' : '0');
}

}