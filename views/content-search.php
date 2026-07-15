<?php
/**
 * Template part for displaying search results
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 * @package awps
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class('search-result-card'); ?>>

	<?php if ( has_post_thumbnail() ) : ?>
		<div class="search-result-thumb">
			<a href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
				<?php the_post_thumbnail( 'medium', array( 'loading' => 'lazy', 'decoding' => 'async' ) ); ?>
			</a>
		</div>
	<?php else : ?>
		<div class="search-result-thumb">
			<a href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
				<img src="<?php echo esc_url( get_theme_file_uri( '/assets/dist/images/image-placeholder.png' ) ); ?>" 
					 alt="<?php esc_attr_e( 'Placeholder image', 'awps' ); ?>"
					 width="300" height="194"
					 loading="lazy">
			</a>
		</div>
	<?php endif; ?>

	<div class="articlecontent">
		<header class="entry-header">
			<h2 class="entry-title">
				<a href="<?php echo esc_url( get_permalink() ); ?>" rel="bookmark">
					<?php the_title(); ?>
				</a>
			</h2>

			<?php if ( 'post' === get_post_type() ) : ?>
				<div class="entry-meta">
					<?php Awps\Core\Tags::posted_on(); ?>
				</div><!-- .entry-meta -->
			<?php endif; ?>
		</header><!-- .entry-header -->

		<div class="entry-content">
			<?php the_excerpt(); ?>
		</div><!-- .entry-content -->

		<a href="<?php echo esc_url( get_permalink() ); ?>" class="read-more">
			<?php esc_html_e( 'Read More', 'awps' ); ?> <span aria-hidden="true">&rarr;</span>
		</a>
	</div>

</article><!-- #post-<?php the_ID(); ?> -->