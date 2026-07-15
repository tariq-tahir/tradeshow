<?php
/**
 * Template part for displaying page content in page.php
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 * @package awps
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'page-content-wrapper' ); ?>>

    <?php
    // Replaced inline shortcode with action hook for cleaner architecture
    do_action( 'awps_page_breadcrumb' );
    ?>

    <div class="entry-content">
        <?php
        the_content();

        wp_link_pages(
            array(
                'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'awps' ),
                'after'  => '</div>',
                'link_before' => '<span class="page-number">',
                'link_after'  => '</span>',
            )
        );
        ?>
    </div><!-- .entry-content -->

</article><!-- #post-<?php the_ID(); ?> -->