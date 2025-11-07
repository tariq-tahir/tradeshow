<?php
namespace AWPS\Exporters;

if (!defined('ABSPATH')) exit;

class Inquiries {

  public function register() {
    // Show button + form on single product page
    add_action('woocommerce_single_product_summary', [$this, 'render_request_button'], 35);

    // Enqueue frontend JS
    add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

    // AJAX handlers (logged-in + not logged-in)
    add_action('wp_ajax_awps_send_inquiry', [$this, 'ajax_submit_inquiry']);
    add_action('wp_ajax_nopriv_awps_send_inquiry', [$this, 'ajax_submit_inquiry']);

    // Admin: custom columns and metabox
    add_filter('manage_inquiry_posts_columns', [$this, 'admin_columns']);
    add_action('manage_inquiry_posts_custom_column', [$this, 'admin_column_content'], 10, 2);
    add_action('add_meta_boxes', [$this, 'add_inquiry_metabox']);
  }

  /**
     * Generate professional TradeShow inquiry code: TS25-09-AB8X3
     */
    protected function generate_inquiry_code() {
        $year  = date('y');  // "25"
        $month = date('m');  // "09"
        $random = strtoupper(wp_generate_password(5, false, false)); // "AB8X3"
        return "TS{$year}-{$month}-{$random}";
    }

  /**
   * Enqueue the JS file (built to /assets/dist/js/inquiry.js)
   */
  public function enqueue_assets() {
    if (!is_product()) return;

    wp_enqueue_script(
      'awps-inquiry',
      get_template_directory_uri() . '/assets/dist/js/inquiry.js',
      ['jquery'],
      wp_get_theme()->get('Version'),
      true
    );

    wp_localize_script('awps-inquiry', 'awpsInquiry', [
      'ajax_url' => admin_url('admin-ajax.php'),
      // we don't need to pass nonce here because we include it in the form as 'security'
    ]);
  }

