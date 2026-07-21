<?php
/**
 * Template Name: Suppliers Archive
 * Description: Custom archive for Supplier CPT
 *
 * @package AWPS
 */

defined( 'ABSPATH' ) || exit;

get_header();

echo do_shortcode('[suppliers_directory]');

?>


<!-- Page Description (content of this /suppliers/ page) -->
<?php if ( have_posts() ) : the_post(); ?>
    <?php if ( ! empty( get_the_content() ) ) : ?>
        <div class="suppliers-page-description">
            <?php the_content(); ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php get_footer(); ?>