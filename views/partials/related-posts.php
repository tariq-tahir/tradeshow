<?php
/**
 * Related Posts Template Part
 * 
 * Displays related posts based on shared categories.
 * 
 * @package awps
 * 
 * @param array $args {
 *     @type int    $post_id   Current post ID to exclude from results
 *     @type string $post_type Post type to query (default: 'post')
 *     @type int    $limit     Number of related posts to show (default: 3)
 *     @type string $title     Section heading (translatable)
 * }
 */

$defaults = array(
    'post_id'   => get_the_ID(),
    'post_type' => 'post',
    'limit'     => 3,
    'title'     => __( 'Related Posts', 'awps' ),
);
$args = wp_parse_args( $args, $defaults );

// Get categories for the current post
$categories = wp_get_post_categories( $args['post_id'] );

// Bail if no categories or not a post type that supports categories
if ( empty( $categories ) || ! is_object_in_taxonomy( $args['post_type'], 'category' ) ) {
    return;
}

// Query related posts
$related_args = array(
    'post_type'           => $args['post_type'],
    'posts_per_page'      => $args['limit'],
    'post__not_in'        => array( $args['post_id'] ),
    'category__in'        => $categories,
    'ignore_sticky_posts' => true,
    'orderby'             => 'rand', // Randomize for variety
);

$related = new WP_Query( $related_args );

// Bail if no related posts found
if ( ! $related->have_posts() ) {
    return;
}
?>

<section class="related-posts" aria-label="<?php echo esc_attr( $args['title'] ); ?>">
    <h3 class="related-posts-title">
        <?php echo esc_html( $args['title'] ); ?>
    </h3>

    <div class="related-posts-grid">
        <?php while ( $related->have_posts() ) : $related->the_post(); ?>
            <article class="related-post-card">
                <a href="<?php the_permalink(); ?>" class="related-post-thumb" aria-hidden="true" tabindex="-1">
                    <?php if ( has_post_thumbnail() ) : ?>
                        <?php the_post_thumbnail( 'medium', array( 'loading' => 'lazy' ) ); ?>
                    <?php else : ?>
                        <img src="<?php echo esc_url( get_theme_file_uri( '/assets/dist/images/image-placeholder.png' ) ); ?>" 
                             alt="<?php esc_attr_e( 'Placeholder image', 'awps' ); ?>"
                             width="300" height="194">
                    <?php endif; ?>
                </a>

                <h4 class="related-post-title">
                    <a href="<?php the_permalink(); ?>">
                        <?php the_title(); ?>
                    </a>
                </h4>

                <?php if ( has_excerpt() ) : ?>
                    <p class="related-post-excerpt">
                        <?php echo wp_kses_post( get_the_excerpt() ); ?>
                    </p>
                <?php endif; ?>
            </article>
        <?php endwhile; ?>
    </div>
</section>

<?php
wp_reset_postdata();