<?php
get_header();
global $wp;
$author = $GLOBALS['awps_current_exporter'] ?? null;

if ( ! $author ) {
    echo '<p>Invalid exporter profile.</p>';
    get_footer();
    exit;
}

// Get the ID of the exporter whose profile this is
$author_id = $author->ID; // <-- This is the crucial fix. Use $author->ID, not get_queried_object_id()

// Check if this specific exporter is disabled
$company_status = get_user_meta($author_id, 'company_status', true);

// If the company is disabled, serve a 410 Gone
if ($company_status === 'disabled') {
    status_header(410); // 410 is best for "permanently gone"
    nocache_headers();
    include get_query_template('404');
    exit;
}

// Fetch ALL fields
$company_name     = get_user_meta($author->ID, 'company_name', true) ?: $author->display_name;
$logo             = get_user_meta($author->ID, 'company_logo', true);
$banner           = get_user_meta($author->ID, 'banner_image', true);
$address          = get_user_meta($author->ID, 'address', true);
$state            = get_user_meta($author->ID, 'state', true);
$city             = get_user_meta($author->ID, 'city', true);
$phone            = get_user_meta($author->ID, 'phone', true);
$whatsapp         = get_user_meta($author->ID, 'whatsapp', true);
$website          = get_user_meta($author->ID, 'website', true);
$facebook         = get_user_meta($author->ID, 'facebook', true);
$instagram        = get_user_meta($author->ID, 'instagram', true);
$linkedin         = get_user_meta($author->ID, 'linkedin', true);
$about            = get_user_meta($author->ID, 'about_company', true);
$certs            = get_user_meta($author->ID, 'certifications', true);


// New fields
$verified         = get_user_meta($author->ID, 'verified_supplier', true);
$exports_to       = (array) get_user_meta($author->ID, 'exports_to', true);
$capacity         = get_user_meta($author->ID, 'annual_capacity', true);
$payment_terms    = (array) get_user_meta($author->ID, 'payment_terms', true);
$languages        = (array) get_user_meta($author->ID, 'languages', true);
$fob_ports        = (array) get_user_meta($author->ID, 'fob_ports', true);
$lead_time        = get_user_meta($author->ID, 'lead_time', true);
$packaging        = (array) get_user_meta($author->ID, 'packaging', true);
$contact_person   = get_user_meta($author->ID, 'contact_person', true);
$designation      = get_user_meta($author->ID, 'designation', true);
$skype            = get_user_meta($author->ID, 'skype', true);
$working_hours    = get_user_meta($author->ID, 'working_hours', true) ?: '9AM–6PM PKT (GMT+5)';

// Get current page URL for sharing
$current_url = home_url(add_query_arg(array(), $wp->request));
?>

