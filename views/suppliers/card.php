<?php
/**
 * Supplier Card Template Part
 * 
 * Displays a supplier card in archive/search views.
 * 
 * @package awps
 */

// Now using global $post (from WP_Query loop)
$banner = get_post_meta( $post->ID, 'banner_image', true );
$logo   = get_post_meta( $post->ID, 'company_logo', true );
$company = get_post_meta( $post->ID, 'company_name', true ) ?: $post->post_title;
$city = get_post_meta( $post->ID, 'city', true );
$state = get_post_meta( $post->ID, 'state', true );

// Normalize banner + logo URLs (consistent with theme architecture)
$banner_url = ( is_string( $banner ) && $banner ) 
    ? esc_url( $banner ) 
    : esc_url( get_theme_file_uri( '/assets/images/default-banner.jpg' ) );

$logo_url = ( is_string( $logo ) && $logo ) 
    ? esc_url( $logo ) 
    : esc_url( get_theme_file_uri( '/assets/images/default-logo.png' ) );

// Link to exporter profile using post slug
$profile_url = esc_url( get_permalink( $post ) );

// Prepare accessible label
$card_aria_label = sprintf(
    /* translators: %1$s: Company name, %2$s: Location */
    __( 'View profile for %1$s in %2$s, Pakistan', 'awps' ),
    $company,
    $city ?: $state ?: __( 'Unknown location', 'awps' )
);
?>

<article <?php post_class( 'exporter-card' ); ?>>
    <a href="<?php echo $profile_url; ?>" class="exporter-card__link" aria-label="<?php echo esc_attr( $card_aria_label ); ?>">
        
        <!-- Background Banner -->
        <div class="card-bg" style="background-image: url('<?php echo $banner_url; ?>');"></div>
        <div class="card-overlay"></div>
        
        <!-- Card Content -->
        <div class="card-content">
            <h3 class="card-title"><?php echo esc_html( $company ); ?></h3>
            <p class="card-location">
                <?php echo esc_html( $city ?: '—' ); ?>,
                <?php echo esc_html( $state ?: '—' ); ?>,
                Pakistan
            </p>
        </div>
        
        <!-- Company Logo -->
        <div class="company-logo">
            <img src="<?php echo $logo_url; ?>" 
                 alt="<?php echo esc_attr( sprintf( __( '%s company logo', 'awps' ), $company ) ); ?>"
                 width="80" 
                 height="80"
                 loading="lazy"
                 decoding="async"
                 class="logo-image">
        </div>
        
    </a>
</article>