  /**
   * Render Request a Quote button + form (inline)
   */
  public function render_request_button() {
    global $product;
    if (!$product) return;

    $product_id = $product->get_id();
    $exporter_id = (int) get_post_field('post_author', $product_id);
    ?>
    <div class="awps-inquiry-wrap">
      <button type="button" class="button awps-inquiry-toggle">📨 Request a Quote</button>

      <div class="awps-inquiry-form" style="display:none;margin-top:1rem;">
        <form class="awps-inquiry-post-form" enctype="multipart/form-data">
          <!-- required fields for AJAX handler -->
          <input type="hidden" name="action" value="awps_send_inquiry">
          <input type="hidden" name="awps_product_id" value="<?php echo esc_attr($product_id); ?>">
          <input type="hidden" name="awps_exporter_id" value="<?php echo esc_attr($exporter_id); ?>">
          <!-- security nonce, named "security" so check_ajax_referer() can find it -->
          <input type="hidden" name="security" value="<?php echo esc_attr(wp_create_nonce('awps_inquiry_nonce')); ?>">

          <p>
            <label>Company Name <span style="color:#d00">*</span></label><br>
            <input type="text" name="inq_company" required class="regular-text">
          </p>

          <p>
            <label>Contact Person Name <span style="color:#d00">*</span></label><br>
            <input type="text" name="inq_name" required class="regular-text">
          </p>

          <p>
            <label>Business Email <span style="color:#d00">*</span></label><br>
            <input type="email" name="inq_email" required class="regular-text">
          </p>

          <p>
            <label>Country <span style="color:#d00">*</span></label><br>
            <select name="inq_country" required class="regular-text" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;background:#fff;">
                <option value="">— Select your country —</option>
                <?php
                $countries = [
                    'Afghanistan',
                    'Albania',
                    'Algeria',
                    'Andorra',
                    'Angola',
                    'Antigua and Barbuda',
                    'Argentina',
                    'Armenia',
                    'Australia',
                    'Austria',
                    'Azerbaijan',
                    'Bahamas',
                    'Bahrain',
                    'Bangladesh',
                    'Barbados',
                    'Belarus',
                    'Belgium',
                    'Belize',
                    'Benin',
                    'Bhutan',
                    'Bolivia',
                    'Bosnia and Herzegovina',
                    'Botswana',
                    'Brazil',
                    'Brunei',
                    'Bulgaria',
                    'Burkina Faso',
                    'Burundi',
                    'Cabo Verde',
                    'Cambodia',
                    'Cameroon',
                    'Canada',
                    'Central African Republic',
                    'Chad',
                    'Chile',
                    'China',
                    'Colombia',
                    'Comoros',
                    'Congo (Congo-Brazzaville)',
                    'Costa Rica',
                    'Croatia',
                    'Cuba',
                    'Cyprus',
                    'Czechia (Czech Republic)',
                    'Democratic Republic of the Congo',
                    'Denmark',
                    'Djibouti',
                    'Dominica',
                    'Dominican Republic',
                    'Ecuador',
                    'Egypt',
                    'El Salvador',
                    'Equatorial Guinea',
                    'Eritrea',
                    'Estonia',
                    'Eswatini (fmr. "Swaziland")',
                    'Ethiopia',
                    'Fiji',
                    'Finland',
                    'France',
                    'Gabon',
                    'Gambia',
                    'Georgia',
                    'Germany',
                    'Ghana',
                    'Greece',
                    'Grenada',
                    'Guatemala',
                    'Guinea',
                    'Guinea-Bissau',
                    'Guyana',
                    'Haiti',
                    'Honduras',
                    'Hungary',
                    'Iceland',
                    'India',
                    'Indonesia',
                    'Iran',
                    'Iraq',
                    'Ireland',
                    'Israel',
                    'Italy',
                    'Jamaica',
                    'Japan',
                    'Jordan',
                    'Kazakhstan',
                    'Kenya',
                    'Kiribati',
                    'Kuwait',
                    'Kyrgyzstan',
                    'Laos',
                    'Latvia',
                    'Lebanon',
                    'Lesotho',
                    'Liberia',
                    'Libya',
                    'Liechtenstein',
                    'Lithuania',
                    'Luxembourg',
                    'Madagascar',
                    'Malawi',
                    'Malaysia',
                    'Maldives',
                    'Mali',
                    'Malta',
                    'Marshall Islands',
                    'Mauritania',
                    'Mauritius',
                    'Mexico',
                    'Micronesia',
                    'Moldova',
                    'Monaco',
                    'Mongolia',
                    'Montenegro',
                    'Morocco',
                    'Mozambique',
                    'Myanmar (formerly Burma)',
                    'Namibia',
                    'Nauru',
                    'Nepal',
                    'Netherlands',
                    'New Zealand',
                    'Nicaragua',
                    'Niger',
                    'Nigeria',
                    'North Korea',
                    'North Macedonia',
                    'Norway',
                    'Oman',
                    'Pakistan',
                    'Palau',
                    'Palestine State',
                    'Panama',
                    'Papua New Guinea',
                    'Paraguay',
                    'Peru',
                    'Philippines',
                    'Poland',
                    'Portugal',
                    'Qatar',
                    'Romania',
                    'Russia',
                    'Rwanda',
                    'Saint Kitts and Nevis',
                    'Saint Lucia',
                    'Saint Vincent and the Grenadines',
                    'Samoa',
                    'San Marino',
                    'Sao Tome and Principe',
                    'Saudi Arabia',
                    'Senegal',
                    'Serbia',
                    'Seychelles',
                    'Sierra Leone',
                    'Singapore',
                    'Slovakia',
                    'Slovenia',
                    'Solomon Islands',
                    'Somalia',
                    'South Africa',
                    'South Korea',
                    'South Sudan',
                    'Spain',
                    'Sri Lanka',
                    'Sudan',
                    'Suriname',
                    'Sweden',
                    'Switzerland',
                    'Syria',
                    'Tajikistan',
                    'Tanzania',
                    'Thailand',
                    'Timor-Leste',
                    'Togo',
                    'Tonga',
                    'Trinidad and Tobago',
                    'Tunisia',
                    'Turkey',
                    'Turkmenistan',
                    'Tuvalu',
                    'Uganda',
                    'Ukraine',
                    'United Arab Emirates',
                    'United Kingdom',
                    'United States of America',
                    'Uruguay',
                    'Uzbekistan',
                    'Vanuatu',
                    'Vatican City',
                    'Venezuela',
                    'Vietnam',
                    'Yemen',
                    'Zambia',
                    'Zimbabwe'
                ];

                foreach ($countries as $country) {
                    echo '<option value="' . esc_attr($country) . '">' . esc_html($country) . '</option>';
                }
                ?>
            </select>
          </p>

          <p>
            <label>Quantity Needed <span style="color:#d00">*</span></label><br>
            <input type="text" name="inq_qty" required placeholder="e.g., 5 MT" class="regular-text">
          </p>

          <p>
            <label>Message <span style="color:#d00">*</span></label><br>
            <textarea name="inq_msg" rows="4" required class="regular-text" placeholder="Looking for monthly supply of Basmati Rice, CIF Dubai"></textarea>
          </p>

          <p>
            <button type="submit" class="button button-primary">📨 Request Quote</button>
            <button type="button" class="button awps-inquiry-cancel">Cancel</button>
          </p>
        </form>

        <div class="awps-inquiry-result" style="margin-top:1rem;display:none;"></div>
      </div>
    </div>
    <?php
  }