<style>
.exporter-profile { max-width: 1200px; margin: 0 auto; padding: 20px; position: relative; }
.exporter-banner img { width: 100%; height: 300px; object-fit: cover; border-radius: 8px; }
.exporter-header { text-align: center; margin: 30px 0; }
.exporter-logo { width: 120px; height: 120px; object-fit: contain; border-radius: 8px; margin-bottom: 15px; }
.exporter-details, .exporter-operations, .contact-details, .exporter-certs, .contact-form-section { margin: 30px 0; padding: 20px; background: #f9f9f9; border-radius: 8px; }
.exporter-details h3, .exporter-operations h3, .contact-details h3, .contact-form-section h3 { margin-top: 0; color: #222; }
.badge { background: #28a745; color: white; padding: 4px 10px; border-radius: 4px; font-size: 14px; margin-left: 10px; }
.tag { display: inline-block; background: #e9ecef; padding: 4px 10px; margin: 3px; border-radius: 4px; font-size: 14px; }
.social-links a { margin: 0 10px; text-decoration: none; color: #0073aa; }

/* WhatsApp Button */
.whatsapp-button {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: #25D366;
    color: white;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    z-index: 9999;
    text-decoration: none;
    font-size: 28px;
    transition: transform 0.2s;
}
.whatsapp-button:hover {
    transform: scale(1.1);
}

/* Share Section */
.share-section {
    text-align: center;
    margin: 30px 0;
    padding: 20px;
    background: #f0f7ff;
    border-radius: 8px;
}
.share-section h3 { margin-top: 0; }
.share-buttons a {
    display: inline-block;
    margin: 0 8px;
    padding: 10px 15px;
    border-radius: 6px;
    color: white;
    text-decoration: none;
    font-weight: bold;
    font-size: 14px;
}
.share-whatsapp { background: #25D366; }
.share-linkedin { background: #0077B5; }
.share-email { background: #EA4335; }
.share-copy { background: #5F6368; cursor: pointer; }
.copied { background: #34A853 !important; }

/* Contact Form */
.contact-form-section form { margin-top: 20px; }
.contact-form-section input,
.contact-form-section select,
.contact-form-section textarea {
    width: 100%;
    padding: 10px;
    margin: 8px 0;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-sizing: border-box;
}
.contact-form-section .button {
    background: #0073aa;
    color: white;
    padding: 12px 24px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
    margin-top: 10px;
}
.contact-form-section .result {
    margin-top: 15px;
    padding: 10px;
    border-radius: 4px;
    display: none;
}
.contact-form-section .success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
.contact-form-section .error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
.notice {
    padding: 10px;
    border-radius: 4px;
    margin: 10px 0;
}
.notice.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
.notice.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
</style>

<div class="exporter-profile">

  <?php if ($banner): ?>
    <div class="exporter-banner">
      <img src="<?= esc_url($banner); ?>" alt="<?= esc_attr($company_name); ?>">
    </div>
  <?php endif; ?>

  <div class="exporter-header">
    <?php if ($logo): ?>
      <img class="exporter-logo" src="<?= esc_url($logo); ?>" alt="<?= esc_attr($company_name); ?>">
    <?php endif; ?>
    <h1><?= esc_html($company_name); ?>
      <?php if ($verified): ?>
        <span class="badge">✅ Verified Supplier</span>
      <?php endif; ?>
    </h1>
  </div>

  <!-- Company Details -->
  <div class="exporter-details">
    <h3>🏢 Company Details</h3>
    <?php if ($address): ?><p><strong>Address:</strong> <?= esc_html($address); ?></p><?php endif; ?>
    <?php if ($state): ?><p><strong>State:</strong> <?= esc_html($state); ?></p><?php endif; ?>
    <?php if ($city): ?><p><strong>City:</strong> <?= esc_html($city); ?></p><?php endif; ?>
    <?php if ($phone): ?><p><strong>Phone:</strong> <?= esc_html($phone); ?></p><?php endif; ?>
    <?php if ($website): ?><p><strong>Website:</strong> <a href="<?= esc_url($website); ?>" target="_blank"><?= esc_html($website); ?></a></p><?php endif; ?>
    
    <?php
    if (is_array($exports_to)) {
        // Sometimes WP stores as array with one string, so flatten it
        if (count($exports_to) === 1 && is_string($exports_to[0]) && strpos($exports_to[0], ',') !== false) {
            $exports_to = explode(',', $exports_to[0]);
        }
    } elseif (is_string($exports_to)) {
        $exports_to = explode(',', $exports_to);
    } else {
        $exports_to = [];
    }

    // Trim spaces
    $exports_to = array_filter(array_map('trim', $exports_to));
    ?>
    
      <?php if (!empty($exports_to)): ?>
      <p><strong>🌍 Exports To:</strong>
        <?php foreach ($exports_to as $country): ?>
          <span class="tag"><?= esc_html($country); ?></span>
        <?php endforeach; ?>
      </p>
    <?php endif; ?>

    <?php if ($capacity): ?><p><strong>📈 Annual Capacity:</strong> <?= esc_html($capacity); ?></p><?php endif; ?>
    <?php if ($lead_time): ?><p><strong>⏱️ Lead Time:</strong> <?= esc_html($lead_time); ?></p><?php endif; ?>
  </div>

  <!-- Operations -->
  <div class="exporter-operations">
    <h3>⚙️ Export Capabilities</h3>
    
    <?php if (!empty($payment_terms)): ?>
      <p><strong>💳 Payment Terms:</strong>
        <?php foreach ($payment_terms as $term): ?>
          <span class="tag"><?= esc_html($term); ?></span>
        <?php endforeach; ?>
      </p>
    <?php endif; ?>

    <?php
    if (is_array($languages)) {
        // Sometimes WP stores as array with one string, so flatten it
        if (count($languages) === 1 && is_string($languages[0]) && strpos($languages[0], ',') !== false) {
            $languages = explode(',', $languages[0]);
        }
    } elseif (is_string($languages)) {
        $languages = explode(',', $languages);
    } else {
        $languages = [];
    }

    // Trim spaces
    $languages = array_filter(array_map('trim', $languages));
    ?>

    <?php if (!empty($languages)): ?>
      <p><strong>🗣️ Languages Spoken:</strong>
        <?php foreach ($languages as $lan): ?>
          <span class="tag"><?= esc_html($lan); ?></span>
        <?php endforeach; ?>
      </p>
    <?php endif; ?>



    <?php if (!empty($fob_ports)): ?>
      <p><strong>⚓ FOB Ports:</strong>
        <?php foreach ($fob_ports as $port): ?>
          <span class="tag"><?= esc_html($port); ?></span>
        <?php endforeach; ?>
      </p>
    <?php endif; ?>

    <?php if (!empty($packaging)): ?>
      <p><strong>📦 Packaging Options:</strong>
        <?php foreach ($packaging as $option): ?>
          <span class="tag"><?= esc_html($option); ?></span>
        <?php endforeach; ?>
      </p>
    <?php endif; ?>
  </div>

  <!-- Contact Person -->
  <div class="contact-details">
    <h3>📞 Contact Person</h3>
    <?php if ($contact_person): ?><p><strong>Name:</strong> <?= esc_html($contact_person); ?></p><?php endif; ?>
    <?php if ($designation): ?><p><strong>Designation:</strong> <?= esc_html($designation); ?></p><?php endif; ?>
    <?php if ($skype): ?><p><strong>Skype:</strong> <?= esc_html($skype); ?></p><?php endif; ?>
    <?php if ($working_hours): ?><p><strong>🕒 Working Hours:</strong> <?= esc_html($working_hours); ?></p><?php endif; ?>
  </div>

  <!-- About -->
  <?php if ($about): ?>
    <div class="about-details" style="margin: 30px 0; padding: 20px; background: #f0f7ff; border-radius: 8px;">
      <h3>📝 About Us</h3>
      <p><?= nl2br(esc_html($about)); ?></p>
    </div>
  <?php endif; ?>

  <!-- Certifications -->
  <?php if (!empty($certs)): ?>
    <div class="exporter-certs">
      <h3>🏅 Certifications</h3>

      <?php if (!empty($certs)): ?>
      
        <?php foreach ($certs as $option): ?>
          <span class="tag"><?= esc_html($option); ?></span>
        <?php endforeach; ?>
      
      <?php endif; ?>

    </div>
  <?php endif; ?>

  <!-- Share Section -->
  <div class="share-section">
    <h3>📤 Share This Profile</h3>
    <div class="share-buttons">
      <?php if ($whatsapp): ?>
        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $whatsapp); ?>?text=<?= urlencode('Check out this exporter: ' . $company_name . ' - ' . $current_url); ?>" class="share-whatsapp" target="_blank">WhatsApp</a>
      <?php endif; ?>
      <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($current_url); ?>" class="share-linkedin" target="_blank">LinkedIn</a>
      <a href="mailto:?subject=<?= urlencode($company_name . ' - Exporter Profile'); ?>&body=<?= urlencode('I thought you might be interested in this exporter: ' . $current_url); ?>" class="share-email">Email</a>
      <a class="share-copy" onclick="copyToClipboard('<?= esc_js($current_url); ?>')">Copy Link</a>
    </div>
  </div>

  
<!-- Contact Form Section -->
<div class="contact-form-section">
    <h3>📨 Send Inquiry to <?= esc_html($company_name); ?></h3>
    
    <form id="exporter-inquiry-form" class="exporter-inquiry-form">
        <input type="hidden" name="action" value="send_exporter_inquiry">
        <input type="hidden" name="exporter_id" value="<?= esc_attr($author->ID); ?>">
        <input type="hidden" name="security" value="<?= esc_attr(wp_create_nonce('exporter_inquiry_nonce')); ?>">

        
        

        <p>
            <label>Your Name <span style="color:#d00">*</span></label>
            <input type="text" name="name" required class="regular-text" placeholder="John Smith">
        </p>

        <p>
            <label>Business Email <span style="color:#d00">*</span></label>
            <input type="email" name="email" required class="regular-text" placeholder="john@importer.com">
        </p>

        <p>
            <label>WhatsApp (Optional)</label>
            <input type="text" name="whatsapp" class="regular-text" placeholder="+1 555 123 4567">
        </p>

        <p>
            <label>Message <span style="color:#d00">*</span></label>
            <textarea name="message" rows="4" required class="regular-text" placeholder="e.g., I need pricing and samples."></textarea>
        </p>

        <p>
            <button type="submit" class="button button-primary">📨 Send Inquiry</button>
        </p>

        <div id="exporter-inquiry-result" class="exporter-inquiry-result" style="margin-top:1rem; display:none;"></div>

        <p style="font-size: 12px; color: #666; margin-top: 10px;">
            🔐 We respect your privacy. Your info is only shared with <?= esc_html($company_name); ?>.
        </p>
    </form>
</div>

  <!-- Social Links -->
  <?php if ($facebook || $instagram || $linkedin): ?>
    <div class="social-links" style="text-align: center; margin: 30px 0;">
      <h3>🔗 Connect With Us</h3>
      <?php if ($facebook): ?><a href="<?= esc_url($facebook); ?>" target="_blank">Facebook</a><?php endif; ?>
      <?php if ($instagram): ?><a href="<?= esc_url($instagram); ?>" target="_blank">Instagram</a><?php endif; ?>
      <?php if ($linkedin): ?><a href="<?= esc_url($linkedin); ?>" target="_blank">LinkedIn</a><?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- Products -->
  <h2 style="margin: 40px 0 20px 0; padding-bottom: 10px; border-bottom: 2px solid #eee;">📦 Products</h2>
  <?php
  $args = array(
      'post_type'      => 'product',
      'post_status'    => 'publish',
      'posts_per_page' => -1,
      'author'         => $author->ID,
  );

  $products = new WP_Query($args);

  if ($products->have_posts()) :
      echo '<div class="exporter-products" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">';
      while ($products->have_posts()) : $products->the_post();
          global $product;
          ?>
          <div style="border: 1px solid #ddd; padding: 15px; border-radius: 8px; background: white;">
              <h4><a href="<?= get_permalink(); ?>" style="text-decoration: none; color: #222;"><?= get_the_title(); ?></a></h4>
              <?php if ($product): ?>
                  <p style="color: #d00; font-weight: bold;"><?= $product->get_price_html(); ?></p>
              <?php endif; ?>
              <a href="<?= get_permalink(); ?>" class="button" style="display: inline-block; padding: 8px 15px; background: #0073aa; color: white; text-decoration: none; border-radius: 4px; font-size: 14px;">View Product</a>
          </div>
          <?php
      endwhile;
      echo '</div>';
  else :
      echo '<p>No products listed yet.</p>';
  endif;

  wp_reset_postdata();
  ?>

</div>

<!-- WhatsApp Floating Button -->
<?php if ($whatsapp): ?>
  <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $whatsapp); ?>?text=<?= urlencode('Hello ' . $company_name . ', I visited your profile on TradeShow.pk and would like to discuss business.'); ?>" class="whatsapp-button" target="_blank" title="Chat on WhatsApp">
    <i>💬</i>
  </a>
<?php endif; ?>

<script>
function copyToClipboard(text) {
    // Modern browsers (HTTPS)
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(() => {
            const btn = document.querySelector('.share-copy');
            btn.textContent = 'Copied!';
            btn.classList.add('copied');
            setTimeout(() => {
                btn.textContent = 'Copy Link';
                btn.classList.remove('copied');
            }, 2000);
        }).catch(err => {
            fallbackCopy(text);
        });
    } else {
        // Fallback for HTTP or older browsers
        fallbackCopy(text);
    }
}

function fallbackCopy(text) {
    const input = document.createElement('input');
    input.value = text;
    document.body.appendChild(input);
    input.select();
    input.setSelectionRange(0, 99999); // For mobile

    try {
        document.execCommand('copy');
        const btn = document.querySelector('.share-copy');
        btn.textContent = 'Copied!';
        btn.classList.add('copied');
        setTimeout(() => {
            btn.textContent = 'Copy Link';
            btn.classList.remove('copied');
        }, 2000);
    } catch (err) {
        alert('Failed to copy. Please copy manually: ' + text);
    }

    document.body.removeChild(input);
}
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('exporter-inquiry-form');
    const resultDiv = document.getElementById('exporter-inquiry-result');
    const submitBtn = form.querySelector('button[type="submit"]');

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        // Show loading
        submitBtn.disabled = true;
        submitBtn.textContent = 'Sending...';
        resultDiv.style.display = 'none';

        // Create FormData
        const formData = new FormData(form);

        // Send AJAX
        fetch('<?= admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            submitBtn.disabled = false;
            submitBtn.textContent = '📨 Send Inquiry';

            if (data.success) {
                resultDiv.innerHTML = '<div class="notice success">' + data.data.message + '</div>';
                resultDiv.style.display = 'block';
                form.reset();
            } else {
                resultDiv.innerHTML = '<div class="notice error">❌ ' + (data.data?.message || 'Submission failed') + '</div>';
                resultDiv.style.display = 'block';
            }
        })
        .catch(error => {
            submitBtn.disabled = false;
            submitBtn.textContent = '📨 Send Inquiry';
            resultDiv.innerHTML = '<div class="notice error">❌ Network error. Please try again.</div>';
            resultDiv.style.display = 'block';
            console.error('Error:', error);
        });
    });
});
</script>

<?php get_footer(); ?>