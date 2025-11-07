<?php
namespace AWPS\Exporters;

class ProfileFields {
    public function register() {
        add_action('show_user_profile', [$this,'fields']);
        add_action('edit_user_profile', [$this,'fields']);
        add_action('personal_options_update', [$this,'save']);
        add_action('edit_user_profile_update', [$this,'save']);
        add_action('admin_enqueue_scripts', function($hook) {
            if ($hook === 'profile.php' || $hook === 'user-edit.php') {
                wp_enqueue_media();
                wp_enqueue_script(
                    'awps-admin-scripts',
                    get_template_directory_uri() . '/assets/dist/js/app.js',
                    ['jquery'],
                    null,
                    true
                );
                // Enqueue jQuery UI for autocomplete
                wp_enqueue_script('jquery-ui-autocomplete');
                add_action('admin_footer', [$this, 'custom_fields_js']);
            }
        });
    }

    public function custom_fields_js() {
       global $pagenow;
        $user_id = 0;
        if ($pagenow === 'profile.php') {
            $user_id = get_current_user_id();
        } elseif ($pagenow === 'user-edit.php' && isset($_GET['user_id'])) {
            $user_id = (int) $_GET['user_id'];
        }
        $saved_city = esc_js(get_user_meta($user_id, 'city', true));
        ?>
        <script>
        jQuery(document).ready(function($) {
            const savedCity = "<?= $saved_city ?>"; // Saved city from DB

            // ---------------------------
            // Generic Tag Widget (Copied/Adapted from index.php)
            // ---------------------------
            function setupTagWidget(opts) {
              try {
                const $input = $(opts.inputSelector);
                const $list  = $(opts.listSelector);
                const $hidden = $(opts.hiddenSelector);
                if ($input.length === 0 || $list.length === 0 || $hidden.length === 0) return;

                function getCurrentValues() {
                  return $list.find('.tag').toArray().map(function(el){
                    return $(el).attr('data-value') ? $(el).attr('data-value').toString() : $(el).clone().children().remove().end().text().trim();
                  });
                }

                function updateHidden() {
                  const vals = getCurrentValues();
                  $hidden.val(vals.join(','));
                }

                function addTag(value) {
                  value = String(value || '').trim();
                  if (!value) return;
                  const current = getCurrentValues();
                  if (current.indexOf(value) !== -1) return;
                  const $tag = $('<span/>', { class: 'tag', 'data-value': value }).text(value + ' ');
                  const $remove = $('<span/>', { class: 'remove-tag' }).text('×');
                  $remove.on('click', function(e){
                    e.preventDefault();
                    $tag.remove();
                    updateHidden();
                  });
                  $tag.append($remove);
                  $list.append($tag);
                  updateHidden();
                }

                // initialization:
                // if server already printed tags (DOM contains .tag), ensure they have data-value
                if ($list.find('.tag').length > 0) {
                  $list.find('.tag').each(function(){
                    const $t = $(this);
                    if (!$t.attr('data-value')) {
                      // derive data-value from inner text (strip remove button)
                      const text = $t.clone().children().remove().end().text().trim();
                      $t.attr('data-value', text);
                    }
                  });
                  updateHidden();
                } else {
                  // populate from hidden CSV if present
                  const raw = ($hidden.val() || '').toString();
                  const arr = raw.split(',').map(function(t){ return t.trim(); }).filter(function(t){ return t.length > 0; });
                  $list.empty();
                  $hidden.val('');
                  arr.forEach(function(v){ addTag(v); });
                }

                // autocomplete
                if (opts.sourceType === 'ajax' && opts.ajaxAction && $.ui && $.ui.autocomplete) {
                  $input.autocomplete({
                    source: function(request, response) {
                      $.ajax({
                        url: '<?= esc_js(admin_url("admin-ajax.php")); ?>',
                        dataType: 'json',
                        data: {
                          action: opts.ajaxAction,
                          term: request.term
                        },
                        success: function(data){ response(data || []); },
                        error: function(){ response([]); }
                      });
                    },
                    focus: function(){ return false; },
                    select: function(event, ui) {
                      // ui.item may be string or object; attempt to get sensible display value
                      var val = (ui && ui.item && (ui.item.value || ui.item.label)) || ui.item || '';
                      addTag(val);
                      $input.val('');
                      return false;
                    }
                  });
                } else if (opts.sourceType === 'array' && Array.isArray(opts.sourceArray) && $.ui && $.ui.autocomplete) {
                  const arr = opts.sourceArray || [];
                  $input.autocomplete({
                    source: function(request, response) {
                      const term = (request.term || '').toLowerCase();
                      const results = arr.filter(function(item){
                        return String(item).toLowerCase().indexOf(term) !== -1;
                      });
                      response(results);
                    },
                    focus: function(){ return false; },
                    select: function(event, ui) {
                      const val = (ui && ui.item && (ui.item.value || ui.item.label)) || ui.item || '';
                      addTag(val);
                      $input.val('');
                      return false;
                    }
                  });
                }

                // allow Enter/Comma to create tags
                if (opts.allowManualEntry) {
                  $input.on('keydown', function(e){
                    if (e.key === 'Enter' || e.key === ',') {
                      e.preventDefault();
                      const v = $input.val().trim();
                      if (v) addTag(v);
                      $input.val('');
                    }
                  });
                }
              } catch (err) {
                console.error('setupTagWidget error', err);
              }
            }

            // ---------------------------
            // State → City Filtering
            // ---------------------------
            function updateCities() {
                const state = $('#state').val();
                const citySelect = $('#city');
                const cities = <?= json_encode(get_state_cities()); ?>;
                citySelect.empty();
                if (state && cities[state]) {
                    cities[state].forEach(city => {
                        const option = new Option(city, city, false, city === savedCity);
                        citySelect.append(option);
                    });
                } else {
                    citySelect.append(new Option('— Select State First —', ''));
                }
            }
            $('#state').on('change', function() {
                // Clear saved city when state changes manually
                updateCities();
            });
            updateCities(); // init on load

            // ---------------------------
            // Initialize Autocomplete Widgets for Exports, Languages, Certifications
            // ---------------------------

            // Certifications (AJAX source)
            setupTagWidget({
              inputSelector: '#certifications-input',
              listSelector: '#certifications-list',
              hiddenSelector: "input[name='certifications']",
              sourceType: 'ajax',
              ajaxAction: 'get_certifications',
              allowManualEntry: false
            });

            // Exports To (master countries)
            try {
              const countries = <?php echo wp_json_encode(get_countries(), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?> || [];
              setupTagWidget({
                inputSelector: '#exports-to-input',
                listSelector: '#exports-to-list',
                hiddenSelector: "input[name='exports_to']",
                sourceType: 'array',
                sourceArray: countries,
                allowManualEntry: true
              });
            } catch (err) {
              console.error('Exports init error', err);
            }

            // Languages (master list)
            try {
              const languages = <?php echo wp_json_encode(get_languages(), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?> || [];
              setupTagWidget({
                inputSelector: '#languages-input',
                listSelector: '#languages-list',
                hiddenSelector: "input[name='languages']",
                sourceType: 'array',
                sourceArray: languages,
                allowManualEntry: true
              });
            } catch (err) {
              console.error('Languages init error', err);
            }


            // ---------------------------
            // Custom Tag Input (Payment Terms, FOB Ports, Packaging)
            // ---------------------------
            $('.custom-tags-input').each(function() {
                const $container = $(this);
                const $input = $container.find('.tag-input');
                const $list = $container.find('.tag-list');
                const $hidden = $container.find('input[type="hidden"]');

                // Load existing tags from hidden field
                const existing = $hidden.val().split(',').filter(tag => tag.trim());
                existing.forEach(tag => addTag(tag.trim()));

                function addTag(text) {
                    if (!text || text.trim() === '') return;
                    const $tag = $('<span class="tag">' + text + ' <span class="remove-tag">×</span></span>');
                    $tag.find('.remove-tag').on('click', function() {
                        $(this).parent().remove();
                        updateHidden();
                    });
                    $list.append($tag);
                    $input.val('');
                    updateHidden();
                }

                function updateHidden() {
                    const tags = [];
                    $list.find('.tag').each(function() {
                        tags.push($(this).text().replace(' ×', ''));
                    });
                    $hidden.val(tags.join(','));
                }

                $input.on('keypress', function(e) {
                    if (e.which === 13 || e.which === 44) { // Enter or Comma
                        e.preventDefault();
                        const value = $input.val().trim();
                        if (value) {
                            addTag(value);
                        }
                    }
                });

                $container.on('click', '.remove-tag', function() {
                    $(this).parent().remove();
                    updateHidden();
                });
            });
        });
        </script>
        <style>
        .custom-tags-input { margin: 10px 0; }
        .tag-input { width: 100%; padding: 8px; margin-bottom: 5px; box-sizing: border-box; }
        .tag-list { min-height: 30px; border: 1px solid #ddd; padding: 5px; border-radius: 4px; background: #fff; }
        .tag { display: inline-block; background: #f0f0f0; padding: 5px 10px; margin: 3px; border-radius: 3px; font-size: 14px; }
        .remove-tag { color: #d00; cursor: pointer; margin-left: 5px; font-weight: bold; }

        /* Autocomplete Styling */
        .ui-autocomplete {
            max-width: 400px !important;
            width: auto !important;
            background: #fff !important;
            border: 1px solid #ddd !important;
            border-radius: 4px !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1) !important;
            z-index: 9999 !important;
        }
        .ui-helper-hidden-accessible {
          display: none !important;
        }
        </style>
        <?php
    }

    public function fields($user) {
        if (!in_array('exporter', (array)$user->roles)) return;

        // Normalize user meta arrays (copied from index.php for consistency)
        function normalize_meta_array( $user_id, $key ) {
            $val = get_user_meta( $user_id, $key, true );
            if ( is_array( $val ) ) {
                if ( count( $val ) === 1 && is_string( $val[0] ) && strpos( $val[0], ',' ) !== false ) {
                    $arr = array_map( 'trim', explode( ',', $val[0] ) );
                } else {
                    $arr = array_map( 'strval', $val );
                }
            } elseif ( is_string( $val ) ) {
                if ( $val === '' ) {
                    $arr = [];
                } else {
                    $arr = array_map( 'trim', explode( ',', $val ) );
                }
            } else {
                $arr = [];
            }
            $arr = array_values(array_filter($arr, function($t){ return (string)$t !== ''; }));
            return $arr;
        }

        $logo           = get_user_meta($user->ID, 'company_logo', true);
        $banner         = get_user_meta($user->ID, 'banner_image', true);
        $company_status = get_user_meta($user->ID, 'company_status', true) ?: 'enabled';
        $verified       = get_user_meta($user->ID, 'verified_supplier', true);

        $state          = get_user_meta($user->ID, 'state', true);
        $city           = get_user_meta($user->ID, 'city', true);

        // Use normalized values for the new tag fields
        $user_exports_to = normalize_meta_array( $user->ID, 'exports_to' );
        $user_languages  = normalize_meta_array( $user->ID, 'languages' );

        // Certifications are stored as IDs — normalize but keep as IDs (strings)
        $user_certs = get_user_meta( $user->ID, 'certifications', true );
        if ( is_string( $user_certs ) ) {
            $user_certs = array_filter(array_map('trim', explode(',', $user_certs)));
        } elseif (!is_array($user_certs)) {
            $user_certs = [];
        }
        $user_certs = array_values($user_certs);

        $capacity       = get_user_meta($user->ID, 'annual_capacity', true);
        $payment_terms  = normalize_meta_array( $user->ID, 'payment_terms' );
        $fob_ports      = normalize_meta_array( $user->ID, 'fob_ports' );
        $lead_time      = get_user_meta($user->ID, 'lead_time', true);
        $packaging      = normalize_meta_array( $user->ID, 'packaging' );
        $contact_person = get_user_meta($user->ID, 'contact_person', true);
        $designation    = get_user_meta($user->ID, 'designation', true);
        $whatsapp       = get_user_meta($user->ID, 'whatsapp', true);
        $skype          = get_user_meta($user->ID, 'skype', true);
        $working_hours  = get_user_meta($user->ID, 'working_hours', true) ?: '9AM–6PM PKT (GMT+5)';

        $all_countries = get_countries();
        $cert_posts = get_posts([
            'post_type'      => 'certification',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC'
        ]);
        ?>
        <div class="exporter-profile" style="border: 1px solid #C3C4C7; background-color: #DCDCDE; padding: 20px;">
            <h2>Exporter Profile</h2>
            <!-- Status & Verification -->
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
                        <span class="description">Show "Verified" badge on profile & products.</span>
                    </td>
                </tr>
            </table>
            <hr>
            <!-- Logo & Banner -->
            <table class="form-table">
                <tr>
                    <th><label for="company_logo">Company Logo</label></th>
                    <td>
                        <div class="media-upload-wrap" data-field="company_logo">
                            <input type="hidden" name="company_logo" value="<?= esc_attr($logo); ?>">
                            <div class="preview">
                                <?php if ($logo): ?>
                                    <img src="<?= esc_url($logo); ?>" style="max-width:150px;height:auto;">
                                <?php endif; ?>
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
                                <?php if ($banner): ?>
                                    <img src="<?= esc_url($banner); ?>" style="max-width:300px;height:auto;">
                                <?php endif; ?>
                            </div>
                            <button type="button" class="button media-upload-btn">Upload Banner</button>
                            <button type="button" class="button media-remove-btn" <?= $banner ? '' : 'style="display:none;"'; ?>>Remove</button>
                        </div>
                    </td>
                </tr>
            </table>
            <hr>
            <!-- Company Info -->
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
                            <option value="Punjab" <?= selected($state, 'Punjab', false); ?>>Punjab</option>
                            <option value="Sindh" <?= selected($state, 'Sindh', false); ?>>Sindh</option>
                            <option value="Khyber Pakhtunkhwa" <?= selected($state, 'Khyber Pakhtunkhwa', false); ?>>Khyber Pakhtunkhwa</option>
                            <option value="Balochistan" <?= selected($state, 'Balochistan', false); ?>>Balochistan</option>
                            <option value="Islamabad Capital Territory" <?= selected($state, 'Islamabad Capital Territory', false); ?>>Islamabad Capital Territory</option>
                            <option value="Gilgit-Baltistan" <?= selected($state, 'Gilgit-Baltistan', false); ?>>Gilgit-Baltistan</option>
                            <option value="Azad Kashmir" <?= selected($state, 'Azad Kashmir', false); ?>>Azad Kashmir</option>
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
                <tr><th>About Company</th>
                    <td><textarea name="about_company" rows="5" cols="50"><?= esc_textarea(get_user_meta($user->ID,'about_company',true)); ?></textarea></td></tr>
            </table>
            <hr>
            <!-- Export & Operations -->
            <table class="form-table">
                <tr>
                    <th><label>Exports To</label></th>
                    <td>
                        <!-- REPLACED SELECT WITH AUTOCOMPLETE INPUT -->
                        <input type="text" id="exports-to-input" placeholder="Start typing country name...">
                        <div id="exports-to-list" class="tag-list">
                            <?php foreach ($user_exports_to as $country): ?>
                                <span class="tag" data-value="<?= esc_attr($country); ?>"><?= esc_html($country); ?> <span class="remove-tag">×</span></span>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="exports_to" value="<?= esc_attr(implode(',', $user_exports_to)); ?>">
                        <p class="description">Press Enter or select from dropdown to add. Type to search.</p>
                    </td>
                </tr>
                <tr>
                    <th><label>Annual Capacity</label></th>
                    <td>
                        <input name="annual_capacity" value="<?= esc_attr($capacity); ?>" class="regular-text" placeholder="e.g., 10,000 Tons">
                    </td>
                </tr>
                <tr>
                    <th><label>Payment Terms</label></th>
                    <td>
                        <div class="custom-tags-input">
                            <input type="text" class="tag-input" placeholder="Type payment term and press Enter or Comma">
                            <div class="tag-list"></div>
                            <input type="hidden" name="payment_terms" value="<?= esc_attr(implode(',', $payment_terms)); ?>">
                        </div>
                        <p class="description">Press Enter or Comma after each term. Click × to remove.</p>
                    </td>
                </tr>
                <tr>
                    <th><label>Languages Spoken</label></th>
                    <td>
                        <!-- REPLACED SELECT WITH AUTOCOMPLETE INPUT -->
                        <input type="text" id="languages-input" placeholder="Start typing language...">
                        <div id="languages-list" class="tag-list">
                            <?php foreach ($user_languages as $lang): ?>
                                <span class="tag" data-value="<?= esc_attr($lang); ?>"><?= esc_html($lang); ?> <span class="remove-tag">×</span></span>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="languages" value="<?= esc_attr(implode(',', $user_languages)); ?>">
                        <p class="description">Press Enter or select from dropdown to add. Type to search.</p>
                    </td>
                </tr>
                <tr>
                    <th><label>FOB Ports</label></th>
                    <td>
                        <div class="custom-tags-input">
                            <input type="text" class="tag-input" placeholder="Type port name and press Enter or Comma">
                            <div class="tag-list"></div>
                            <input type="hidden" name="fob_ports" value="<?= esc_attr(implode(',', $fob_ports)); ?>">
                        </div>
                        <p class="description">e.g., Karachi Port, Port Qasim, Gwadar Port</p>
                    </td>
                </tr>
                <tr>
                    <th><label>Lead Time</label></th>
                    <td>
                        <input name="lead_time" value="<?= esc_attr($lead_time); ?>" class="regular-text" placeholder="e.g., 15–20 Days">
                    </td>
                </tr>
                <tr>
                    <th><label>Packaging Options</label></th>
                    <td>
                        <div class="custom-tags-input">
                            <input type="text" class="tag-input" placeholder="Type packaging option and press Enter or Comma">
                            <div class="tag-list"></div>
                            <input type="hidden" name="packaging" value="<?= esc_attr(implode(',', $packaging)); ?>">
                        </div>
                        <p class="description">e.g., 25kg PP Bags, Custom Logo, Vacuum Sealed</p>
                    </td>
                </tr>
            </table>
            <hr>
            <!-- Contact Person -->
            <table class="form-table">
                <tr>
                    <th><label>Contact Person</label></th>
                    <td><input name="contact_person" value="<?= esc_attr($contact_person); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label>Designation</label></th>
                    <td><input name="designation" value="<?= esc_attr($designation); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label>WhatsApp</label></th>
                    <td><input name="whatsapp" value="<?= esc_attr($whatsapp); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label>Skype</label></th>
                    <td><input name="skype" value="<?= esc_attr($skype); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><label>Working Hours</label></th>
                    <td><input name="working_hours" value="<?= esc_attr($working_hours); ?>" class="regular-text" placeholder="9AM–6PM PKT (GMT+5)"></td>
                </tr>
            </table>
            <hr>
            <!-- Certifications -->
            <table class="form-table">
                <tr>
                    <th><label for="certifications">Certifications</label></th>
                    <td>
                        <!-- REPLACED SELECT WITH AUTOCOMPLETE INPUT -->
                        <input type="text" id="certifications-input" placeholder="Start typing certification name...">
                        <small>Press Enter or select from dropdown to add. Type to search.</small>
                        <div id="certifications-list" class="tag-list">
                            <?php
                            // render saved certification IDs as tags with their titles
                            foreach ($user_certs as $cert_id) {
                                $cert = get_post($cert_id);
                                if ($cert) {
                                    echo '<span class="tag" data-value="' . esc_attr($cert_id) . '">' . esc_html($cert->post_title) . ' <span class="remove-tag">×</span></span>';
                                }
                            }
                            ?>
                        </div>
                        <input type="hidden" name="certifications" value="<?= esc_attr(implode(',', array_map('strval', $user_certs))); ?>">
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    public function save($user_id) {
        if (!current_user_can('edit_user',$user_id)) return;

        $fields = [
            'company_logo','banner_image','company_name','address','state','city','phone',
            'website','facebook','instagram','linkedin','about_company',
            'contact_person','designation','whatsapp','skype','working_hours',
            'annual_capacity','lead_time'
        ];

        foreach ($fields as $k) {
            if (!isset($_POST[$k])) continue;
            if (in_array($k, ['company_logo','banner_image','website','facebook','instagram','linkedin'])) {
                update_user_meta($user_id, $k, esc_url_raw($_POST[$k]));
            } elseif (in_array($k, ['about_company'])) {
                update_user_meta($user_id, $k, sanitize_textarea_field($_POST[$k]));
            } else {
                update_user_meta($user_id, $k, sanitize_text_field($_POST[$k]));
            }
        }

        // Save comma-separated custom fields (including the new ones)
        $custom_fields = ['payment_terms', 'fob_ports', 'packaging', 'exports_to', 'languages', 'certifications'];
        foreach ($custom_fields as $field) {
            if (isset($_POST[$field])) {
                $values = array_filter(array_map('trim', explode(',', $_POST[$field])));
                update_user_meta($user_id, $field, array_values($values));
            } else {
                delete_user_meta($user_id, $field);
            }
        }

        // Save checkboxes & radios
        update_user_meta($user_id, 'company_status', sanitize_text_field($_POST['company_status'] ?? 'enabled'));
        update_user_meta($user_id, 'verified_supplier', isset($_POST['verified_supplier']) ? 1 : 0);
    }
}