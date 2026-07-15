<?php
/**
 * Template part for displaying a single post
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 * @package awps
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class('single-post'); ?>>

	<header class="entry-header">
		<h1 class="entry-title">
			<?php the_title(); ?>
		</h1>

		<?php
		// Replaced inline shortcode with action hook for cleaner architecture
		do_action( 'awps_single_post_breadcrumb' );
		?>

		<div class="entry-format format-image">
			<?php if ( has_post_thumbnail() ) : ?>
				<div class="post-header-thumb">
					<?php the_post_thumbnail( 'large', array( 'loading' => 'eager', 'decoding' => 'async' ) ); ?>
				</div>
			<?php endif; ?>
		</div>

		<div class="entry-meta-wrapper">
			<?php Awps\Core\Tags::posted_on(); ?>

			<?php
			$categories = Awps\Core\Tags::get_post_categories_with_links();
			if ( ! empty( $categories ) ) :
				?>
				<span class="post-categories">
					<?php esc_html_e( 'in', 'awps' ); ?>
					<?php
					$total = count( $categories );
					foreach ( $categories as $index => $cat ) :
						?>
						<a href="<?php echo esc_url( $cat['url'] ); ?>" class="post-category">
							<?php echo esc_html( $cat['name'] ); ?>
						</a>
						<?php if ( $index < $total - 1 ) : ?>, <?php endif; ?>
					<?php endforeach; ?>
				</span>
				<?php
			endif;
			?>
		</div>
	</header><!-- .entry-header -->

	<div class="entry-content">
		<div class="entry-content-top">
			<div class="entry-desc">
				<?php
				the_content();

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
		</div>
	</div><!-- .entry-content -->

	<?php
	// Optional: Add tags, share buttons, or author bio here via hooks
	do_action( 'awps_after_single_post_content' );
	?>

</article><!-- #post-<?php the_ID(); ?> -->