  /**
   * AJAX endpoint: save inquiry, send emails
   */
  public function ajax_submit_inquiry() {
    // Verify nonce (expecting 'security' field in POST)
    if (!check_ajax_referer('awps_inquiry_nonce', 'security', false)) {
      wp_send_json_error(['message' => 'Security check failed. Please reload the page and try again.']);
    }

    // Get & sanitize
    $company     = sanitize_text_field($_POST['inq_company'] ?? '');
    $name        = sanitize_text_field($_POST['inq_name'] ?? '');
    $email       = sanitize_email($_POST['inq_email'] ?? '');
    $country     = sanitize_text_field($_POST['inq_country'] ?? '');
    $qty         = sanitize_text_field($_POST['inq_qty'] ?? '');
    $msg         = sanitize_textarea_field($_POST['inq_msg'] ?? '');
    $product_id  = intval($_POST['awps_product_id'] ?? 0);
    $exporter_id = intval($_POST['awps_exporter_id'] ?? 0);

    // Basic required fields check
    if (!$company || !$name || !$email || !$country || !$qty || !$msg || !$product_id) {
      wp_send_json_error(['message' => 'Please fill all required fields.']);
    }


    // Validate email format
    if (!is_email($email)) {
        wp_send_json_error(['message' => '❌ Please enter a valid business email address.']);
    }

    // Block free/disposable emails
    $domain = strtolower(substr(strrchr($email, "@"), 1));
    $blocked = [
        'gmail.com','yahoo.com','hotmail.com','outlook.com','aol.com',
        'protonmail.com','icloud.com','live.com','msn.com','ymail.com',
        'mailinator.com','tempmail.com','10minutemail.com','guerrillamail.com',
        'throwawaymail.com','dispostable.com','fakeinbox.com','trashmail.com','getnada.com'
    ];
    if (in_array($domain, $blocked)) {
        wp_send_json_error(['message' => '❌ Please use a valid company email (free/disposable emails are not allowed).']);
    }

    // Insert CPT 'inquiry' (create post)
    $title = $company . ' — ' . $name;
    $postarr = [
      'post_type'   => 'inquiry',
      'post_status' => 'publish',
      'post_title'  => wp_strip_all_tags($title),
      'post_content'=> $msg,
    ];

    $inquiry_id = wp_insert_post($postarr, true);

    if (is_wp_error($inquiry_id) || !$inquiry_id) {
        wp_send_json_error(['message' => 'Unable to save inquiry.']);
    }

    // ✅ Generate professional TradeShow inquiry code
    $inquiry_code = $this->generate_inquiry_code();
    update_post_meta($inquiry_id, 'inquiry_code', $inquiry_code);

    // Save meta
    update_post_meta($inquiry_id, 'company_name', $company);
    update_post_meta($inquiry_id, 'buyer_name', $name);
    update_post_meta($inquiry_id, 'buyer_email', $email);
    update_post_meta($inquiry_id, 'buyer_country', $country);
    update_post_meta($inquiry_id, 'buyer_qty', $qty);
    update_post_meta($inquiry_id, 'buyer_message', $msg);
    update_post_meta($inquiry_id, 'product_id', $product_id);
    update_post_meta($inquiry_id, 'exporter_id', $exporter_id);

    // Send emails (exporter + buyer)
    $this->email_exporter($inquiry_id);
    $this->email_buyer($inquiry_id);

    // OK
    wp_send_json_success(['message' => 'Thank you — your inquiry has been sent to the exporter.']);
  }

