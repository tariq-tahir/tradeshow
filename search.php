<?php
/**
 * The template for displaying search results pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#search-result
 *
 * @package awps
 */

get_header();

// Search header callback (title + results count)
$search_header = function() {
    global $wp_query;
    $query = get_search_query();
    $count = $wp_query->found_posts;
    ?>
    <header class="page-header">
        <h1 class="page-title">
            <?php
            printf(
                /* translators: %s: Search term */
                esc_html__( 'Search Results for: %s', 'awps' ),
                '<span>' . esc_html( $query ) . '</span>'
            );
            ?>
        </h1>
        <?php if ( $count > 0 ) : ?>
            <p class="search-results-count">
                <?php
                printf(
                    /* translators: %d: Number of results */
                    esc_html( _n( '%d result found', '%d results found', $count, 'awps' ) ),
                    absint( $count )
                );
                ?>
            </p>
        <?php endif; ?>
    </header>
    <?php
};

// Load reusable loop with search-specific config
get_template_part( 'views/partials/main-loop', null, array(
    'content_template' => 'search',        // Uses views/content-search.php
    'show_pagination'  => true,            // Enable pagination for results
    'before_loop'      => $search_header,  // Inject search header before loop
) );

get_footer();