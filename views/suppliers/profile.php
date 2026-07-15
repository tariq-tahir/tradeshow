<?php
get_header();

// Get the current exporter post (from Routes or WordPress)
$post = $GLOBALS['awps_current_supplier_post'] ?? null;

if (!$post || $post->post_type !== 'supplier') {
    echo '<p>Invalid supplier profile.</p>';
    get_footer();
    exit;
}

// Helper to get post meta safely
$get_meta = function($key, $single = true) use ($post) {
    return get_post_meta($post->ID, $key, $single);
};

// Basic info
$company_name   = $get_meta('company_name') ?: $post->post_title;
$logo           = $get_meta('company_logo');
$banner         = $get_meta('banner_image');
$address        = $get_meta('address');
$state          = $get_meta('state');
$city           = $get_meta('city');
$phone          = $get_meta('phone');
$whatsapp       = $get_meta('whatsapp');
$website        = $get_meta('website');
$facebook       = $get_meta('facebook');
$instagram      = $get_meta('instagram');
$linkedin       = $get_meta('linkedin');
$about          = $get_meta('about_company');
$certs          = explode(',', $get_meta('certifications'));

// Status & verification
$company_status = $get_meta('company_status') ?: 'enabled';
$verified       = $get_meta('verified_supplier');

// New fields (array-type, stored as comma-separated strings)
$exports_to     = array_filter(array_map('trim', explode(',', $get_meta('exports_to'))));
$capacity       = $get_meta('annual_capacity');
$payment_terms  = array_filter(array_map('trim', explode(',', $get_meta('payment_terms'))));
$languages      = array_filter(array_map('trim', explode(',', $get_meta('languages'))));
$fob_ports      = array_filter(array_map('trim', explode(',', $get_meta('fob_ports'))));
$lead_time      = $get_meta('lead_time');
$packaging      = array_filter(array_map('trim', explode(',', $get_meta('packaging'))));
$contact_person = $get_meta('contact_person');
$designation    = $get_meta('designation');
$skype          = $get_meta('skype');
$working_hours  = $get_meta('working_hours'); // ✅ Raw value (no fallback)

// Disabled check
if ($company_status === 'disabled') {
    status_header(410);
    nocache_headers();
    get_template_part('404');
    exit;
}

// Current URL
global $wp;
$current_url = home_url(add_query_arg([], $wp->request));

// Sanitize WhatsApp
$clean_whatsapp = preg_replace('/[^0-9]/', '', $whatsapp);

// ─────────────────────────────────────────────────────────────
// CONDITIONAL CHECKS: Should sections be shown?
// ─────────────────────────────────────────────────────────────

// Export Capabilities: Show if ANY field has content
$show_export_capabilities = (
    !empty($capacity) || 
    !empty($lead_time) || 
    !empty($payment_terms) || 
    !empty($exports_to) || 
    !empty($languages) || 
    !empty($packaging)
);

// Contact Person: Show if ANY of these fields have content
$show_contact_person = (
    !empty($contact_person) || 
    !empty($designation) || 
    !empty($skype)
);

// Working Hours: Show only if this specific field has content
$show_working_hours = (!empty($working_hours) && trim($working_hours) !== '');
?>

