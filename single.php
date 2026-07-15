<?php
/**
 * The template for displaying all single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package awps
 */

get_header();



// Load reusable loop with single-post config
get_template_part( 'views/partials/main-loop', null, array(
    'content_template' => 'single-post',  // Uses views/content-single-post.php
    'show_pagination'  => false,          // Single posts don't paginate themselves
    //'before_loop'      => $single_header, // Inject header before content
) );

// Related posts section (extracted to template part for reusability)
get_template_part( 'views/partials/related-posts', null, array(
    'post_id'      => get_the_ID(),
    'post_type'    => get_post_type(),
    'limit'        => 3,
    'title'        => __( 'Related Posts', 'awps' ),
) );

// Comments for single posts
if ( comments_open() || get_comments_number() ) :
    comments_template();
endif;

get_footer();