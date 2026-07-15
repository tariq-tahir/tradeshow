<?php
/**
 * Template part for displaying posts in archives and loops
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 * @package awps
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class('archive-card'); ?>>
	
	<header class="entry-header">
		<div class="entry-format format-image">
			<a class="entry-image" href="<?php echo esc_url( get_permalink() ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Read more about %s', 'awps' ), get_the_title() ) ); ?>">
				<?php if ( has_post_thumbnail() ) : ?>
					<div class="post-header-thumb">
						<?php the_post_thumbnail( 'large', array( 'loading' => 'lazy', 'decoding' => 'async' ) ); ?>
					</div>
				<?php else : ?>
					<div class="post-header-thumb post-header-thumb-placeholder">
						<img src="<?php echo esc_url( get_theme_file_uri( '/assets/dist/images/image-placeholder.png' ) ); ?>" 
							 alt="<?php esc_attr_e( 'Placeholder image', 'awps' ); ?>"
							 width="800" height="400"
							 loading="lazy">
					</div>
				<?php endif; ?>
			</a>
		</div>
	</header><!-- .entry-header -->

	<div class="entry-content">
		<div class="entry-content-top">
			
			<h2 class="entry-title">
				<a href="<?php echo esc_url( get_permalink() ); ?>" rel="bookmark">
					<?php the_title(); ?>
				</a>
			</h2>

			<div class="entry-desc">
				<?php
				the_excerpt();
				?>
			</div>

			<a href="<?php echo esc_url( get_permalink() ); ?>" class="read-more" aria-label="<?php echo esc_attr( sprintf( __( 'Continue reading %s', 'awps' ), get_the_title() ) ); ?>">
				<?php esc_html_e( 'Read More', 'awps' ); ?> <span aria-hidden="true">&rarr;</span>
			</a>

			<?php
			wp_link_pages(
				array(
					'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'awps' ),
					'after'  => '</div>',
					'link_before' => '<span class="page-number">',
					'link_after'  => '</span>',
				)
			);
			?>
		</div>

		<div class="entry-content-bottom">
			<?php Awps\Core\Tags::posted_on(); ?>
		</div>
	</div><!-- .entry-content -->

</article><!-- #post-<?php the_ID(); ?> -->