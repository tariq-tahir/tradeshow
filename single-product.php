<?php
/**
 * Single Product Template – AWPS B2B Supplier Platform (Custom CPT Version)
 * 
 * @package AWPS
 */

if (!defined('ABSPATH')) exit;

get_header();

global $post;
$product_id = get_the_ID();

// Get supplier data using custom helper
$supplier_data = \AWPS\Suppliers\SupplierProducts::get_supplier_data($product_id);
$supplier_id   = $supplier_data['id'] ?? 0;

// ─────────────────────────────────────────────────────────────
// ✅ CHECK: Is supplier enabled? (Hide card if disabled)
// ─────────────────────────────────────────────────────────────
$supplier_is_enabled = true;

if ($supplier_id > 0) {
    $supplier_posts = get_posts([
        'post_type'      => 'supplier',
        'post_status'    => 'any',
        'meta_query'     => [
            [
                'key'   => '_linked_user_id',
                'value' => $supplier_id,
            ]
        ],
        'numberposts'    => 1,
        'fields'         => 'ids'
    ]);
    
    if (!empty($supplier_posts)) {
        $supplier_post_id = $supplier_posts[0];
        $company_status   = get_post_meta($supplier_post_id, 'company_status', true) ?: 'enabled';
        $supplier_is_enabled = ($company_status !== 'disabled');
    }
}

// Get product meta (all fields with _awps_product_ prefix)
$product_meta = \AWPS\Suppliers\SupplierProducts::get_all_meta($product_id);
$made_in_pakistan = $product_meta['made_in_pakistan'] === 'yes';
$hs_code = $product_meta['hs_code'];
$moq = $product_meta['moq'];
$packaging = $product_meta['packaging'];
$lead_time = $product_meta['lead_time'];
$shipping = $product_meta['shipping'];
$payment_terms = $product_meta['payment_terms'];
$incoterms = $product_meta['incoterms'];
$key_benefits = $product_meta['key_benefits'];
$quality_control = $product_meta['quality_control'];
$sample_policies = $product_meta['sample_policies'];
$factory_video = $product_meta['factory_video'];

// Helper: Explode comma-separated values
function awps_explode_tags($str) {
    return $str ? array_filter(array_map('trim', explode(',', $str))) : [];
}

$packaging_list = awps_explode_tags($packaging);
$shipping_list = awps_explode_tags($shipping);
$payment_list = awps_explode_tags($payment_terms);
$benefits_list = awps_explode_tags($key_benefits);
$quality_list = awps_explode_tags($quality_control);
$sample_list = awps_explode_tags($sample_policies);

// Certifications (loads titles from Certification CPT)
$cert_titles = \AWPS\Suppliers\SupplierProducts::get_certifications($product_id);

// Incoterms tooltip
$incoterms_list = function_exists('get_intoterms') ? get_intoterms() : [];
$incoterm_description = isset($incoterms_list[$incoterms]) ? $incoterms_list[$incoterms] : '';

// Get all product images (featured + gallery meta field)
$all_images = \AWPS\Suppliers\SupplierProducts::get_all_product_images($product_id);
$full_image_urls = [];
foreach ($all_images as $img) {
    $full_image_urls[$img['id']] = $img['full_url'];
}

// Prepare image data for JS
$image_ids_json = wp_json_encode(array_keys($full_image_urls));
$image_urls_json = wp_json_encode($full_image_urls);
$ajax_url = admin_url('admin-ajax.php');
$inquiry_nonce = wp_create_nonce('awps_inquiry_nonce');
$share_url = get_permalink();

// ─────────────────────────────────────────────────────────────
// ✅ CHECK: Should we show technical specs section?
// ─────────────────────────────────────────────────────────────
$show_specs = $hs_code || !empty($packaging_list) || !empty($shipping_list) || !empty($payment_list) || $incoterms || $moq || $lead_time;

// ─────────────────────────────────────────────────────────────
// ✅ QUERY: More products from same supplier
// ─────────────────────────────────────────────────────────────
$supplier_products = new WP_Query([
    'post_type'      => 'product',
    'post_status'    => 'publish',
    'author'         => $supplier_id,
    'posts_per_page' => 4,
    'post__not_in'   => [$product_id],
    'orderby'        => 'date',
    'order'          => 'DESC'
]);
?>