  /**
   * Email exporter
   */
  protected function email_exporter($inquiry_id) {
        $company   = get_post_meta($inquiry_id, 'company_name', true);
        $name      = get_post_meta($inquiry_id, 'buyer_name', true);
        $email     = get_post_meta($inquiry_id, 'buyer_email', true);
        $country   = get_post_meta($inquiry_id, 'buyer_country', true);
        $qty       = get_post_meta($inquiry_id, 'buyer_qty', true);
        $msg       = get_post_meta($inquiry_id, 'buyer_message', true);
        $product   = get_post_meta($inquiry_id, 'product_id', true);
        $exporter  = get_post_meta($inquiry_id, 'exporter_id', true);
        $file_id   = get_post_meta($inquiry_id, 'attachment_id', true);

        $product_title = get_the_title($product);
        $product_url   = get_permalink($product);
        $post_date     = get_the_date('F j, Y \a\t g:i A', $inquiry_id);

        // Get exporter's business email from CPT (recommended)
        $exporter_email = get_post_meta($exporter, 'contact_email', true);
        if (!$exporter_email) {
            $exporter_email = get_option('admin_email'); // fallback
        }

        $inquiry_code = get_post_meta($inquiry_id, 'inquiry_code', true) ?: $this->generate_inquiry_code();
        $subject = sprintf('New inquiry for "%s" — Ref:  %s', $product_title, $inquiry_code);

        $body  = "<p><strong>New Buyer Inquiry</strong> — Ref:  <strong>" . esc_html($inquiry_code) . "</strong></p>";
        $body .= "<p><strong>Product:</strong> <a href='" . esc_url($product_url) . "'>" . esc_html($product_title) . "</a></p>";

        $body .= "<p><strong>Received On:</strong> " . esc_html($post_date) . "</p>";

        $body .= "<p><strong>Buyer Details:</strong></p>";
        $body .= "<p><strong>Company:</strong> " . esc_html($company) . "<br>";
        $body .= "<strong>Contact:</strong> " . esc_html($name) . "<br>";
        $body .= "<strong>Email:</strong> " . esc_html($email) . "<br>";
        $body .= "<strong>Country:</strong> " . esc_html($country) . "<br>";
        $body .= "<strong>Quantity:</strong> " . esc_html($qty) . "</p>";

        $body .= "<p><strong>Message:</strong><br>" . nl2br(esc_html($msg)) . "</p>";

        $body .= "<p><a href='mailto:" . esc_attr($email) . "?subject=Re: Inquiry #" . esc_attr($inquiry_code) . "'>Reply to Buyer</a></p>";

        $body .= "<p><small>This inquiry was submitted via " . esc_html(get_bloginfo('name')) . ". Buyer’s email is verified.</small></p>";

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'Reply-To: ' . $email, // Critical! Lets exporter reply with 1 click
            'From: ' . get_bloginfo('name') . ' <no-reply@' . $_SERVER['HTTP_HOST'] . '>'
        ];

