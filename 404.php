<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * @link https://codex.wordpress.org/Creating_an_Error_404_Page
 *
 * @package awps
 */

get_header();
?>

<div class="container notfound">
    <div class="row">
        <div class="col-sm-12">
            <div id="primary" class="content-area">
                <main id="main" class="site-main" role="main">

                    <?php
                    get_template_part( 'views/partials/empty-state', null, array(
                        'heading'     => __( 'Oops! That page can&rsquo;t be found.', 'awps' ),
                        'message'     => __( "It seems we can't find what you're looking for. Perhaps searching can help or go back to", 'awps' ),
                        'link_url'    => home_url( '/' ),
                        'link_text'   => __( 'Homepage', 'awps' ),
                        'show_search' => true,
                        'image_alt'   => __( '404 error - page not found illustration', 'awps' ),
                        'css_class'   => 'notfound-empty-state',
                    ) );
                    ?>

                </main><!-- #main -->
            </div><!-- #primary -->
        </div><!-- .col-sm-12 -->
    </div><!-- .row -->
</div><!-- .container -->

<?php
get_footer();