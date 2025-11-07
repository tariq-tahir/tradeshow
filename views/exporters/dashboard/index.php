<?php
// views/exporters/dashboard/index.php

// ✅✅✅ MOVE THE ACCOUNT STATUS CHECK TO THE VERY TOP ✅✅✅
// Get current user
$current_user = wp_get_current_user();

// Check if user is logged in and is an exporter
if ($current_user->ID && in_array('exporter', (array) $current_user->roles)) {
    $company_status = get_user_meta($current_user->ID, 'company_status', true);
    if ($company_status === 'disabled') {
        // Clear any output that might have started
        if (ob_get_level()) {
            ob_end_clean();
        }
        // Log them out
        wp_logout();
        // Redirect to login page with an error message
        wp_redirect(add_query_arg('login', 'disabled', wp_login_url()));
        exit;
    }
}

// Only proceed if user is valid and account is enabled
if (!$current_user->ID) {
    wp_redirect(wp_login_url());
    exit;
}

// Now assign to $user for the rest of the code
$user = $current_user;
$tab  = sanitize_text_field($_GET['tab'] ?? 'profile');

// -------------------------
// Master lists (for autocomplete)
// -------------------------
$state_cities  = get_state_cities();
$countries     = get_countries();
$all_languages = get_languages(); // <-- master list used for autocomplete

