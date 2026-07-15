<?php
/**
 * Template part for displaying a message that posts cannot be found
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 * @package awps
 */

?>

<section class="no-results not-found">
    <header class="page-header">
        <h1 class="page-title"><?php esc_html_e( 'Nothing Found', 'awps' ); ?></h1>
    </header><!-- .page-header -->

    <div class="page-content">
        <?php if ( is_home() && current_user_can( 'publish_posts' ) ) : ?>

            <p>
                <?php
                printf(
                    wp_kses(
                        /* translators: %s: Link to create new post */
                        __( 'Ready to publish your first post? <a href="%s">Get started here</a>.', 'awps' ),
                        array(
                            'a' => array( 'href' => array() ),
                        )
                    ),
                    esc_url( admin_url( 'post-new.php' ) )
                );
                ?>
            </p>

        <?php elseif ( is_search() ) : ?>

            <p><?php esc_html_e( 'Sorry, but nothing matched your search terms. Please try again with different keywords.', 'awps' ); ?></p>
            
            <div class="search-form-container">
                <?php get_search_form(); ?>
            </div>

        <?php else : ?>

            <p><?php esc_html_e( 'It seems we can&rsquo;t find what you&rsquo;re looking for. Perhaps searching can help.', 'awps' ); ?></p>
            
            <div class="search-form-container">
                <?php get_search_form(); ?>
            </div>

        <?php endif; ?>
    </div><!-- .page-content -->
</section><!-- .no-results -->