<div class="exporter-profile">
  <!-- Breadcrumbs -->
   <nav class="awps-breadcrumbs" aria-label="Breadcrumb">
    <?php echo do_shortcode('[awps_custom_breadcrumb]'); ?>
   </nav>

  <div class="profile-layout">
    <!-- LEFT COLUMN: Inquiry Form -->
    <aside class="inquiry-sidebar">
      <div class="contact-form-section">
        <h3><i class="fa-solid fa-envelope"></i> Contact <?= esc_html($company_name); ?></h3>
        <form id="exporter-inquiry-form" 
          class="exporter-inquiry-form"
          data-ajax-url="<?= esc_url( admin_url( 'admin-ajax.php' ) ); ?>">

          <input type="hidden" name="action" value="send_exporter_inquiry">
          <input type="hidden" name="security" value="<?= wp_create_nonce('exporter_inquiry_nonce'); ?>">
          <input type="hidden" name="exporter_id" value="<?= esc_attr($post->ID); ?>">

          <p>
            <label>Your Name <span>*</span></label>
            <input type="text" name="name" required class="regular-text" placeholder="John Smith">
          </p>

          <p>
            <label>Your Email <span>*</span></label>
            <input type="email" name="email" required class="regular-text" placeholder="john@importer.com">
          </p>

          <p>
            <label>WhatsApp (Optional)</label>
            <input type="text" name="whatsapp" class="regular-text" placeholder="+1 555 123 4567">
          </p>

          <p>
            <label>Message <span>*</span></label>
            <textarea name="message" rows="4" required class="regular-text" placeholder="e.g., I need pricing and samples."></textarea>
          </p>

          <p>
            <button type="submit" class="button button-primary"><i class="fa-solid fa-paper-plane"></i> Send Inquiry</button>
          </p>

          <div id="exporter-inquiry-result" class="exporter-inquiry-result" style="margin-top:1rem; display:none;"></div>

          <p class="privacy-note">
            <i class="fa-solid fa-lock"></i> We respect your privacy. Your info is only shared with <?= esc_html($company_name); ?>.
          </p>
        </form>
      </div>
    </aside>

    <!-- RIGHT COLUMN: Company Info -->
    <main class="company-content">
      <!-- Enhanced Banner Section -->
      <div class="exporter-hero">
        <div class="hero-image" style="background-image: url('<?= esc_url($banner ?: get_theme_file_uri('assets/images/default-banner.jpg')); ?>');"></div>
        <div class="hero-content">
          <?php if ($logo): ?>
            <div class="hero-logo">
              <img src="<?= esc_url($logo); ?>" alt="<?= esc_attr($company_name); ?>">
              <h1><?= esc_html($company_name); ?></h1>
            </div>
          <?php else: ?>
            <h1><?= esc_html($company_name); ?></h1>
          <?php endif; ?>

          <!-- Address -->
          <?php if ($address || $city || $state): ?>
            <?php
            $clean_address = $address;
            $parts_to_remove = array_filter([$city, $state, 'Pakistan']);
            foreach ($parts_to_remove as $part) {
                if ($part) {
                    $pattern = '/\b' . preg_quote(trim($part), '/') . '\b/i';
                    $clean_address = preg_replace($pattern, '', $clean_address);
                }
            }
            $clean_address = trim(preg_replace('/[,\s]+/', ' ', preg_replace('/\s*([,])\s*/', '$1 ', $clean_address)), " ,");
            $final_parts = array_filter([$clean_address, $city, $state, 'Pakistan']);
            $display_address = implode(', ', $final_parts);
            ?>
            <div class="hero-info">
              <div class="info-item">
                <i class="fa-solid fa-location-dot"></i>
                <span><?= esc_html($display_address); ?></span>
              </div>
            </div>
          <?php endif; ?>

          <?php if ($phone): ?>
            <div class="hero-info">
              <div class="info-item">
                <i class="fa-solid fa-phone"></i>
                <span><?= esc_html($phone); ?></span>
              </div>
            </div>
          <?php endif; ?>

          <?php if ($website): ?>
            <div class="hero-info">
              <div class="info-item">
                <i class="fa-solid fa-globe"></i>
                <a href="<?= esc_url($website); ?>" target="_blank" rel="noopener"><?= esc_html($website); ?></a>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <!-- Social Icons -->
        <?php if ($facebook || $instagram || $linkedin || !empty($clean_whatsapp)): ?>
          <div class="hero-social">
            <?php if ($facebook): ?><a href="<?= esc_url($facebook); ?>" target="_blank" rel="noopener" title="Facebook"><i class="fab fa-facebook-f"></i></a><?php endif; ?>
            <?php if ($instagram): ?><a href="<?= esc_url($instagram); ?>" target="_blank" rel="noopener" title="Instagram"><i class="fab fa-instagram"></i></a><?php endif; ?>
            <?php if ($linkedin): ?><a href="<?= esc_url($linkedin); ?>" target="_blank" rel="noopener" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a><?php endif; ?>
            <?php if (!empty($clean_whatsapp)): ?><a href="https://wa.me/<?= esc_attr($clean_whatsapp); ?>" target="_blank" rel="noopener" title="WhatsApp"><i class="fab fa-whatsapp"></i></a><?php endif; ?>
          </div>
        <?php endif; ?>

        <?php if ($verified): ?>
          <span class="badge"><i class="fa-solid fa-check"></i> Verified Supplier</span>
        <?php endif; ?>
      </div>

      <!-- Products -->
      <section class="products-section">
        <h2><i class="fa-solid fa-boxes-stacked"></i> Products</h2>
        <?php
        $args = [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'author'         => $post->post_author,
        ];
        $products = new WP_Query($args);

        if ($products->have_posts()) :
            echo '<div class="exporter-products">';
            while ($products->have_posts()) : $products->the_post(); ?>
                <div class="product-card">
                    <?php if (has_post_thumbnail()) : ?>
                        <a href="<?= esc_url(get_permalink()); ?>" class="product-image">
                            <?= get_the_post_thumbnail(null, 'medium', ['alt' => get_the_title()]); ?>
                        </a>
                    <?php else: ?>
                        <div class="product-image placeholder"><i class="fa-solid fa-box-open"></i></div>
                    <?php endif; ?>
                    <h4><a href="<?= esc_url(get_permalink()); ?>"><?= esc_html(get_the_title()); ?></a></h4>
                </div>
            <?php endwhile;
            echo '</div>';
        else :
            echo '<p class="no-products">No products listed yet.</p>';
        endif;
        wp_reset_postdata();
        ?>
      </section>

      <!-- Export Capabilities (Conditional) -->
      <?php if ($show_export_capabilities): ?>
      <section class="exporter-operations">
        <h3><i class="fa-solid fa-truck-fast"></i> Export Capabilities</h3>
        <?php if ($capacity): ?><p><strong><i class="fa-solid fa-chart-line"></i> Annual Capacity:</strong> <?= esc_html($capacity); ?></p><?php endif; ?>
        <?php if ($lead_time): ?><p><strong><i class="fa-solid fa-clock"></i> Lead Time:</strong> <?= esc_html($lead_time); ?></p><?php endif; ?>

        <?php if (!empty($payment_terms)): ?>
          <p><strong><i class="fa-solid fa-money-bill-wave"></i> Payment Terms:</strong>
            <?php foreach ($payment_terms as $term): ?><span class="tag"><?= esc_html($term); ?></span><?php endforeach; ?>
          </p>
        <?php endif; ?>

        <?php if (!empty($exports_to)): ?>
          <p><strong><i class="fa-solid fa-earth-americas"></i> Exports To:</strong>
            <?php foreach ($exports_to as $country): ?><span class="tag"><?= esc_html($country); ?></span><?php endforeach; ?>
          </p>
        <?php endif; ?>

        <?php if (!empty($languages)): ?>
          <p><strong><i class="fa-solid fa-language"></i> Languages Spoken:</strong>
            <?php foreach ($languages as $lan): ?><span class="tag"><?= esc_html($lan); ?></span><?php endforeach; ?>
          </p>
        <?php endif; ?>

        <?php if (!empty($packaging)): ?>
          <p><strong><i class="fa-solid fa-box-open"></i> Packaging Options:</strong>
            <?php foreach ($packaging as $option): ?><span class="tag"><?= esc_html($option); ?></span><?php endforeach; ?>
          </p>
        <?php endif; ?>
      </section>
      <?php endif; ?>

      <!-- Contact Person (Conditional - Separate Card) -->
      <?php if ($show_contact_person): ?>
      <section class="contact-details">
        <h3><i class="fa-solid fa-user-tie"></i> Contact Person</h3>
        <?php if ($contact_person): ?><p><strong>Name:</strong> <?= esc_html($contact_person); ?></p><?php endif; ?>
        <?php if ($designation): ?><p><strong>Designation:</strong> <?= esc_html($designation); ?></p><?php endif; ?>
        <?php if ($skype): ?><p><strong><i class="fab fa-skype"></i> Skype:</strong> <?= esc_html($skype); ?></p><?php endif; ?>
      </section>
      <?php endif; ?>

      <!-- Working Hours (Conditional - Separate Card) -->
      <?php if ($show_working_hours): ?>
      <section class="working-hours-details">
        <h3><i class="fa-solid fa-business-time"></i> Working Hours</h3>
        <p><?= esc_html($working_hours); ?></p>
      </section>
      <?php endif; ?>

      <!-- About -->
      <?php if ($about): ?>
        <section class="about-details">
          <h3><i class="fa-solid fa-circle-info"></i> About Us</h3>
          <p><?= nl2br(esc_html($about)); ?></p>
        </section>
      <?php endif; ?>

      <!-- Certifications -->
      <?php if (!empty($certs[0])): ?>
        <section class="exporter-certs">
          <h3><i class="fa-solid fa-certificate"></i> Certifications</h3>
          <?php foreach ($certs as $cert): if (!empty($cert)): ?><span class="tag"><?= esc_html($cert); ?></span><?php endif; endforeach; ?>
        </section>
      <?php endif; ?>

      <!-- Share Section -->
      <div class="profile-footer">
        <div class="share-section">
          <h3><i class="fa-solid fa-share-nodes"></i> Share This Profile</h3>
          <div class="share-buttons">
                <!-- ✅ WhatsApp Share: Generic link (user chooses recipient) -->
                <a href="https://wa.me/?text=<?php echo urlencode('Check out this supplier: ' . $company_name . ' - ' . $current_url); ?>" 
                  class="share-whatsapp" target="_blank" rel="noopener">
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </a>
                
                <!-- LinkedIn Share -->
                <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode($current_url); ?>" 
                  class="share-linkedin" target="_blank" rel="noopener">
                    <i class="fab fa-linkedin-in"></i> LinkedIn
                </a>
                
                <!-- Email Share -->
                <a href="mailto:?subject=<?php echo urlencode($company_name . ' - Supplier Profile'); ?>&body=<?php echo urlencode('I found this exporter on TradeShow.pk: ' . $current_url); ?>" 
                  class="share-email">
                    <i class="fas fa-envelope"></i> Email
                </a>
                
                <!-- Copy Link Button -->
                <a type="button" class="share-copy" data-url="<?php echo esc_url($current_url); ?>">
                    <i class="fas fa-link"></i> Copy Link
                </a>
            </div>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- WhatsApp Floating Button -->
<?php if (!empty($clean_whatsapp)): ?>
  <a href="https://wa.me/<?= esc_attr($clean_whatsapp); ?>?text=<?= urlencode('Hello ' . $company_name . ', I visited your profile on TradeShow.pk and would like to discuss business.'); ?>" class="whatsapp-button" target="_blank" title="Chat on WhatsApp" rel="noopener">
    <i class="fab fa-whatsapp"></i>
  </a>
<?php endif; ?>

<?php get_footer(); ?>