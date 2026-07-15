<?php
/**
 * The template for displaying all pages
 *
 * @package awps
 */

get_header();

// Pages use different content template + no pagination
get_template_part( 'views/partials/main-loop', null, array(
    'content_template' => 'page',
    'show_pagination'  => false,
    'before_loop'      => null, // Pages don't need archive-style headers
) );

// Comments are handled separately for pages
if ( comments_open() || get_comments_number() ) :
    comments_template();
endif;

get_footer();