<?php
namespace AWPS\Suppliers;

class ProfileFields {
    public function register() {
        add_action('show_user_profile', [$this, 'fields']);
        add_action('edit_user_profile', [$this, 'fields']);
        add_action('user_new_form', [$this, 'new_user_fields']);

        add_action('personal_options_update', [$this, 'save']);
        add_action('edit_user_profile_update', [$this, 'save']);
        add_action('user_register', [$this, 'save_new_user']);

        add_action('admin_enqueue_scripts', function ($hook) {
            if ($hook === 'profile.php' || $hook === 'user-edit.php' || $hook === 'user-new.php') {
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
                    get_template_directory_uri() . '/assets/dist/css/admin.css',
                    [],
                    null
                );

                // Localize PHP → JS data
                $user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : get_current_user_id();
                $saved_city = get_user_meta($user_id, 'city', true);

                wp_localize_script('awps-admin-js', 'awpsAdmin', [
                    'ajaxUrl'        => admin_url('admin-ajax.php'),
                    'savedCity'      => $saved_city,
                    'cities'         => get_state_cities(),
                    'countries'      => get_countries(),
                    'languages'      => get_languages(),
                    'paymentTerms'   => get_payment_terms(),
                    'packaging'      => get_packaging_options(),
                ]);
            }
        });
    }

    /**
     * Edit User / Your Profile screens — only for users who already have the supplier role.
     */
    public function fields($user) {
        if (!in_array('supplier', (array) $user->roles)) return;
        $this->render($user->ID);
    }

    /**
     * Add New User screen — user doesn't exist yet, so there's no role to check yet.
     * $type is 'add-new-user' on single site (or 'add-existing-user' on multisite, which we skip).
     */
    public function new_user_fields($type) {
        if ($type !== 'add-new-user') return;
        $this->render(0);
    }

    /**
     * Shared renderer for both screens. $user_id is 0 when the user doesn't exist yet.
     */
    private function render($user_id) {
        // Helper to normalize arrays
        $normalize_meta_array = function ($uid, $key) {
            if (!$uid) return [];
            $val = get_user_meta($uid, $key, true);
            if (is_array($val)) {
                return array_values(array_filter(array_map('trim', $val)));
            } elseif (is_string($val)) {
                return array_values(array_filter(array_map('trim', explode(',', $val))));
            }
            return [];
        };

        // --- Load existing data (all empty defaults when $user_id is 0) ---
        $logo           = $user_id ? get_user_meta($user_id, 'company_logo', true) : '';
        $banner         = $user_id ? get_user_meta($user_id, 'banner_image', true) : '';
        $company_status = ($user_id ? get_user_meta($user_id, 'company_status', true) : '') ?: 'enabled';
        $verified       = $user_id ? get_user_meta($user_id, 'verified_supplier', true) : '';

        $state          = $user_id ? get_user_meta($user_id, 'state', true) : '';
        $city           = $user_id ? get_user_meta($user_id, 'city', true) : '';
        $capacity       = $user_id ? get_user_meta($user_id, 'annual_capacity', true) : '';
        $lead_time      = $user_id ? get_user_meta($user_id, 'lead_time', true) : '';
        $contact_person = $user_id ? get_user_meta($user_id, 'contact_person', true) : '';
        $designation    = $user_id ? get_user_meta($user_id, 'designation', true) : '';
        $whatsapp       = $user_id ? get_user_meta($user_id, 'whatsapp', true) : '';
        $skype          = $user_id ? get_user_meta($user_id, 'skype', true) : '';
        $working_hours  = ($user_id ? get_user_meta($user_id, 'working_hours', true) : '') ?: '9AM–6PM PKT (GMT+5)';

        $user_exports_to = $normalize_meta_array($user_id, 'exports_to');
        $user_languages  = $normalize_meta_array($user_id, 'languages');
        $user_certs      = $normalize_meta_array($user_id, 'certifications');
        $payment_terms   = $normalize_meta_array($user_id, 'payment_terms');
        $fob_ports       = $normalize_meta_array($user_id, 'fob_ports');
        $packaging       = $normalize_meta_array($user_id, 'packaging');

        $user = (object) ['ID' => $user_id];

        $cert_posts = get_posts([
            'post_type'      => 'certification',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC'
        ]);
        ?>
        <div class="supplier-profile">
            
            <h2>supplier Profile</h2>

            <!-- =======================
                 STATUS & VERIFICATION
                 ======================= -->
            <table class="form-table">
                <tr>
                    <th><label>Company Status</label></th>
                    <td>
                        <label><input type="radio" name="company_status" value="enabled" <?= checked($company_status, 'enabled', false); ?>> Enabled</label><br>
                        <label><input type="radio" name="company_status" value="disabled" <?= checked($company_status, 'disabled', false); ?>> Disabled</label>
                        <p class="description">Disabled companies won’t appear in public directories.</p>
                    </td>
                </tr>
                <tr>
                    <th><label>Verified Supplier</label></th>
                    <td>
                        <input type="checkbox" name="verified_supplier" value="1" <?= checked($verified, 1, false); ?>>
                        <span class="description">Show “Verified” badge on profile & products.</span>
                    </td>
                </tr>
            </table>
            <hr>

            <!-- =======================
                 LOGO & BANNER UPLOADERS
                 ======================= -->
            <table class="form-table">
                <tr>
                    <th><label for="company_logo">Company Logo</label></th>
                    <td>
                        <div class="media-upload-wrap" data-field="company_logo">
                            <input type="hidden" name="company_logo" value="<?= esc_attr($logo); ?>">
                            <div class="preview">
                                <?php if ($logo): ?><img src="<?= esc_url($logo); ?>" style="max-width:150px;height:auto;"><?php endif; ?>
                            </div>
                            <button type="button" class="button media-upload-btn">Upload Logo</button>
                            <button type="button" class="button media-remove-btn" <?= $logo ? '' : 'style="display:none;"'; ?>>Remove</button>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th><label for="banner_image">Banner Image</label></th>
                    <td>
                        <div class="media-upload-wrap" data-field="banner_image">
                            <input type="hidden" name="banner_image" value="<?= esc_attr($banner); ?>">
                            <div class="preview">
                                <?php if ($banner): ?><img src="<?= esc_url($banner); ?>" style="max-width:300px;height:auto;"><?php endif; ?>
                            </div>
                            <button type="button" class="button media-upload-btn">Upload Banner</button>
                            <button type="button" class="button media-remove-btn" <?= $banner ? '' : 'style="display:none;"'; ?>>Remove</button>
                        </div>
                    </td>
                </tr>
            </table>
            <hr>

            <!-- =======================
                 COMPANY INFO
                 ======================= -->
            <table class="form-table">
                <tr><th>Company Name</th>
                    <td><input name="company_name" value="<?= esc_attr(get_user_meta($user->ID,'company_name',true)); ?>" class="regular-text"></td></tr>
                <tr><th>Address</th>
                    <td><input name="address" value="<?= esc_attr(get_user_meta($user->ID,'address',true)); ?>" class="regular-text"></td></tr>
                <tr>
                    <th><label>State</label></th>
                    <td>
                        <select name="state" id="state" class="regular-text">
                            <option value="">— Select State —</option>
                            <?php
                            $states = ['Punjab','Sindh','Khyber Pakhtunkhwa','Balochistan','Islamabad Capital Territory','Gilgit-Baltistan','Azad Kashmir'];
                            foreach ($states as $st) {
                                echo '<option value="' . esc_attr($st) . '" ' . selected($state, $st, false) . '>' . esc_html($st) . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label>City</label></th>
                    <td>
                        <select name="city" id="city" class="regular-text">
                            <option value="">— Select State First —</option>
                        </select>
                    </td>
                </tr>
                <tr><th>Phone</th>
                    <td><input name="phone" value="<?= esc_attr(get_user_meta($user->ID,'phone',true)); ?>" class="regular-text"></td></tr>
                <tr><th>Website</th>
                    <td><input name="website" type="url" value="<?= esc_url(get_user_meta($user->ID,'website',true)); ?>" class="regular-text"></td></tr>
                <tr><th>Facebook</th>
                    <td><input name="facebook" type="url" value="<?= esc_url(get_user_meta($user->ID,'facebook',true)); ?>" class="regular-text"></td></tr>
                <tr><th>Instagram</th>
                    <td><input name="instagram" type="url" value="<?= esc_url(get_user_meta($user->ID,'instagram',true)); ?>" class="regular-text"></td></tr>
                <tr><th>LinkedIn</th>
                    <td><input name="linkedin" type="url" value="<?= esc_url(get_user_meta($user->ID,'linkedin',true)); ?>" class="regular-text"></td></tr>
                <!-- CORRECTED -->
                <tr>
                <th>About Company</th>
                <td>
                    <textarea name="about_company" rows="5" cols="50">
                    <?= esc_textarea(get_user_meta($user->ID, 'about_company', true)); ?>
                    </textarea>
                </td>
                </tr>
            </table>
            <hr>

            <!-- =======================
                 EXPORTS & OPERATIONS
                 ======================= -->
            <table class="form-table">
                <tr>
                    <th><label>Exports To</label></th>
                    <td>
                        <div class="custom-tags-input">
                            <input type="text" id="exports-to-input" class="tag-input" placeholder="Start typing country name...">
                            <div id="exports-to-list" class="tag-list">
                                <?php foreach ($user_exports_to as $country): ?>
                                    <span class="tag" data-value="<?= esc_attr($country); ?>"><?= esc_html($country); ?> <span class="remove-tag">×</span></span>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="exports_to" value="<?= esc_attr(implode(',', $user_exports_to)); ?>">
                            <p class="description">Press Enter or select from dropdown to add.</p>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th><label>Annual Capacity</label></th>
                    <td><input name="annual_capacity" value="<?= esc_attr($capacity); ?>" class="regular-text" placeholder="e.g., 10,000 Tons"></td>
                </tr>
                <tr>
                    <th><label>Payment Terms</label></th>
                    <td>
                        <div class="custom-tags-input">
                        <input type="text" id="payment-terms-input" class="tag-input" placeholder="Type payment term and press Enter or Comma">
                        <div id="payment-terms-list" class="tag-list">
                            <?php foreach ($payment_terms as $term): ?>
                            <span class="tag" data-value="<?= esc_attr($term); ?>"><?= esc_html($term); ?> <span class="remove-tag">×</span></span>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="payment_terms" value="<?= esc_attr(implode(',', $payment_terms)); ?>">
                        </div>
                        <p class="description">Press Enter or Comma after each term. Type to search.</p>
                    </td>
                </tr>

                <tr>
                    <th><label>Languages Spoken</label></th>
                    <td>
                        <div class="custom-tags-input">
                            <input type="text" id="languages-input" class="tag-input" placeholder="Start typing language...">
                            <div id="languages-list" class="tag-list">
                                <?php foreach ($user_languages as $lang): ?>
                                    <span class="tag" data-value="<?= esc_attr($lang); ?>"><?= esc_html($lang); ?> <span class="remove-tag">×</span></span>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="languages" value="<?= esc_attr(implode(',', $user_languages)); ?>">
                        </div>
                    </td>
                </tr>
               
                <tr>
                    <th><label>Lead Time</label></th>
                    <td><input name="lead_time" value="<?= esc_attr($lead_time); ?>" class="regular-text" placeholder="e.g., 15–20 Days"></td>
                </tr>
                <tr>
  <th><label>Packaging Options</label></th>
  <td>
    <div class="custom-tags-input">
      <input type="text" id="packaging-input" class="tag-input" placeholder="Type packaging option and press Enter or Comma">
      <div id="packaging-list" class="tag-list">
        <?php foreach ($packaging as $pkg): ?>
          <span class="tag" data-value="<?= esc_attr($pkg); ?>"><?= esc_html($pkg); ?> <span class="remove-tag">×</span></span>
        <?php endforeach; ?>
      </div>
      <input type="hidden" name="packaging" value="<?= esc_attr(implode(',', $packaging)); ?>">
    </div>
  </td>
</tr>

            </table>
            <hr>

            <!-- =======================
                 CONTACT PERSON
                 ======================= -->
            <table class="form-table">
                <tr><th><label>Contact Person</label></th><td><input name="contact_person" value="<?= esc_attr($contact_person); ?>" class="regular-text"></td></tr>
                <tr><th><label>Designation</label></th><td><input name="designation" value="<?= esc_attr($designation); ?>" class="regular-text"></td></tr>
                <tr><th><label>WhatsApp</label></th><td><input name="whatsapp" value="<?= esc_attr($whatsapp); ?>" class="regular-text"></td></tr>
                <tr><th><label>Skype</label></th><td><input name="skype" value="<?= esc_attr($skype); ?>" class="regular-text"></td></tr>
                <tr><th><label>Working Hours</label></th><td><input name="working_hours" value="<?= esc_attr($working_hours); ?>" class="regular-text" placeholder="9AM–6PM PKT (GMT+5)"></td></tr>
            </table>
            <hr>

            <!-- =======================
                 CERTIFICATIONS
                 ======================= -->
            <table class="form-table">
                <tr>
                    <th><label for="certifications">Certifications</label></th>
                    <td>
                        <div class="custom-tags-input">
                            <input type="text" id="certifications-input" class="tag-input" placeholder="Start typing certification name...">
                            <div id="certifications-list" class="tag-list">
                                <?php foreach ($user_certs as $cert_id):
                                    $cert = get_post($cert_id);
                                    if ($cert): ?>
                                        <span class="tag" data-value="<?= esc_attr($cert_id); ?>"><?= esc_html($cert->post_title); ?> <span class="remove-tag">×</span></span>
                                    <?php endif;
                                endforeach; ?>
                            </div>
                            <input type="hidden" name="certifications" value="<?= esc_attr(implode(',', $user_certs)); ?>">
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    /**
     * Fires right after a new user is created via Add New User.
     * WordPress creates the account before this hook, so $user_id is valid here.
     */
    public function save_new_user($user_id) {
        // Only run for admin-created accounts on our Add New User screen submission,
        // not front-end self-registration or other user_register callers.
        if (!is_admin() || !current_user_can('create_users')) return;
        if (!isset($_POST['company_status']) && !isset($_POST['company_name'])) return;

        $this->save($user_id);
    }

    public function save($user_id) {
        if (!current_user_can('edit_user', $user_id) && !current_user_can('create_users')) return;

        $fields = [
            'company_logo', 'banner_image', 'company_name', 'address', 'state', 'city', 'phone',
            'website', 'facebook', 'instagram', 'linkedin', 
            'contact_person', 'designation', 'whatsapp', 'skype', 'working_hours',
            'annual_capacity', 'lead_time'
        ];


        $textarea_fields = ['about_company']; // Fields needing line break preservation

        foreach ($fields as $f) {
            if (isset($_POST[$f])) {
                update_user_meta($user_id, $f, sanitize_text_field($_POST[$f]));
            }
        }

        // Save textarea fields WITH line breaks preserved
        foreach ($textarea_fields as $f) {
            if (isset($_POST[$f])) {
                // sanitize_textarea_field() preserves \n characters (WP 4.7+)
                update_user_meta($user_id, $f, sanitize_textarea_field($_POST[$f]));
            }
        }

        $custom_fields = ['payment_terms', 'fob_ports', 'packaging', 'exports_to', 'languages', 'certifications'];
        foreach ($custom_fields as $f) {
            $vals = array_filter(array_map('trim', explode(',', $_POST[$f] ?? '')));
            update_user_meta($user_id, $f, $vals);
        }

        update_user_meta($user_id, 'company_status', sanitize_text_field($_POST['company_status'] ?? 'enabled'));
        update_user_meta($user_id, 'verified_supplier', isset($_POST['verified_supplier']) ? 1 : 0);
    }
}