<?php

namespace Awps\Core;

/**
 * Tags.
 */
class Tags
{
	/**
	 * register default hooks and actions for WordPress
	 * @return
	 */
	public function register()
		{
			add_action( 'edit_category', array( $this, 'category_transient_flusher' ) );
			add_action( 'save_post', array( $this, 'category_transient_flusher' ) );
		}

		public static function posted_on()
	{
		// Date archive link (year / month / day)
		$date_link = get_day_link(
			get_the_date( 'Y' ),
			get_the_date( 'm' ),
			get_the_date( 'd' )
		);

		$time_string = sprintf(
			'<time class="entry-date published" datetime="%1$s">%2$s</time>',
			esc_attr( get_the_date( 'c' ) ),
			esc_html( get_the_date() )
		);

		$posted_on = sprintf(
			esc_html_x( 'Posted on %s', 'post date', 'awps' ),
			'<a href="' . esc_url( $date_link ) . '">' . $time_string . '</a>'
		);

		$byline = sprintf(
			esc_html_x( 'by %s', 'post author', 'awps' ),
			'<span class="author vcard">
				<a class="url fn n" href="' . esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ) . '">
					' . esc_html( get_the_author() ) . '
				</a>
			</span>'
		);

		echo '<span class="posted-on">' . $posted_on . '</span><span class="byline"> ' . $byline . '</span>';
	}



	public static function entry_footer()
	{

		// Hide category and tag text for pages.
		if ('post' === get_post_type()) {
			/* translators: used between list items, there is a space after the comma */
			$categories_list = get_the_category_list(esc_html__(', ', 'awps'));
			if ($categories_list && self::categorized_blog()) {
				printf('<span class="cat-links">'.esc_html__('Posted in %1$s', 'awps').'</span>', $categories_list); // WPCS: XSS OK.
			}
			/* translators: used between list items, there is a space after the comma */
			$tags_list = get_the_tag_list('', esc_html__(', ', 'awps'));
			if ($tags_list) {
				printf('<span class="tags-links">'.esc_html__('Tagged %1$s', 'awps').'</span>', $tags_list); // WPCS: XSS OK.
			}
		}
		if (!is_single() && !post_password_required() && (comments_open() || get_comments_number())) {
			echo '<span class="comments-link">';
			/* translators: %s: post title */
			comments_popup_link(sprintf(wp_kses(__('Leave a Comment<span class="screen-reader-text"> on %s</span>', 'awps'), array('span' => array('class' => array()))), get_the_title()));
			echo '</span>';
		}
		edit_post_link(
			sprintf(
				/* translators: %s: Name of current post */
				esc_html__('Edit %s', 'awps'),
				the_title('<span class="screen-reader-text">"', '"</span>', false)
			),
			'<span class="edit-link">',
			'</span>'
		);
	}

	public static function categorized_blog()
	{
		if (false === ($all_the_cool_cats = get_transient('awps_categories'))) {
			// Create an array of all the categories that are attached to posts.
			$all_the_cool_cats = get_categories(array(
				'fields' => 'ids',
				'hide_empty' => 1,
				// We only need to know if there is more than one category.
				'number' => 2,
			));
			// Count the number of categories that are attached to the posts.
			$all_the_cool_cats = count($all_the_cool_cats);
			set_transient('awps_categories', $all_the_cool_cats);
		}
		if ($all_the_cool_cats > 1) {
			// This blog has more than 1 category so awps_categorized_blog should return true.
			return true;
		} else {
			// This blog has only 1 category so awps_categorized_blog should return false.
			return false;
		}
	}

	public function category_transient_flusher()
	{
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}
		delete_transient('awps_categories');
	}


	public static function get_post_categories_with_links( $post_id = null ) {

		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		$categories = get_the_category( $post_id );

		if ( empty( $categories ) || is_wp_error( $categories ) ) {
			return [];
		}

		$result = [];

		foreach ( $categories as $category ) {
			$result[] = [
				'id'   => $category->term_id,
				'name' => $category->name,
				'slug' => $category->slug,
				'url'  => get_category_link( $category->term_id ),
			];
		}

		return $result;
	}


	public static function get_post_terms_with_links( $taxonomy = 'category', $post_id = null ) {

		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		$terms = get_the_terms( $post_id, $taxonomy );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return [];
		}

		$result = [];

		foreach ( $terms as $term ) {
			$result[] = [
				'id'   => $term->term_id,
				'name' => $term->name,
				'slug' => $term->slug,
				'url'  => get_term_link( $term ),
			];
		}

		return $result;
	}

	
}
