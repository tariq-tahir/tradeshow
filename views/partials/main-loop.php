<?php
/**
 * Main Query Loop Template Part
 * 
 * Reusable loop for archive, index, search, and blog pages.
 * 
 * @package awps
 * 
 * @param array $args {
 *     @type string $content_template  Slug for get_template_part (e.g., '', 'page', 'none')
 *     @type bool   $show_pagination   Whether to show pagination
 *     @type callable $before_loop     Optional callback to run before loop (e.g., archive header)
 * }
 */

$defaults = array(
    'content_template' => get_post_format(),
    'show_pagination'  => true,
    'before_loop'      => null,
);
$args = wp_parse_args( $args, $defaults );

// Optional pre-loop callback (e.g., archive title)
if ( is_callable( $args['before_loop'] ) ) {
    call_user_func( $args['before_loop'] );
}

if ( have_posts() ) :

    while ( have_posts() ) :
        the_post();
        get_template_part( 'views/content', $args['content_template'] );
    endwhile;

    if ( $args['show_pagination'] && function_exists( 'custom_navigation' ) ) :
        custom_navigation();
    endif;

else :

    get_template_part( 'views/content', 'none' );

endif;