        if (!wp_mail($exporter_email, $subject, $body, $headers)) {
            error_log("Failed to send exporter notification for inquiry #{$inquiry_id}");
        }
    }


  /**
   * Email buyer confirmation
   */
  protected function email_buyer($inquiry_id) {
        $buyer_name  = get_post_meta($inquiry_id, 'buyer_name', true);
        $buyer_email = get_post_meta($inquiry_id, 'buyer_email', true);
        $product_id  = get_post_meta($inquiry_id, 'product_id', true);
        $exporter_id = get_post_meta($inquiry_id, 'exporter_id', true);


       if ($exporter_id) {
            $u = get_userdata((int) $exporter_id);
            if ($u) {
                $exporter_name = get_user_meta($exporter_id, 'company_name', true);
            }
        }

        $company   = get_post_meta($inquiry_id, 'company_name', true);
        $name      = get_post_meta($inquiry_id, 'buyer_name', true);
        $email     = get_post_meta($inquiry_id, 'buyer_email', true);
        $country   = get_post_meta($inquiry_id, 'buyer_country', true);
        $qty       = get_post_meta($inquiry_id, 'buyer_qty', true);
        $msg       = get_post_meta($inquiry_id, 'buyer_message', true);
        $file_id   = get_post_meta($inquiry_id, 'attachment_id', true);

        $product_title = get_the_title($product_id);
        $product_url   = get_permalink($product_id);
        $post_date     = get_the_date('F j, Y \a\t g:i A', $inquiry_id);

        $subject = sprintf('Your inquiry for "%s" has been received', $product_title);

        $body  = "<p>Dear " . esc_html($buyer_name) . ",</p>";
        $body .= "<p>Thank you — your inquiry about <strong>{$product_title}</strong> has been sent to the exporter <strong>{$exporter_name}</strong>. They will contact you soon.</p>";

        $inquiry_code = get_post_meta($inquiry_id, 'inquiry_code', true) ?: $this->generate_inquiry_code();
        $body .= "<p><strong>Inquiry Reference:</strong> " . esc_html($inquiry_code) . "</p>";
        $body .= "<p><strong>Submitted On:</strong> " . esc_html($post_date) . "</p>";

        $body .= "<p><strong>Your Inquiry Details:</strong></p>";
        $body .= "<p><strong>Company:</strong> " . esc_html($company) . "<br>";
        $body .= "<strong>Contact:</strong> " . esc_html($name) . "<br>";
        $body .= "<strong>Email:</strong> " . esc_html($email) . "<br>";
        $body .= "<strong>Country:</strong> " . esc_html($country) . "<br>";
        $body .= "<strong>Quantity:</strong> " . esc_html($qty) . "</p>";

        $body .= "<p><strong>Message:</strong><br>" . nl2br(esc_html($msg)) . "</p>";

        $body .= "<p><a href='" . esc_url($product_url) . "'>View Product Page</a></p>";

        $body .= "<p><small><em>Your information is only shared with " . esc_html($exporter_name) . " to respond to your inquiry. We do not sell or distribute your data.</em></small></p>";

        $body .= "<p>— " . esc_html(get_bloginfo('name')) . "</p>";

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <no-reply@' . $_SERVER['HTTP_HOST'] . '>'
        ];

        if (!wp_mail($buyer_email, $subject, $body, $headers)) {
            error_log("Failed to send buyer confirmation email for inquiry #{$inquiry_code}");
        }
    }

  /**
   * Admin list columns (Inquiry CPT)
   */
  public function admin_columns($cols) {
    $new = [];
    $new['cb']      = $cols['cb'];
    $new['ref']     = 'Ref Code';
    $new['company'] = 'Company';
    $new['name']    = 'Contact Person';
    $new['email']   = 'Email';
    $new['country'] = 'Country';
    $new['qty']     = 'Quantity';
    $new['product'] = 'Product';
    $new['exporter']= 'Exporter';
    $new['date']    = $cols['date'];
    return $new;
  }

  public function admin_column_content($column, $post_id) {
    switch ($column) {
      case 'ref':
        $code = get_post_meta($post_id, 'inquiry_code', true);
        if ($code) {
            $edit_link = get_edit_post_link($post_id);
            echo '<a href="' . esc_url($edit_link) . '">' . esc_html($code) . '</a>';
        } else {
            echo '—';
        }
        break;
      case 'company':
        echo esc_html(get_post_meta($post_id, 'company_name', true));
        break;
      case 'name':
        echo esc_html(get_post_meta($post_id, 'buyer_name', true));
        break;
      case 'email':
        echo esc_html(get_post_meta($post_id, 'buyer_email', true));
        break;
      case 'country':
        echo esc_html(get_post_meta($post_id, 'buyer_country', true));
        break;
      case 'qty':
        echo esc_html(get_post_meta($post_id, 'buyer_qty', true));
        break;
      case 'product':
        $pid = get_post_meta($post_id, 'product_id', true);
        if ($pid) echo '<a href="' . esc_url(get_edit_post_link($pid)) . '">' . esc_html(get_the_title($pid)) . '</a>';
        break;
      case 'exporter':
        $eid = get_post_meta($post_id, 'exporter_id', true);
        if ($eid) {
          $u = get_userdata($eid);
          echo $u ? esc_html($u->company_name) : '-';
        }
        break;
    }
  }

  /**
   * Metabox on single inquiry view
   */
  public function add_inquiry_metabox() {
    add_meta_box('awps_inquiry_details', 'Inquiry Details', [$this, 'render_inquiry_metabox'], 'inquiry', 'normal', 'high');
  }

  public function render_inquiry_metabox($post) {
    $inquiry_code = get_post_meta($post->ID, 'inquiry_code', true) ?: 'Not generated';

    $company = get_post_meta($post->ID, 'company_name', true);
    $name = get_post_meta($post->ID, 'buyer_name', true);
    $email = get_post_meta($post->ID, 'buyer_email', true);
    $country = get_post_meta($post->ID, 'buyer_country', true);
    $qty = get_post_meta($post->ID, 'buyer_qty', true);
    $product_id = get_post_meta($post->ID, 'product_id', true);
    $exporter_id = get_post_meta($post->ID, 'exporter_id', true);
    $attachment_id = get_post_meta($post->ID, 'attachment_id', true);

    echo '<p><strong>Ref Code:</strong> ' . esc_html($inquiry_code) . '</p>';
    if ($company) {
    echo '<p><strong>Company:</strong> ' . esc_html($company) . '</p>';
    }
    echo '<p><strong>Contact Person:</strong> ' . esc_html($name) . '</p>';
    echo '<p><strong>Email:</strong> ' . esc_html($email) . '</p>';
    if ($country) {
    echo '<p><strong>Country:</strong> ' . esc_html($country) . '</p>';
    }
    if ($qty) {
    echo '<p><strong>Quantity:</strong> ' . esc_html($qty) . '</p>';
    }
    if ($product_id) {
      echo '<p><strong>Product:</strong> <a href="' . esc_url(get_edit_post_link($product_id)) . '">' . esc_html(get_the_title($product_id)) . '</a></p>';
    }
    if ($exporter_id) {
      $u = get_userdata($exporter_id);
      echo '<p><strong>Exporter:</strong> ' . ($u ? esc_html($u->company_name) : '-') . '</p>';
    }

    echo '<p><strong>Message:</strong><br>' . nl2br(esc_html(get_post_field('post_content', $post->ID))) . '</p>';
  }
}