<main id="main-content" class="site-main">
    <div class="container">
        <!-- Breadcrumbs -->
        <div class="awps-breadcrumbs">
            <nav class="custom-breadcrumb">
                <a href="<?php echo esc_url(home_url()); ?>">Home</a> &raquo;
                <a href="<?php echo esc_url(home_url() . '/products/'); ?>">Supplier Products</a> &raquo;
                <?php echo esc_html(get_the_title($product_id)); ?>
            </nav>
        </div>

        <div class="awps-product-container"
             data-image-ids="<?php echo esc_attr($image_ids_json); ?>"
             data-image-urls="<?php echo esc_attr($image_urls_json); ?>"
             data-ajax-url="<?php echo esc_url($ajax_url); ?>"
             data-inquiry-nonce="<?php echo esc_attr($inquiry_nonce); ?>"
             data-share-url="<?php echo esc_url($share_url); ?>">

            <!-- Left Sidebar: Supplier Card + Inquiry Form -->
            <aside class="awps-product-sidebar">
                <!-- Supplier Card -->
                <?php if ($supplier_data && $supplier_is_enabled): ?>
                    <div class="awps-exporter-card">
                    <?php if ($supplier_data['logo']): ?>
                        <a href="<?php echo esc_url($supplier_data['profile_url']); ?>">
                            <img src="<?php echo esc_url($supplier_data['logo']); ?>" alt="<?php echo esc_attr($supplier_data['name']); ?>">
                        </a>
                    <?php else: ?>
                        <div class="awps-exporter-logo-placeholder">
                            <i class="fas fa-building"></i>
                        </div>
                    <?php endif; ?>
                    <div class="awps-exporter-info">
                        <h4><a href="<?php echo esc_url($supplier_data['profile_url']); ?>"><?php echo esc_html($supplier_data['name']); ?></a></h4>
                        <?php if ($supplier_data['city'] || $supplier_data['state']): ?>
                            <p class="exporter-location"><i class="fas fa-map-marker-alt"></i> <?php echo esc_html($supplier_data['city']); ?>, <?php echo esc_html($supplier_data['state']); ?>, Pakistan</p>
                        <?php endif; ?>
                        <?php if ($supplier_data['whatsapp']): ?>
                            <p class="exporter-whatsapp">
                                <?php
                                $clean_whatsapp = preg_replace('/[^0-9]/', '', $supplier_data['whatsapp']);
                                $message = sprintf(
                                    'Hello %s, I saw your product "%s" on TradeShow.pk and would like to discuss business.',
                                    esc_html($supplier_data['name']),
                                    esc_html(get_the_title())
                                );
                                ?>
                                <a href="https://wa.me/<?php echo esc_attr($clean_whatsapp); ?>?text=<?php echo urlencode($message); ?>" 
                                target="_blank" rel="noopener">
                                    <i class="fab fa-whatsapp"></i> WhatsApp
                                </a>
                            </p>
                        <?php endif; ?>
                        <?php if ($supplier_data['verified']): ?>
                            <span class="badge verified"><i class="fas fa-check-circle"></i> Verified Supplier</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Inquiry Form -->
                <div class="awps-inquiry-card">
                    <h3><i class="fas fa-envelope"></i> Request Quote</h3>
                    <form id="awps-inquiry-form" 
                        data-ajax-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"
                        data-product-id="<?php echo get_the_ID(); ?>"
                        data-exporter-id="<?php echo get_post_meta(get_the_ID(), 'exporter_id', true); ?>">
                        
                        
                        <input type="hidden" name="action" value="awps_send_inquiry">
                        <input type="hidden" name="security" value="<?php echo esc_attr($inquiry_nonce); ?>">
                        <input type="hidden" name="awps_product_id" value="<?php echo esc_attr($product_id); ?>">
                        <input type="hidden" name="awps_exporter_id" value="<?php echo esc_attr($supplier_id); ?>">

                        <!-- Row 1: Company + Name -->
                        <div class="form-row">
                            <div class="form-group">
                                <label>Company Name <span class="required">*</span></label>
                                <input type="text" name="inq_company" placeholder="e.g., ABC Trading LLC" required>
                            </div>
                            <div class="form-group">
                                <label>Your Name <span class="required">*</span></label>
                                <input type="text" name="inq_name" placeholder="e.g., John Smith" required>
                            </div>
                        </div>

                        <!-- Row 2: Email + Whatsapp -->
                        <div class="form-row">
                            <div class="form-group">
                                <label>Email <span class="required">*</span></label>
                                <input type="email" name="inq_email" placeholder="john@company.com" required>
                            </div>
                            <div class="form-group">
                                <label>WhatsApp/Phone <span class="optional">(optional)</span></label>
                                <input type="text" name="inq_whatsapp" placeholder="+1 555 123 4567">
                            </div>
                        </div>

                        <!-- Row 3: Country + Destination Port -->
                        <div class="form-row">
                            <div class="form-group">
                                <label>Destination Country <span class="required">*</span></label>
                                <select name="inq_country" required>
                                    <option value="">Select Country *</option>
                                    <?php foreach (get_countries() as $country): ?>
                                        <option value="<?php echo esc_attr($country); ?>"><?php echo esc_html($country); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Destination Port <span class="required">*</span></label>
                                <input type="text" name="inq_destination_port" placeholder="e.g., Jebel Ali, Karachi" required>
                            </div>
                        </div>

                        <!-- Row 4: Shipment Type + Quantity -->
                        <div class="form-row">
                            <div class="form-group">
                                <label>Shipment Type <span class="required">*</span></label>
                                <select name="inq_shipment_type" required>
                                    <option value="">Select Shipment Type *</option>
                                    <?php foreach (get_shipping_methods() as $method): ?>
                                        <option value="<?php echo esc_attr($method); ?>"><?php echo esc_html($method); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Quantity <span class="required">*</span></label>
                                <div class="quantity-input-group">
                                    <input type="number" name="inq_qty_value" placeholder="e.g., 50" min="1" step="any" required class="qty-value">
                                    <select name="inq_qty_unit" required class="qty-unit">
                                        <option value="">Unit *</option>
                                        <?php foreach (get_quantity_units() as $key => $label): ?>
                                            <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Row 5: Message -->
                        <div class="form-group">
                            <label>Message / Additional Requirements <span class="required">*</span></label>
                            <textarea name="inq_msg" rows="4" placeholder="e.g., Required packaging: 25kg bags, CIF pricing, sample availability, etc." required></textarea>
                        </div>

                        <!-- Submit -->
                        <button type="submit" class="button button-primary button-full">
                            <i class="fas fa-paper-plane"></i> Send RFQ
                        </button>
                        <div id="inquiry-response" class="response-message"></div>
                    </form>
                </div>
            </aside>

            <!-- Right Column: Product Details -->
            <div class="awps-product-main">
                <!-- Title & Origin -->
                <div class="awps-header-wrapper">
                    <div class="awps-product-header">
                        <h1><?php echo esc_html(get_the_title()); ?></h1>
                        <?php if ($made_in_pakistan): ?>
                            <span class="awps-origin-badge"><span class="flag">🇵🇰</span> Made in Pakistan</span>
                        <?php endif; ?>
                    </div>
                    <div class="product-meta">
                        <?php
                        $terms = get_the_terms($product_id, 'product_cat');
                        if ($terms && !is_wp_error($terms)) {
                            $term_links = array();
                            foreach ($terms as $term) {
                                $term_links[] = '<a href="' . esc_url(get_term_link($term)) . '">' . esc_html($term->name) . '</a>';
                            }
                            echo '<span class="category">' . implode(', ', $term_links) . '</span>';
                        }
                        ?>
                    </div>
                </div>

                <!-- Featured Image + Gallery -->
                <div class="awps-featured-image">
                    <?php if (has_post_thumbnail()): ?>
                        <div class="main-image-wrapper">
                            <?php echo get_the_post_thumbnail($product_id, 'large', [
                                'class' => 'main-product-image',
                                'loading' => 'eager'
                            ]); ?>
                        </div>
                    <?php else: ?>
                        <div class="placeholder-image">No image available</div>
                    <?php endif; ?>

                    <?php if (!empty($all_images)): ?>
                        <div class="awps-gallery-thumbnails">
                            <?php foreach ($all_images as $index => $img): ?>
                                <a href="#" class="gallery-thumb" data-id="<?php echo esc_attr($img['id']); ?>">
                                    <img src="<?php echo esc_url($img['url']); ?>" 
                                        alt="<?php echo esc_attr($img['alt']); ?>"
                                        class="<?php echo $index === 0 ? 'active' : ''; ?>"
                                        loading="lazy">
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Benefits, Quality, Sample Policy -->
                <?php if (!empty($benefits_list) || !empty($quality_list) || !empty($sample_list)): ?>
                    <div class="awps-three-columns">
                        <?php if (!empty($benefits_list)): ?>
                            <section class="awps-section">
                                <h3><i class="fas fa-star"></i> Key Benefits</h3>
                                <ul>
                                    <?php foreach ($benefits_list as $b): ?><li><?php echo esc_html($b); ?></li><?php endforeach; ?>
                                </ul>
                            </section>
                        <?php endif; ?>
                        <?php if (!empty($quality_list)): ?>
                            <section class="awps-section">
                                <h3><i class="fas fa-vial"></i> Quality Control</h3>
                                <ul>
                                    <?php foreach ($quality_list as $q): ?><li><?php echo esc_html($q); ?></li><?php endforeach; ?>
                                </ul>
                            </section>
                        <?php endif; ?>
                        <?php if (!empty($sample_list)): ?>
                            <section class="awps-section">
                                <h3><i class="fas fa-gift"></i> Sample Policy</h3>
                                <ul>
                                    <?php foreach ($sample_list as $s): ?><li><?php echo esc_html($s); ?></li><?php endforeach; ?>
                                </ul>
                            </section>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Description -->
                <?php if (get_the_content()): ?>
                    <section class="awps-section awps-section-description">
                        <h3><i class="fas fa-align-left"></i> Product Description</h3>
                        <div><?php echo wpautop(wp_kses_post(get_the_content())); ?></div>
                    </section>
                <?php endif; ?>

                <!-- Certifications -->
                <?php if (!empty($cert_titles)): ?>
                    <section class="awps-section awps-section-description">
                        <h3><i class="fas fa-certificate"></i> Certifications</h3>
                        <p><?php echo esc_html(implode(', ', $cert_titles)); ?></p>
                    </section>
                <?php endif; ?>

                <!-- Technical Specs -->
                <?php if ($show_specs): ?>
                <section class="awps-section">
                    <h3><i class="fas fa-table-list"></i> Technical Specifications</h3>
                    <div class="specs-grid">
                        <?php if ($hs_code): ?>
                            <div class="spec-item">
                                <div class="spec-label">HS Code</div>
                                <div class="spec-value"><?php echo esc_html($hs_code); ?></div>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($packaging_list)): ?>
                            <div class="spec-item">
                                <div class="spec-label">Packaging</div>
                                <div class="spec-value"><?php echo esc_html(implode(', ', $packaging_list)); ?></div>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($shipping_list)): ?>
                            <div class="spec-item">
                                <div class="spec-label">Shipping Methods</div>
                                <div class="spec-value"><?php echo esc_html(implode(', ', $shipping_list)); ?></div>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($payment_list)): ?>
                            <div class="spec-item">
                                <div class="spec-label">Payment Terms</div>
                                <div class="spec-value"><?php echo esc_html(implode(', ', $payment_list)); ?></div>
                            </div>
                        <?php endif; ?>
                        <?php if ($incoterms): ?>
                            <div class="spec-item">
                                <div class="spec-label">Incoterms</div>
                                <div class="spec-value">
                                    <?php echo esc_html($incoterms); ?>
                                    <?php if ($incoterm_description): ?>
                                        <span class="awps-tooltip">
                                            <i class="fas fa-info-circle"></i>
                                            <span class="tooltiptext"><?php echo esc_html($incoterm_description); ?></span>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if ($moq): ?>
                            <div class="spec-item">
                                <div class="spec-label">MOQ</div>
                                <div class="spec-value"><?php echo esc_html($moq); ?></div>
                            </div>
                        <?php endif; ?>
                        <?php if ($lead_time): ?>
                            <div class="spec-item">
                                <div class="spec-label">Lead Time</div>
                                <div class="spec-value"><?php echo esc_html($lead_time); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
                <?php endif; ?>

                <!-- Factory Video -->
                <?php if ($factory_video): ?>
                    <section class="awps-section">
                        <h3><i class="fas fa-video"></i> Factory/Processing Video</h3>
                        <?php if (strpos($factory_video, 'youtube') !== false): ?>
                            <?php preg_match('%(?:youtube\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $factory_video, $match); ?>
                            <?php if (!empty($match[1])): ?>
                                <div class="video-wrapper">
                                    <iframe src="https://www.youtube.com/embed/<?php echo esc_attr($match[1]); ?>" allowfullscreen loading="lazy"></iframe>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <video controls class="video-wrapper" preload="metadata">
                                <source src="<?php echo esc_url($factory_video); ?>" type="video/mp4">
                            </video>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>

                <!-- Share Section -->
                <div class="awps-section share-product">
                    <h3><i class="fas fa-share-nodes"></i> Share This Product</h3>
                    <div class="share-buttons">
                        <a href="https://wa.me/?text=<?php echo urlencode('Check out this product: ' . get_the_title() . ' - ' . get_permalink()); ?>" 
                           class="share-whatsapp" target="_blank" rel="noopener">
                            <i class="fab fa-whatsapp"></i> WhatsApp
                        </a>
                        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode(get_permalink()); ?>" 
                           class="share-linkedin" target="_blank" rel="noopener">
                            <i class="fab fa-linkedin-in"></i> LinkedIn
                        </a>
                        <a href="mailto:?subject=<?php echo urlencode('Product: ' . get_the_title()); ?>&body=<?php echo urlencode('I found this product on TradeShow.pk: ' . get_permalink()); ?>" 
                           class="share-email">
                            <i class="fas fa-envelope"></i> Email
                        </a>
                        <a type="button" class="share-copy" data-url="<?php echo esc_url(get_permalink()); ?>">
                            <i class="fas fa-link"></i> Copy Link
                        </a>
                    </div>
                </div>

                <!-- More Products from This Supplier -->
                <?php if ($supplier_products->have_posts()): ?>
                <section class="awps-section more-products">
                    <h3><i class="fas fa-boxes"></i> More Products from <?php echo esc_html($supplier_data['name']); ?></h3>
                    
                    <div class="supplier-products-grid">
                        <?php while ($supplier_products->have_posts()): $supplier_products->the_post(); ?>
                            <div class="supplier-product-card">
                                <a href="<?php echo esc_url(get_permalink()); ?>">
                                    <?php if (has_post_thumbnail()): ?>
                                        <div class="product-image-wrapper">
                                            <?php echo get_the_post_thumbnail(get_the_ID(), 'medium'); ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="product-image-placeholder">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="product-card-content">
                                        <h4><?php echo esc_html(get_the_title()); ?></h4>
                                        <?php 
                                        $cats = get_the_terms(get_the_ID(), 'product_cat');
                                        if ($cats && !is_wp_error($cats)): 
                                        ?>
                                            <p><?php echo esc_html($cats[0]->name); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    
                    <?php if ($supplier_products->found_posts > 4): ?>
                        <p class="view-all-link">
                            <a href="<?php echo esc_url($supplier_data['profile_url']); ?>">
                                View All <?php echo esc_html($supplier_data['name']); ?> Products →
                            </a>
                        </p>
                    <?php endif; ?>
                </section>
                <?php 
                wp_reset_postdata(); 
                endif; 
                ?>
            </div>
        </div>
    </div>

    <!-- Lightbox Modal -->
    <div id="awps-lightbox" aria-hidden="true">
        <div>
            <img id="lightbox-image" src="" alt="">
            <button id="lightbox-close" aria-label="Close lightbox">×</button>
            <button id="lightbox-prev" aria-label="Previous image">‹</button>
            <button id="lightbox-next" aria-label="Next image">›</button>
        </div>
    </div>
</main>

<!-- WhatsApp Floating Button -->
<?php if ($supplier_data && $supplier_data['whatsapp']): ?>
    <?php
    $clean_whatsapp = preg_replace('/[^0-9]/', '', $supplier_data['whatsapp']);
    $message = sprintf(
        'Hello %s, I saw your product "%s" on TradeShow.pk and would like to discuss business.',
        esc_html($supplier_data['name']),
        esc_html(get_the_title())
    );
    ?>
    <a href="https://wa.me/<?php echo esc_attr($clean_whatsapp); ?>?text=<?php echo urlencode($message); ?>" 
       class="whatsapp-button" 
       target="_blank" 
       title="Chat on WhatsApp" 
       rel="noopener">
        <i class="fab fa-whatsapp"></i>
    </a>
<?php endif; ?>


<script>
    // Mobile Lightbox Scroll Lock
(function() {
    if (typeof window === 'undefined') return;
    
    const lightbox = document.getElementById('awps-lightbox');
    if (!lightbox) return;
    
    let scrollY = 0;
    
    // Function to lock scroll
    function lockScroll() {
        scrollY = window.scrollY;
        document.body.classList.add('awps-lightbox-open');
        document.body.style.top = `-${scrollY}px`;
    }
    
    // Function to unlock scroll
    function unlockScroll() {
        document.body.classList.remove('awps-lightbox-open');
        document.body.style.top = '';
        window.scrollTo(0, scrollY);
    }
    
    // Observe lightbox display changes
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.attributeName === 'style') {
                const isDisplayed = lightbox.style.display === 'block' || 
                                   lightbox.style.display === 'flex';
                
                if (isDisplayed) {
                    lockScroll();
                } else {
                    unlockScroll();
                }
            }
        });
    });
    
    observer.observe(lightbox, { attributes: true, attributeFilter: ['style'] });
    
    // Also handle close button click
    const closeBtn = document.getElementById('lightbox-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', unlockScroll);
    }
    
    // Handle prev/next buttons
    const prevBtn = document.getElementById('lightbox-prev');
    const nextBtn = document.getElementById('lightbox-next');
    if (prevBtn) prevBtn.addEventListener('click', () => { /* Keep scroll locked */ });
    if (nextBtn) nextBtn.addEventListener('click', () => { /* Keep scroll locked */ });
})();
</script>

<?php get_footer(); ?>