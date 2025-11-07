<?php
/**
 * Custom Single Product Template for AWPS B2B Export Platform
 *
 * @package AWPS
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Ensure WooCommerce is active
if (!class_exists('WooCommerce') || !function_exists('wc_get_product')) {
    return;
}

// Get product object safely
global $product;
if (!$product && get_the_ID()) {
    $product = wc_get_product(get_the_ID());
}

// Still no product? Bail.
if (!$product) {
    wp_die('Product not found.');
}

// Get post data safely
$post_data = get_post($product->get_id());
$exporter_id = $post_data ? $post_data->post_author : 0;

$exporter = $exporter_id ? get_user_by('id', $exporter_id) : null;
$exporter_name = $exporter ? get_user_meta($exporter_id, 'company_name', true) ?: $exporter->display_name : 'Exporter';
$exporter_logo = $exporter ? get_user_meta($exporter_id, 'company_logo', true) : '';
$exporter_profile_url = $exporter ? home_url('/exporter/' . sanitize_title($exporter_name)) : '#';

// Get custom meta
$made_in_pakistan = $product->get_meta('_made_in_pakistan') === 'yes';
$hs_code = $product->get_meta('_hs_code');
$moq = $product->get_meta('_moq');
$packaging = $product->get_meta('_packaging_details');
$lead_time = $product->get_meta('_lead_time');
$shipping = $product->get_meta('_shipping_options');
$payment_terms = $product->get_meta('_payment_terms');
$incoterms = $product->get_meta('_incoterms');
$key_benefits = $product->get_meta('_key_benefits');
$quality_control = $product->get_meta('_quality_control');
$certifications = $product->get_meta('_certifications');
$factory_video = $product->get_meta('_factory_video_url');
$sample_policies = $product->get_meta('_sample_policies');

// Get Incoterms full description for tooltip
$incoterms_list = function_exists('get_intoterms') ? get_intoterms() : [];
$incoterm_description = isset($incoterms_list[$incoterms]) ? $incoterms_list[$incoterms] : '';

// Get certifications titles from stored IDs
$cert_titles = [];

if ($certifications) {
    $cert_ids = array_filter(array_map('trim', explode(',', $certifications)));
    foreach ($cert_ids as $id) {
        $id = intval($id);
        if ($id > 0) {
            $cert_post = get_post($id);
            if ($cert_post && $cert_post->post_status === 'publish') {
                $cert_titles[] = $cert_post->post_title;
            }
        }
    }
}

// Explode comma-separated fields
function awps_explode_tags($str) {
    return $str ? array_filter(array_map('trim', explode(',', $str))) : [];
}

$packaging_list = awps_explode_tags($packaging);
$shipping_list = awps_explode_tags($shipping);
$payment_list = awps_explode_tags($payment_terms);
$benefits_list = awps_explode_tags($key_benefits);
$quality_list = awps_explode_tags($quality_control);
$sample_list = awps_explode_tags($sample_policies);

// Verified Supplier
$verified = $exporter_id ? get_user_meta($exporter_id, 'verified_supplier', true) : false;

// Stock status
$stock_status = $product->get_stock_status();

// Add body class for styling
add_filter('body_class', function($classes) use ($product) {
    $classes[] = 'awps-product-page';
    return $classes;
});

// Load header
get_header();
?>

<style>
    .awps-product-header {
        background: #f8f9fa;
        padding: 25px;
        border-bottom: 1px solid #e9ecef;
        margin-bottom: 30px;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .awps-exporter-name {
        font-size: 1.3rem;
        font-weight: 600;
        margin-bottom: 12px;
        color: #212529;
    }
    .awps-badges {
        display: flex;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
        font-size: 0.95rem;
        color: #6c757d;
    }
    .awps-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #e9ecef;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.88rem;
        font-weight: 500;
    }
    .awps-badge.verified { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .awps-badge.origin { background: #cce5ff; color: #004085; border: 1px solid #b8daff; }

    .awps-exporter-card {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 20px;
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        margin: 20px 0;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .awps-exporter-logo {
        width: 60px;
        height: 60px;
        border-radius: 8px;
        object-fit: contain;
        background: #f8f9fa;
        padding: 5px;
    }
    .awps-exporter-info h4 {
        margin: 0 0 5px 0;
        font-size: 1.1rem;
    }
    .awps-exporter-info p {
        margin: 0;
        font-size: 0.9rem;
        color: #6c757d;
    }
    .awps-exporter-link {
        text-decoration: none;
        font-weight: 600;
        color: #007cba;
    }
    .awps-exporter-link:hover {
        text-decoration: underline;
    }

    .awps-hero-gallery {
        margin-bottom: 30px;
        background: #fff;
        padding: 20px;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .awps-product-badges {
        display: flex;
        gap: 10px;
        margin: 15px 0;
        flex-wrap: wrap;
    }
    .awps-product-badge {
        background: #e8f0fe;
        color: #1a73e8;
        padding: 6px 14px;
        border-radius: 16px;
        font-size: 0.88rem;
        font-weight: 500;
    }

    .summary.entry-summary {
        background: #fff;
        padding: 30px;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        margin-bottom: 30px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .product_title {
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 15px;
        color: #212529;
    }
    .sku_wrapper {
        font-size: 0.95rem;
        color: #6c757d;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #e9ecef;
    }
    .price {
        font-size: 1.3rem;
        font-weight: 600;
        color: #d63638;
        margin: 15px 0;
        padding: 15px 0;
        border-bottom: 1px solid #e9ecef;
    }

    .awps-section {
        background: #fff;
        padding: 25px;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        margin: 30px 0;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .awps-section h3 {
        font-size: 1.25rem;
        font-weight: 600;
        margin: 0 0 20px 0;
        padding-bottom: 10px;
        border-bottom: 2px solid #007cba;
        color: #212529;
    }
    .awps-section ul {
        padding-left: 20px;
        margin: 0;
    }
    .awps-section ul li {
        margin: 8px 0;
        line-height: 1.6;
        color: #495057;
    }

    .awps-specs-grid {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
    }
    .awps-specs-grid th,
    .awps-specs-grid td {
        padding: 14px;
        text-align: left;
        border-bottom: 1px solid #dee2e6;
    }
    .awps-specs-grid th {
        width: 30%;
        background: #f8f9fa;
        font-weight: 600;
        color: #495057;
    }
    .awps-specs-grid tr:last-child td {
        border-bottom: none;
    }

    .awps-tooltip {
        position: relative;
        cursor: help;
        text-decoration: underline dotted;
        color: #007cba;
    }
    .awps-tooltip .tooltiptext {
        visibility: hidden;
        width: 280px;
        background-color: #333;
        color: #fff;
        text-align: left;
        border-radius: 6px;
        padding: 12px;
        position: absolute;
        z-index: 10;
        bottom: 125%;
        left: 50%;
        margin-left: -140px;
        opacity: 0;
        transition: opacity 0.3s;
        font-size: 0.85rem;
        line-height: 1.5;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }
    .awps-tooltip:hover .tooltiptext {
        visibility: visible;
        opacity: 1;
    }

    #awps-inquiry-form {
        background: #f8f9fa;
        padding: 25px;
        border-radius: 8px;
        border: 1px solid #e9ecef;
    }
    #awps-inquiry-form label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: #495057;
    }
    #awps-inquiry-form input,
    #awps-inquiry-form select,
    #awps-inquiry-form textarea {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #ced4da;
        border-radius: 4px;
        font-size: 1rem;
        margin-bottom: 15px;
        box-sizing: border-box;
    }
    #awps-inquiry-form input:focus,
    #awps-inquiry-form select:focus,
    #awps-inquiry-form textarea:focus {
        outline: none;
        border-color: #007cba;
        box-shadow: 0 0 0 3px rgba(0,124,186,0.1);
    }
    #awps-inquiry-form button {
        background: #007cba;
        color: white;
        border: none;
        padding: 14px 20px;
        font-size: 1.1rem;
        font-weight: 600;
        border-radius: 6px;
        cursor: pointer;
        transition: background 0.2s;
    }
    #awps-inquiry-form button:hover {
        background: #005a87;
    }

    .awps-related-products h3,
    .awps-related-products h4 {
        font-size: 1.25rem;
        font-weight: 600;
        margin: 30px 0 15px 0;
        color: #212529;
    }
    .awps-related-products .grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 25px;
        margin: 20px 0;
    }
    .awps-related-products .product-card {
        border: 1px solid #e9ecef;
        padding: 20px;
        border-radius: 8px;
        background: #fff;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .awps-related-products .product-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .awps-related-products .product-card img {
        width: 100%;
        height: 200px;
        object-fit: cover;
        border-radius: 6px;
        margin-bottom: 15px;
    }
    .awps-related-products .product-card h4 {
        font-size: 1rem;
        margin: 0 0 10px 0;
        font-weight: 600;
        color: #212529;
    }
    .awps-related-products .product-card .price {
        font-weight: 600;
        color: #d63638;
        font-size: 1.1rem;
    }

    .awps-share-section {
        display: flex;
        align-items: center;
        gap: 15px;
        margin: 40px 0;
        padding: 25px;
        background: #f8f9fa;
        border-radius: 8px;
        border: 1px solid #e9ecef;
        flex-wrap: wrap;
    }
    .awps-share-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background: #007cba;
        color: white;
        text-decoration: none;
        border-radius: 6px;
        font-size: 0.95rem;
        font-weight: 500;
        transition: background 0.2s;
    }
    .awps-share-btn:hover {
        background: #005a87;
    }
    .awps-share-section span:last-child {
        margin-left: auto;
        font-size: 0.9rem;
        color: #6c757d;
        white-space: nowrap;
    }
    .awps-share-section a.report {
        color: #dc3545;
        font-weight: 500;
    }

    @media (max-width: 768px) {
        .awps-exporter-card {
            flex-direction: column;
            text-align: center;
        }
        .awps-share-section {
            flex-direction: column;
            align-items: stretch;
        }
        .awps-share-section span:last-child {
            margin-left: 0;
            margin-top: 15px;
            text-align: center;
        }
    }
</style>

<main id="main-content" class="site-main">
    <div class="container">
        <!-- Header Section -->
        <div class="awps-product-header">
            <div class="awps-exporter-name">
                🏷️ You are viewing: <strong><?= esc_html($exporter_name); ?>’s Product</strong>
            </div>
            <div class="awps-badges">
                <?php if ($verified): ?>
                    <span class="awps-badge verified">✅ Verified Supplier</span>
                <?php endif; ?>
                <?php if ($made_in_pakistan): ?>
                    <span class="awps-badge origin">🇵🇰 Made in Pakistan</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Exporter Card -->
        <div class="awps-exporter-card">
            <?php if ($exporter_logo): ?>
                <img src="<?= esc_url($exporter_logo); ?>" alt="<?= esc_attr($exporter_name); ?>" class="awps-exporter-logo">
            <?php else: ?>
                <div class="awps-exporter-logo" style="display: flex; align-items: center; justify-content: center; background: #007cba; color: white; font-weight: bold;">
                    <?= substr($exporter_name, 0, 1); ?>
                </div>
            <?php endif; ?>
            <div class="awps-exporter-info">
                <h4><a href="<?= esc_url($exporter_profile_url); ?>" class="awps-exporter-link"><?= esc_html($exporter_name); ?></a></h4>
                <?php if ($exporter && (get_user_meta($exporter_id, 'city', true) || get_user_meta($exporter_id, 'state', true))): ?>
                    <p style="margin: 8px 0 0 0; font-size: 0.9rem; color: #6c757d;">
                        <?= esc_html(get_user_meta($exporter_id, 'city', true)); ?>, 
                        <?= esc_html(get_user_meta($exporter_id, 'state', true)); ?>, 
                        Pakistan
                    </p>
                <?php endif; ?>
                <?php if ($exporter && get_user_meta($exporter_id, 'whatsapp', true)): ?>
                    <p style="margin: 8px 0 0 0; font-size: 0.9rem; color: #6c757d;">
                        <a href="https://wa.me/<?= esc_attr(str_replace('+', '', get_user_meta($exporter_id, 'whatsapp', true))); ?>" target="_blank" style="color: #25d366; text-decoration: none; font-weight: 500;">
                            📱 WhatsApp: <?= esc_html(get_user_meta($exporter_id, 'whatsapp', true)); ?>
                        </a>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Product Gallery -->
        <div class="awps-hero-gallery">
            <?php
            // Display product gallery
            wc_get_template_part('single-product/product-image');
            ?>
            <div class="awps-product-badges">
                <?php if ($stock_status === 'instock'): ?>
                    <span class="awps-product-badge">In Stock</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Product Info -->
        <div class="summary entry-summary">
            <h1 class="product_title"><?= esc_html($product->get_name()); ?></h1>
            <div class="sku_wrapper">
                <strong>SKU:</strong> <?= esc_html($product->get_sku()); ?> | 
                <strong>Category:</strong> <?= wc_get_product_category_list($product->get_id()); ?>
            </div>

            <?php if ($product->is_type('variable')): ?>
                <?php wc_get_template('single-product/price.php'); ?>
                <p><em>*Get Exact Quote</em></p>
            <?php else: ?>
                <?php if ($product->get_price()): ?>
                    <p class="price">
                        <strong>💰 Ex-Factory Price:</strong> 
                        <?= wc_price($product->get_price()); ?> 
                        <?php if ($incoterms): ?>
                            / (<?= esc_html($incoterms); ?> 
                            <?php if ($incoterm_description): ?>
                                <span class="awps-tooltip">
                                    ℹ️
                                    <span class="tooltiptext"><?= esc_html($incoterm_description); ?></span>
                                </span>
                            <?php endif; ?>)
                        <?php endif; ?>
                        — <em>Get Exact Quote</em>
                    </p>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($moq): ?>
                <p><strong>📦 MOQ:</strong> <?= esc_html($moq); ?></p>
            <?php endif; ?>

            <?php if (!empty($packaging_list)): ?>
                <p><strong>🧾 Packaging:</strong> <?= esc_html(implode(', ', $packaging_list)); ?></p>
            <?php endif; ?>

            <?php if ($lead_time): ?>
                <p><strong>⏱️ Lead Time:</strong> <?= esc_html($lead_time); ?></p>
            <?php endif; ?>

            <?php if (!empty($shipping_list)): ?>
                <p><strong>🚚 Shipping:</strong> <?= esc_html(implode(', ', $shipping_list)); ?></p>
            <?php endif; ?>

            <?php if (!empty($payment_list)): ?>
                <p><strong>💵 Payment:</strong> <?= esc_html(implode(', ', $payment_list)); ?></p>
            <?php endif; ?>
        </div>

        <!-- Product Description -->
        <?php if ($product->get_description()): ?>
            <div class="awps-section">
                <h3>📝 Product Description:</h3>
                <div><?= wp_kses_post($product->get_description()); ?></div>
            </div>
        <?php endif; ?>

        <!-- Key Benefits -->
        <?php if (!empty($benefits_list)): ?>
            <div class="awps-section">
                <h3>✅ Key Benefits:</h3>
                <ul>
                    <?php foreach ($benefits_list as $benefit): ?>
                        <li><?= esc_html($benefit); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Quality Control -->
        <?php if (!empty($quality_list)): ?>
            <div class="awps-section">
                <h3>🧪 Quality Control:</h3>
                <ul>
                    <?php foreach ($quality_list as $qc): ?>
                        <li><?= esc_html($qc); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Sample Details -->
        <?php if (!empty($sample_list)): ?>
            <div class="awps-section">
                <h3>📦 Sample Details:</h3>
                <ul>
                    <?php foreach ($sample_list as $sample): ?>
                        <li><?= esc_html($sample); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Certifications -->
        <?php if (!empty($certifications)): ?>
            <div class="awps-section">
                <h3>🏅 Product Certifications:</h3>
                <p><?= esc_html($certifications); ?></p>
            </div>
        <?php endif; ?>

        <!-- Specifications Table -->
        <?php if ($hs_code || $moq || !empty($packaging_list) || $lead_time || !empty($shipping_list) || !empty($payment_list) || $incoterms): ?>
            <div class="awps-section">
                <h3>📋 Technical Specifications:</h3>
                <table class="awps-specs-grid">
                    <tr>
                        <th>Parameter</th>
                        <th>Value</th>
                    </tr>
                    <?php if ($hs_code): ?>
                        <tr><td>HS Code</td><td><?= esc_html($hs_code); ?></td></tr>
                    <?php endif; ?>
                    <?php if ($moq): ?>
                        <tr><td>MOQ</td><td><?= esc_html($moq); ?></td></tr>
                    <?php endif; ?>
                    <?php if (!empty($packaging_list)): ?>
                        <tr><td>Packaging</td><td><?= esc_html(implode(', ', $packaging_list)); ?></td></tr>
                    <?php endif; ?>
                    <?php if ($lead_time): ?>
                        <tr><td>Lead Time</td><td><?= esc_html($lead_time); ?></td></tr>
                    <?php endif; ?>
                    <?php if (!empty($shipping_list)): ?>
                        <tr><td>Shipping Methods</td><td><?= esc_html(implode(', ', $shipping_list)); ?></td></tr>
                    <?php endif; ?>
                    <?php if (!empty($payment_list)): ?>
                        <tr><td>Payment Terms</td><td><?= esc_html(implode(', ', $payment_list)); ?></td></tr>
                    <?php endif; ?>
                    <?php if ($incoterms): ?>
                        <tr><td>Incoterms</td><td>
                            <?= esc_html($incoterms); ?>
                            <?php if ($incoterm_description): ?>
                                <span class="awps-tooltip">
                                    ℹ️
                                    <span class="tooltiptext"><?= esc_html($incoterm_description); ?></span>
                                </span>
                            <?php endif; ?>
                        </td></tr>
                    <?php endif; ?>
                </table>
            </div>
        <?php endif; ?>

        <?php if (!empty($cert_titles)): ?>
            <div class="awps-section">
                <h3>🏅 Certifications:</h3>
                <p><?= esc_html(implode(', ', $cert_titles)); ?></p>
            </div>
        <?php endif; ?>

        <!-- Factory Video -->
        <?php if ($factory_video): ?>
            <div class="awps-section">
                <h3>🎥 Factory/Processing Video</h3>
                <?php if (strpos($factory_video, 'youtube') !== false || strpos($factory_video, 'youtu.be') !== false): ?>
                    <?php
                    preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $factory_video, $match);
                    $youtube_id = $match[1] ?? '';
                    if ($youtube_id): ?>
                        <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 8px;">
                            <iframe src="https://www.youtube.com/embed/<?= esc_attr($youtube_id); ?>" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none;" allowfullscreen></iframe>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <video controls width="100%" style="border-radius: 8px; max-height: 500px;">
                        <source src="<?= esc_url($factory_video); ?>" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Product Gallery -->
        <?php
        $attachment_ids = $product->get_gallery_image_ids();
        if ($attachment_ids) {
            echo '<div class="awps-section">';
            echo '<h3>🖼️ Product Gallery</h3>';
            echo '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-top: 20px;">';
            foreach ($attachment_ids as $attachment_id) {
                $image_url = wp_get_attachment_image_url($attachment_id, 'large');
                $full_url = wp_get_attachment_image_url($attachment_id, 'full');
                echo '<a href="' . esc_url($full_url) . '" target="_blank">';
                echo '<img src="' . esc_url($image_url) . '" style="width:100%; height:200px; object-fit: cover; border-radius: 8px; cursor: zoom-in; border: 1px solid #eee;" />';
                echo '</a>';
            }
            echo '</div>';
            echo '</div>';
        }
        ?>

        <!-- Inquiry Form -->
        <div class="awps-section">
            <h3>📩 Send RFQ to: <strong><?= esc_html($exporter_name); ?></strong></h3>
            <form id="awps-inquiry-form" method="post">
                <p>
                    <label>Name*<br>
                    <input type="text" name="inquiry_name" required></label>
                </p>
                <p>
                    <label>Company*<br>
                    <input type="text" name="inquiry_company" required></label>
                </p>
                <p>
                    <label>Email*<br>
                    <input type="email" name="inquiry_email" required></label>
                </p>
                <p>
                    <label>Country*<br>
                    <select name="inquiry_country" required>
                        <option value="">Select Country</option>
                        <?php
                        $countries = WC()->countries->get_countries();
                        foreach ($countries as $code => $name) {
                            echo '<option value="' . esc_attr($code) . '">' . esc_html($name) . '</option>';
                        }
                        ?>
                    </select>
                </label>
                </p>
                <p>
                    <label>Quantity*<br>
                    <input type="number" name="inquiry_quantity" min="1" step="any" required placeholder="Tons / Metric Tons"></label>
                </p>
                <p>
                    <label>Message<br>
                    <textarea name="inquiry_message" rows="4" placeholder="Looking for monthly supply. Need CIF Dubai pricing."></textarea></label>
                </p>
                <p>
                    <label><input type="checkbox" name="inquiry_gdpr" required> 🔒 GDPR: “We don’t share your data.”</label>
                </p>
                <p>
                    <button type="submit" class="button">📨 REQUEST QUOTE</button>
                </p>
                <div id="inquiry-response" style="margin-top:20px; padding:15px; border-radius:6px; display:none;"></div>
            </form>
        </div>

        <!-- Related Products -->
        <div class="awps-related-products">
            <h3>► RELATED PRODUCTS</h3>
            
            <h4>From Same Exporter</h4>
            <?php
            $same_exporter_args = [
                'post_type' => 'product',
                'posts_per_page' => 4,
                'author' => $exporter_id,
                'post__not_in' => [$product->get_id()],
                'orderby' => 'rand'
            ];
            $same_exporter_query = new WP_Query($same_exporter_args);
            if ($same_exporter_query->have_posts()):
                echo '<div class="grid">';
                while ($same_exporter_query->have_posts()): $same_exporter_query->the_post();
                    global $product;
                    $current_product = wc_get_product(get_the_ID());
                    echo '<div class="product-card">';
                    echo '<a href="' . get_permalink() . '">';
                    echo get_the_post_thumbnail(get_the_ID(), 'woocommerce_thumbnail');
                    echo '<h4>' . get_the_title() . '</h4>';
                    echo '</a>';
                    echo '<span class="price">' . wc_price($current_product->get_price()) . '</span>';
                    echo '</div>';
                endwhile;
                echo '</div>';
                wp_reset_postdata();
            else:
                echo '<p>No other products from this exporter.</p>';
            endif;
            ?>

            <h4>Similar Products from Other Exporters</h4>
            <?php
            $terms = wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'ids']);
            if ($terms) {
                $similar_args = [
                    'post_type' => 'product',
                    'posts_per_page' => 4,
                    'post__not_in' => [$product->get_id()],
                    'tax_query' => [
                        [
                            'taxonomy' => 'product_cat',
                            'field' => 'term_id',
                            'terms' => $terms,
                        ]
                    ],
                    'orderby' => 'rand'
                ];
                $similar_query = new WP_Query($similar_args);
                if ($similar_query->have_posts()):
                    echo '<div class="grid">';
                    while ($similar_query->have_posts()): $similar_query->the_post();
                        global $product;
                        $current_product = wc_get_product(get_the_ID());
                        echo '<div class="product-card">';
                        echo '<a href="' . get_permalink() . '">';
                        echo get_the_post_thumbnail(get_the_ID(), 'woocommerce_thumbnail');
                        echo '<h4>' . get_the_title() . '</h4>';
                        echo '</a>';
                        echo '<span class="price">' . wc_price($current_product->get_price()) . '</span>';
                        echo '</div>';
                    endwhile;
                    echo '</div>';
                    wp_reset_postdata();
                endif;
            }
            ?>
        </div>

        <!-- Share + SEO -->
        <div class="awps-share-section">
            <strong>Share + SEO</strong>
            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode(get_permalink()); ?>" class="awps-share-btn" target="_blank">LinkedIn</a>
            <a href="https://api.whatsapp.com/send?text=<?= urlencode(get_the_title() . ' - ' . get_permalink()); ?>" class="awps-share-btn" target="_blank">WhatsApp</a>
            <a href="mailto:?subject=<?= urlencode(get_the_title()); ?>&body=<?= urlencode('Check this product: ' . get_permalink()); ?>" class="awps-share-btn">Email</a>
            <span>
                Last Updated: <?= get_the_modified_date('F Y'); ?> | 
                <a href="#" class="report">Report Listing ❗</a>
            </span>
        </div>
    </div>
</main>

<script>
jQuery(document).ready(function($) {
    // AJAX Inquiry Form (placeholder — implement handler later)
    $('#awps-inquiry-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        formData.append('action', 'awps_submit_inquiry');
        formData.append('product_id', <?= $product->get_id(); ?>);
        formData.append('security', '<?= wp_create_nonce('awps-inquiry-nonce'); ?>');

        $.ajax({
            url: '<?= admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#inquiry-response')
                        .html('<div style="background:#d4edda; color:#155724; padding:15px; border-radius:6px;">' + response.data.message + '</div>')
                        .show();
                    $('#awps-inquiry-form')[0].reset();
                } else {
                    $('#inquiry-response')
                        .html('<div style="background:#f8d7da; color:#721c24; padding:15px; border-radius:6px;">' + response.data.message + '</div>')
                        .show();
                }
            },
            error: function() {
                $('#inquiry-response')
                    .html('<div style="background:#f8d7da; color:#721c24; padding:15px; border-radius:6px;">Error submitting form. Please try again.</div>')
                    .show();
            }
        });
    });
});
</script>

<?php
// Load footer
get_footer();