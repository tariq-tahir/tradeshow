<?php
/**
 * Empty State Template Part
 * 
 * Reusable component for 404, no-results, empty archives, etc.
 * 
 * @package awps
 * 
 * @param array $args {
 *     Optional. Array of arguments to customize the empty state.
 * 
 *     @type string $image_url      URL/path to illustration image. Default: theme 404 image.
 *     @type string $image_alt      Alt text for image. Default: 'Illustration'.
 *     @type string $heading        Heading text (translatable string key). Default: 'Oops! That page can&rsquo;t be found.'.
 *     @type string $message        Message text (translatable string key). Default: 'It seems we can\'t find what you\'re looking for.'.
 *     @type bool   $show_search    Whether to display search form. Default: true.
 *     @type string $link_url       Optional CTA link URL. Default: homepage.
 *     @type string $link_text      Optional CTA link text (translatable). Default: 'Homepage'.
 *     @type string $css_class      Additional CSS classes for wrapper. Default: empty.
 * }
 */

$defaults = array(
    'image_url'   => get_theme_file_uri( '/assets/dist/images/image-404.jpg' ),
    'image_alt'   => __( 'Page not found illustration', 'awps' ),
    'heading'     => 'Oops! That page can&rsquo;t be found.',
    'message'     => 'It seems we can\'t find what you\'re looking for. Perhaps searching can help.',
    'show_search' => true,
    'link_url'    => home_url( '/' ),
    'link_text'   => 'Homepage',
    'css_class'   => '',
);
$args = wp_parse_args( $args, $defaults );
?>

<div class="empty-state <?php echo esc_attr( $args['css_class'] ); ?>">
    
    <?php if ( $args['image_url'] ) : ?>
        <img src="<?php echo esc_url( $args['image_url'] ); ?>" 
             alt="<?php echo esc_attr( $args['image_alt'] ); ?>"
             class="empty-state-image"
             width="800"
             height="400"
             loading="lazy">
    <?php endif; ?>

    <h1 class="empty-state-heading">
        <?php esc_html_e( $args['heading'], 'awps' ); ?>
    </h1>

    <p class="empty-state-message">
        <?php 
        if ( $args['link_url'] && $args['link_text'] ) {
            printf(
                /* translators: %s: Link to homepage or other CTA */
                esc_html__( $args['message'] . ' %s', 'awps' ),
                '<a href="' . esc_url( $args['link_url'] ) . '" class="empty-state-link">' . esc_html__( $args['link_text'], 'awps' ) . '</a>'
            );
        } else {
            esc_html_e( $args['message'], 'awps' );
        }
        ?>
    </p>

    <?php if ( $args['show_search'] ) : ?>
        <div class="empty-state-search">
            <?php get_search_form(); ?>
        </div>
    <?php endif; ?>

</div>

<?php
// Optional: Structured data for SEO (uncomment if needed)
/*
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "WebPage",
    "name": "<?php echo esc_js( __( $args['heading'], 'awps' ) ); ?>",
    "description": "<?php echo esc_js( __( $args['message'], 'awps' ) ); ?>"
}
</script>
*/
?>