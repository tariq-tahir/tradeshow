<?php
/**
 * Product card for category archive.
 * Shows: image, title, supplier name, certifications, MOQ, HS code.
 * Entire card is a single link to the product.
 *
 * @package AWPS
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $post;
$product_id = get_the_ID();

// Get supplier data using custom helper
$supplier_data = \AWPS\Suppliers\SupplierProducts::get_supplier_data( $product_id );
$supplier_name = $supplier_data['name'] ?? __( 'Supplier', 'awps' );

// Get product meta
$product_meta = \AWPS\Suppliers\SupplierProducts::get_all_meta( $product_id );
$made_in_pakistan = $product_meta['made_in_pakistan'] === 'yes';

// Prepare accessible labels
$product_title = get_the_title();
$card_aria_label = sprintf(
    /* translators: %1$s: Product title, %2$s: Supplier name */
    __( 'View details for %1$s by %2$s', 'awps' ),
    $product_title,
    $supplier_name
);
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'product-card' ); ?>>
    
    <a href="<?php echo esc_url( get_permalink() ); ?>" class="product-card__link" aria-label="<?php echo esc_attr( $card_aria_label ); ?>">
        
        <div class="product-card__image">
            <?php if ( has_post_thumbnail() ) : ?>
                <?php the_post_thumbnail( 'medium', array( 
                    'class' => 'img-fluid',
                    'loading' => 'lazy',
                    'decoding' => 'async',
                    'alt' => sprintf( __( '%s product image', 'awps' ), $product_title )
                ) ); ?>
                
                <?php if ( $made_in_pakistan ) : ?>
                    <span class="made-in-pakistan-badge" aria-label="<?php esc_attr_e( 'Made in Pakistan', 'awps' ); ?>">
                        🇵🇰
                    </span>
                <?php endif; ?>
                
            <?php else : ?>
                <div class="placeholder-image" aria-hidden="true">
                    <img src="<?php echo esc_url( get_theme_file_uri( '/assets/dist/images/image-placeholder.png' ) ); ?>" 
                         alt="<?php esc_attr_e( 'Product placeholder image', 'awps' ); ?>"
                         width="300" height="194"
                         loading="lazy">
                </div>
            <?php endif; ?>
        </div>
        
    </a>
    
    <div class="product-card__info">
        <?php if ( $supplier_data && ! empty( $supplier_data['profile_url'] ) ) : ?>
            <a href="<?php echo esc_url( $supplier_data['profile_url'] ); ?>" 
               class="company-name-link" 
               aria-label="<?php echo esc_attr( sprintf( __( 'View %s profile', 'awps' ), $supplier_name ) ); ?>">
                <div class="company-name"><?php echo esc_html( $supplier_name ); ?></div>
            </a>
        <?php else : ?>
            <div class="company-name"><?php echo esc_html( $supplier_name ); ?></div>
        <?php endif; ?>
        
        <h3 class="product-title">
            <a href="<?php echo esc_url( get_permalink() ); ?>">
                <?php echo esc_html( $product_title ); ?>
            </a>
        </h3>
    </div>
    
</article><!-- #post-<?php the_ID(); ?> -->