<?php
/**
 * The template for displaying archive pages
 *
 * @package awps
 */

get_header();

// Archive header callback (title + description)
$archive_header = function() {
    ?>
    <header class="page-header">
        <?php
        the_archive_title( '<h1 class="page-title">', '</h1>' );
        the_archive_description( '<div class="archive-description">', '</div>' );
        ?>
    </header>
    <?php
};

// Load reusable loop with archive-specific config
get_template_part( 'views/partials/main-loop', null, array(
    'content_template' => get_post_format(),
    'show_pagination'  => true,
    'before_loop'      => $archive_header,
) );

get_footer();