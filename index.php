<?php
/**
 * The main template file
 *
 * @package awps
 */

get_header();

// Optional header callback for blog home (breadcrumbs, title)
$blog_header = function() {
    if ( is_home() && ! is_front_page() ) :
        ?>
        <header class="page-header">
            <h1 class="page-title"><?php single_post_title(); ?></h1>
            <?php do_action( 'awps_breadcrumb' ); ?>
        </header>
        <?php
    endif;
};

// Load reusable loop with blog-specific config
get_template_part( 'views/partials/main-loop', null, array(
    'content_template' => get_post_format(),
    'show_pagination'  => true,
    'before_loop'      => $blog_header,
) );

get_footer();