// -------------------------
// Helper: normalize whatever is stored in usermeta into an array of strings
// Handles: string "A,B,C", array(['A,B,C']), array(['A','B','C']), empty
// -------------------------
function normalize_meta_array( $user_id, $key ) {
    $val = get_user_meta( $user_id, $key, true );

    if ( is_array( $val ) ) {
        // array might be [ "A,B,C" ] or [ "A", "B" ]
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

    // remove empty values and reindex
    $arr = array_values(array_filter($arr, function($t){ return (string)$t !== ''; }));
    return $arr;
}

// -------------------------
// Normalized user values (use these to render tags & hidden inputs)
// -------------------------
$user_exports_to    = normalize_meta_array( $user->ID, 'exports_to' );
$user_languages     = normalize_meta_array( $user->ID, 'languages' );
$user_payment_terms = normalize_meta_array( $user->ID, 'payment_terms' );
$user_fob_ports     = normalize_meta_array( $user->ID, 'fob_ports' );
$user_packaging     = normalize_meta_array( $user->ID, 'packaging' );

// Certifications are stored as IDs previously — normalize but keep as IDs (strings)
$user_certs = get_user_meta( $user->ID, 'certifications', true );
if ( is_string( $user_certs ) ) {
    $user_certs = array_filter(array_map('trim', explode(',', $user_certs)));
} elseif (!is_array($user_certs)) {
    $user_certs = [];
}
$user_certs = array_values($user_certs);

?>

<div class="exporter-dashboard">
  <h2>Welcome, <?php echo esc_html($user->display_name); ?></h2>

  <nav class="exporter-tabs">
    <a href="?tab=profile" <?php if ($tab==='profile') echo 'class="active"'; ?>>Profile</a> |
    <a href="?tab=products" <?php if ($tab==='products') echo 'class="active"'; ?>>My Products</a> |
    <a href="?tab=add-product" <?php if ($tab==='add-product') echo 'class="active"'; ?>>Add Product</a> |
    <a href="<?= esc_url(wp_logout_url( site_url('/my-account/') )); ?>">Logout</a>
  </nav>

  <div class="exporter-content">
    <?php
    // Handle save FIRST — so get_user_meta() reads fresh data
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['awps_exporter_profile_nonce'])) {
        if (wp_verify_nonce($_POST['awps_exporter_profile_nonce'], 'awps_exporter_profile')) {
            $this->save_profile($user->ID);
            echo '<div class="notice notice-success"><p>✅ Profile updated successfully!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>❌ Security check failed.</p></div>';
        }
    }

    switch ($tab) {
      case 'products':
        include get_theme_file_path('views/exporters/dashboard/product-list.php');
        break;

      case 'add-product':
        include get_theme_file_path('views/exporters/dashboard/product-form.php');
        break;

      case 'profile':
      default:
        // Get saved values AFTER save_profile() runs
        $saved_state = get_user_meta($user->ID, 'state', true);
        $saved_city  = get_user_meta($user->ID, 'city', true);
        ?>
        <form method="post" enctype="multipart/form-data" id="exporter-profile-form">
          <?php wp_nonce_field('awps_exporter_profile','awps_exporter_profile_nonce'); ?>

          <h3>🖼️ Images</h3>
          <p>
            <label>Company Logo</label><br>
            <?php $logo = get_user_meta($user->ID,'company_logo',true); ?>
            <div class="image-upload-wrap" data-field="company_logo">
              <div class="preview">
                <?php if ($logo): ?>
                  <img src="<?= esc_url($logo); ?>" style="max-width:150px;height:auto;">
                <?php endif; ?>
              </div>
              <input type="file" name="company_logo_file" accept="image/*" class="image-input">
              <button type="button" class="button remove-image" <?= $logo ? '' : 'style="display:none;"'; ?>>Remove Logo</button>
              <input type="hidden" name="remove_company_logo" value="">
            </div>
          </p>

          <p>
            <label>Banner Image</label><br>
            <?php $banner = get_user_meta($user->ID,'banner_image',true); ?>
            <div class="image-upload-wrap" data-field="banner_image">
              <div class="preview">
                <?php if ($banner): ?>
                  <img src="<?= esc_url($banner); ?>" style="max-width:400px;height:auto;">
                <?php endif; ?>
              </div>
              <input type="file" name="banner_image_file" accept="image/*" class="image-input">
              <button type="button" class="button remove-image" <?= $banner ? '' : 'style="display:none;"'; ?>>Remove Banner</button>
              <input type="hidden" name="remove_banner_image" value="">
            </div>
          </p>

<!-- Cropping Modal -->
<div id="cropper-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
  background:rgba(0,0,0,0.8); align-items:center; justify-content:center; z-index:9999;">
  <div style="background:#fff; padding:10px; max-width:90%; max-height:90%;">
    <div style="text-align:right;">
      <button id="cropper-cancel" type="button">Cancel</button>
      <button id="cropper-save" type="button">Crop & Save</button>
    </div>
    <div style="max-height:80vh; overflow:auto;">
      <img id="cropper-image" src="" style="max-width:100%; display:block;">
    </div>
  </div>
</div>

          <h3>🏢 Company Info</h3>
          <p>
            <label>Company Name</label><br>
            <input type="text" name="company_name" value="<?= esc_attr(get_user_meta($user->ID,'company_name',true)); ?>" class="regular-text">
          </p>

          <p>
            <label>Address</label><br>
            <input type="text" name="address" value="<?= esc_attr(get_user_meta($user->ID,'address',true)); ?>" class="regular-text">
          </p>

          <p>
            <label>State</label><br>
            <select name="state" id="state" class="regular-text">
                <option value="">— Select State —</option>
                <?php foreach ($state_cities as $state_name => $cities): ?>
                    <option value="<?= esc_attr($state_name); ?>" <?= selected($saved_state, $state_name, true); ?>>
                        <?= esc_html($state_name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
          </p>

          <p>
            <label>City</label><br>
            <select name="city" id="city" class="regular-text">
                <option value="">— Select State First —</option>
                <!-- Options populated by JS -->
            </select>
          </p>

          <p>
            <label>Phone</label><br>
            <input type="text" name="phone" value="<?= esc_attr(get_user_meta($user->ID,'phone',true)); ?>" class="regular-text">
          </p>

          <p>
            <label>Website</label><br>
            <input type="url" name="website" value="<?= esc_url(get_user_meta($user->ID,'website',true)); ?>" class="regular-text">
          </p>

          <p>
            <label>Facebook</label><br>
            <input type="url" name="facebook" value="<?= esc_url(get_user_meta($user->ID,'facebook',true)); ?>" class="regular-text">
          </p>

          <p>
            <label>Instagram</label><br>
            <input type="url" name="instagram" value="<?= esc_url(get_user_meta($user->ID,'instagram',true)); ?>" class="regular-text">
          </p>

          <p>
            <label>LinkedIn</label><br>
            <input type="url" name="linkedin" value="<?= esc_url(get_user_meta($user->ID,'linkedin',true)); ?>" class="regular-text">
          </p>

          <h3>🌍 Export Details</h3>

          <p>
            <label>Exports To</label><br>
            <input type="text" id="exports-to-input" placeholder="Start typing country name...">
            <div id="exports-to-list" class="tag-list">
                <?php foreach ($user_exports_to as $country): ?>
                    <span class="tag" data-value="<?= esc_attr($country); ?>"><?= esc_html($country); ?> <span class="remove-tag">×</span></span>
                <?php endforeach; ?>
            </div>
            <input type="hidden" name="exports_to" value="<?= esc_attr(implode(',', $user_exports_to)); ?>">
            <small>Press Enter or select from dropdown to add. Type to search.</small>
          </p>


          <p>
            <label>Annual Capacity</label><br>
            <input type="text" name="annual_capacity" value="<?= esc_attr(get_user_meta($user->ID,'annual_capacity',true)); ?>" class="regular-text" placeholder="e.g., 10,000 Tons">
          </p>

          <p>
            <label>Payment Terms</label><br>
            <div class="custom-tags-input">
                <input type="text" class="tag-input" placeholder="Type payment term and press Enter or Comma">
                <div class="tag-list" data-tags="<?= esc_attr(implode(',', $user_payment_terms)); ?>"></div>
                <input type="hidden" name="payment_terms" value="<?= esc_attr(implode(',', $user_payment_terms)); ?>">
            </div>
            <small>Press Enter or Comma after each term. Click × to remove.</small>
          </p>

          <p>
            <label>Languages Spoken</label><br>
            <input type="text" id="languages-input" placeholder="Start typing language...">
            <div id="languages-list" class="tag-list">
                <?php foreach ($user_languages as $lang): ?>
                    <span class="tag" data-value="<?= esc_attr($lang); ?>"><?= esc_html($lang); ?> <span class="remove-tag">×</span></span>
                <?php endforeach; ?>
            </div>
            <input type="hidden" name="languages" value="<?= esc_attr(implode(',', $user_languages)); ?>">
            <small>Press Enter or select from dropdown to add. Type to search.</small>
          </p>

          <p>
            <label>FOB Ports</label><br>
            <div class="custom-tags-input">
                <input type="text" class="tag-input" placeholder="Type port name and press Enter or Comma">
                <div class="tag-list" data-tags="<?= esc_attr(implode(',', $user_fob_ports)); ?>"></div>
                <input type="hidden" name="fob_ports" value="<?= esc_attr(implode(',', $user_fob_ports)); ?>">
            </div>
            <small>e.g., Karachi Port, Port Qasim, Gwadar Port</small>
          </p>

          <p>
            <label>Lead Time</label><br>
            <input type="text" name="lead_time" value="<?= esc_attr(get_user_meta($user->ID,'lead_time',true)); ?>" class="regular-text" placeholder="e.g., 15–20 Days">
          </p>

          <p>
            <label>Packaging Options</label><br>
            <div class="custom-tags-input">
                <input type="text" class="tag-input" placeholder="Type packaging option and press Enter or Comma">
                <div class="tag-list" data-tags="<?= esc_attr(implode(',', $user_packaging)); ?>"></div>
                <input type="hidden" name="packaging" value="<?= esc_attr(implode(',', $user_packaging)); ?>">
            </div>
            <small>e.g., 25kg PP Bags, Custom Logo, Vacuum Sealed</small>
          </p>

          <h3>📞 Contact Person</h3>
          <p>
            <label>Contact Person</label><br>
            <input type="text" name="contact_person" value="<?= esc_attr(get_user_meta($user->ID,'contact_person',true)); ?>" class="regular-text">
          </p>

          <p>
            <label>Designation</label><br>
            <input type="text" name="designation" value="<?= esc_attr(get_user_meta($user->ID,'designation',true)); ?>" class="regular-text">
          </p>

          <p>
            <label>WhatsApp</label><br>
            <input type="text" name="whatsapp" value="<?= esc_attr(get_user_meta($user->ID,'whatsapp',true)); ?>" class="regular-text">
          </p>

          <p>
            <label>Skype</label><br>
            <input type="text" name="skype" value="<?= esc_attr(get_user_meta($user->ID,'skype',true)); ?>" class="regular-text">
          </p>

          <p>
            <label>Working Hours</label><br>
            <input type="text" name="working_hours" value="<?= esc_attr(get_user_meta($user->ID,'working_hours',true)); ?>" class="regular-text" placeholder="9AM–6PM PKT (GMT+5)">
          </p>

          <h3>📝 About & Certifications</h3>
          <p>
            <label>About Company</label><br>
            <textarea name="about_company" rows="5" class="regular-text"><?= esc_textarea(get_user_meta($user->ID,'about_company',true)); ?></textarea>
          </p>

          <p>
            <label>Certifications</label><br>
            <input type="text" id="certifications-input" placeholder="Start typing certification name...">
            <small>Press Enter or select from dropdown to add. Type to search.</small>
            <div id="certifications-list" class="tag-list">
                <?php
                // render saved certification IDs as tags with their titles
                $selected_certs = $user_certs; // normalized above
                foreach ($selected_certs as $cert_id) {
                    $cert = get_post($cert_id);
                    if ($cert) {
                        echo '<span class="tag" data-value="' . esc_attr($cert_id) . '">' . esc_html($cert->post_title) . ' <span class="remove-tag">×</span></span>';
                    }
                }
                ?>
            </div>
            <input type="hidden" name="certifications" value="<?= esc_attr(implode(',', array_map('strval', $selected_certs))); ?>">
          </p>

          <p>
            <button type="submit" class="button button-primary">💾 Save Profile</button>
          </p>
        </form>
        <?php
        break;
    }
    ?>
  </div>
</div>


<style>
.exporter-dashboard { max-width: 1000px; margin: 0 auto; padding: 20px; }
.exporter-tabs a { margin-right: 15px; text-decoration: none; font-weight: bold; }
.exporter-tabs a.active { color: #0073aa; }
.notice { padding: 10px; margin: 20px 0; border-left: 4px solid #00a0d2; background: #f0f0f0; }
.notice-success { border-left-color: #46b450; }
.notice-error { border-left-color: #dc3232; }

/* Tags */
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

<script>
(function($){
  $(function(){

    // ---------------------------
    // Image Upload (preview + remove)
    // ---------------------------
    document.querySelectorAll(".image-upload-wrap").forEach(function (wrap) {
      const input = wrap.querySelector(".image-input");
      const preview = wrap.querySelector(".preview");
      const removeBtn = wrap.querySelector(".remove-image");
      const hiddenRemove = wrap.querySelector("input[type=hidden]");

      if (!input) return;

      input.addEventListener("change", function (e) {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function (ev) {
          preview.innerHTML = `<img src="${ev.target.result}" style="max-width:200px;height:auto;">`;
          if (removeBtn) removeBtn.style.display = "inline-block";
          if (hiddenRemove) hiddenRemove.value = "";
        };
        reader.readAsDataURL(file);
      });

      if (removeBtn) {
        removeBtn.addEventListener("click", function () {
          const preview = wrap.querySelector(".preview");
          preview.innerHTML = "";
          if (input) input.value = "";
          if (hiddenRemove) hiddenRemove.value = "1";
          removeBtn.style.display = "none";
        });
      }
    });

    // ---------------------------
    // State -> City
    // ---------------------------
    try {
      const cities = <?php echo wp_json_encode($state_cities); ?>;
      const savedCity = <?= json_encode( (string) get_user_meta($user->ID, 'city', true) ); ?>;

      function updateCities() {
        const state = document.getElementById('state') ? document.getElementById('state').value : '';
        const citySelect = document.getElementById('city');
        if (!citySelect) return;
        citySelect.innerHTML = '<option value="">— Select State First —</option>';
        if (state && cities[state]) {
          cities[state].forEach(city => {
            const option = document.createElement('option');
            option.value = city;
            option.textContent = city;
            citySelect.appendChild(option);
          });
        }
        if (savedCity) {
          for (let i = 0; i < citySelect.options.length; i++) {
            if (citySelect.options[i].value === savedCity) {
              citySelect.selectedIndex = i;
              break;
            }
          }
        }
      }
      const stateEl = document.getElementById('state');
      if (stateEl) stateEl.addEventListener('change', updateCities);
      updateCities();
    } catch (err) {
      console.error('updateCities error', err);
    }

    // ---------------------------
    // Generic Tag Widget
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
    // Initialize widgets
    // ---------------------------

    // Certifications (AJAX source) — preserve existing behavior
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
      const countries = <?php echo wp_json_encode($countries, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?> || [];
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
      const languages = <?php echo wp_json_encode($all_languages, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?> || [];
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

    // Custom tags inputs (payment_terms, fob_ports, packaging)
    try {
      document.querySelectorAll('.custom-tags-input').forEach(function(container) {
        const input = container.querySelector('.tag-input');
        const list = container.querySelector('.tag-list');
        const hidden = container.querySelector('input[type="hidden"]');

        // If server already printed tags inside .tag-list, sync hidden and continue
        function syncFromDOM() {
          const tags = Array.from(list.querySelectorAll('.tag')).map(function(t){
            var tmp = t.cloneNode(true);
            Array.from(tmp.querySelectorAll('.remove-tag')).forEach(function(n){ n.remove(); });
            return tmp.textContent.trim();
          }).filter(Boolean);
          hidden.value = tags.join(',');
        }

        if (list.querySelectorAll('.tag').length > 0) {
          syncFromDOM();
        } else {
          const pre = (list.dataset.tags || hidden.value || '').split(',').map(function(t){ return t.trim(); }).filter(Boolean);
          pre.forEach(function(t){
            const tag = document.createElement('span');
            tag.className = 'tag';
            tag.innerHTML = t + ' <span class="remove-tag">×</span>';
            tag.querySelector('.remove-tag').addEventListener('click', function() {
              tag.remove();
              const tags = Array.from(list.querySelectorAll('.tag')).map(function(x){
                var tmp = x.cloneNode(true);
                Array.from(tmp.querySelectorAll('.remove-tag')).forEach(function(n){ n.remove(); });
                return tmp.textContent.trim();
              }).filter(Boolean);
              hidden.value = tags.join(',');
            });
            list.appendChild(tag);
          });
          // initial hidden
          const tagsNow = Array.from(list.querySelectorAll('.tag')).map(function(x){
            var tmp = x.cloneNode(true);
            Array.from(tmp.querySelectorAll('.remove-tag')).forEach(function(n){ n.remove(); });
            return tmp.textContent.trim();
          }).filter(Boolean);
          hidden.value = tagsNow.join(',');
        }

        function updateHidden(listEl, hiddenEl) {
          const tags = Array.from(listEl.querySelectorAll('.tag')).map(function(tag){
            var tmp = tag.cloneNode(true);
            Array.from(tmp.querySelectorAll('.remove-tag')).forEach(function(n){ n.remove(); });
            return tmp.textContent.trim();
          }).filter(Boolean);
          hiddenEl.value = tags.join(',');
        }

        function addCustomTag(text, listEl, hiddenEl) {
          if (!text || !text.trim()) return;
          const existing = Array.from(listEl.querySelectorAll('.tag')).map(function(tag){
            var tmp = tag.cloneNode(true);
            Array.from(tmp.querySelectorAll('.remove-tag')).forEach(function(n){ n.remove(); });
            return tmp.textContent.trim();
          });
          if (existing.indexOf(text.trim()) !== -1) return;

          const tag = document.createElement('span');
          tag.className = 'tag';
          tag.innerHTML = text + ' <span class="remove-tag">×</span>';
          tag.querySelector('.remove-tag').addEventListener('click', function() {
            tag.remove();
            updateHidden(listEl, hiddenEl);
          });
          listEl.appendChild(tag);
          input.value = '';
          updateHidden(listEl, hiddenEl);
        }

        input.addEventListener('keypress', function(e) {
          if (e.key === 'Enter' || e.key === ',') {
            e.preventDefault();
            addCustomTag(input.value, list, hidden);
          }
        });
      });
    } catch (err) {
      console.error('custom-tags init error', err);
    }

    // ---------------------------
    // Image Cropper (modal)
    // ---------------------------
    try {
      let cropper;
      let activeWrap = null;
      const modal = document.getElementById("cropper-modal");
      const cropperImage = document.getElementById("cropper-image");
      const btnCancel = document.getElementById("cropper-cancel");
      const btnSave = document.getElementById("cropper-save");

      document.querySelectorAll(".image-upload-wrap .image-input").forEach(function(input){
        input.addEventListener('change', function(e){
          var file = e.target.files && e.target.files[0];
          if (!file) return;
          var reader = new FileReader();
          reader.onload = function(ev) {
            cropperImage.src = ev.target.result;
            modal.style.display = "flex";
            activeWrap = input.closest(".image-upload-wrap");

            if (cropper) { try { cropper.destroy(); } catch(e){} }

            var ratio = (activeWrap && activeWrap.dataset && activeWrap.dataset.field === "company_logo") ? 1 : 4/1;
            if (typeof Cropper === 'undefined') {
              console.error('Cropper not found');
              return;
            }
            cropper = new Cropper(cropperImage, {
              aspectRatio: ratio,
              viewMode: 1,
              autoCropArea: 1
            });
          };
          reader.readAsDataURL(file);
        });
      });

      if (btnCancel) btnCancel.addEventListener('click', function(){
        if (cropper) { try { cropper.destroy(); } catch(e){} }
        modal.style.display = "none";
      });

      if (btnSave) btnSave.addEventListener('click', function(){
        if (!cropper || !activeWrap) return;
        var canvas = cropper.getCroppedCanvas({
          width: activeWrap.dataset.field === "company_logo" ? 300 : 1200,
          height: activeWrap.dataset.field === "company_logo" ? 300 : 300
        });
        canvas.toBlob(function(blob){
          var preview = activeWrap.querySelector(".preview");
          preview.innerHTML = '<img src="' + canvas.toDataURL() + '" style="max-width:100%;height:auto;">';

          var fileInput = activeWrap.querySelector(".image-input");
          var dt = new DataTransfer();
          dt.items.add(new File([blob], "cropped.png", { type: "image/png" }));
          fileInput.files = dt.files;

          var removeBtn = activeWrap.querySelector(".remove-image");
          if (removeBtn) removeBtn.style.display = 'inline-block';

          modal.style.display = "none";
          try { cropper.destroy(); } catch(e){}
        }, "image/png");
      });
    } catch (err) {
      console.error('cropper init error', err);
    }

  }); // document ready
})(jQuery);
</script>