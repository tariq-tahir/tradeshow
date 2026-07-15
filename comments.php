<?php
/**
 * The template for displaying comments
 *
 * This is the template that displays the area of the page that contains both the current comments
 * and the comment form.
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package awps
 */

/*
 * If the current post is protected by a password and
 * the visitor has not yet entered the password,
 * return early without loading the comments.
 */
if ( post_password_required() ) {
	return;
}
?>

<div id="comments" class="comments-area">

	<?php if ( have_comments() ) : ?>

		<h2 class="comments-title">
			<?php
			$comment_count = get_comments_number();
			printf(
				/* translators: %d: Number of comments */
				esc_html( _n( '%d Comment', '%d Comments', $comment_count, 'awps' ) ),
				absint( $comment_count )
			);
			?>
		</h2><!-- .comments-title -->

		<?php awps_comment_navigation( 'above' ); ?>

		<ol class="comment-list">
			<?php
			wp_list_comments(
				array(
					'style'      => 'ol',
					'short_ping' => true,
					'avatar_size' => 60,
					'reply_text'  => esc_html__( 'Reply', 'awps' ),
				)
			);
			?>
		</ol><!-- .comment-list -->

		<?php awps_comment_navigation( 'below' ); ?>

	<?php endif; // End have_comments(). ?>

	<?php
	// If comments are closed and there are existing comments, display a notice.
	if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) :
	?>
		<p class="no-comments">
			<?php esc_html_e( 'Comments are closed.', 'awps' ); ?>
		</p>
	<?php endif; ?>

	<?php
	// Comment form with accessibility improvements
	comment_form(
		array(
			'title_reply'          => esc_html__( 'Leave a Reply', 'awps' ),
			'title_reply_to'       => esc_html__( 'Leave a Reply to %s', 'awps' ),
			'cancel_reply_link'    => esc_html__( 'Cancel reply', 'awps' ),
			'label_submit'         => esc_html__( 'Post Comment', 'awps' ),
			'comment_field'        => '<p class="comment-form-comment"><label for="comment">' . esc_html_x( 'Comment', 'noun', 'awps' ) . '</label><textarea id="comment" name="comment" rows="5" aria-required="true" required></textarea></p>',
			'comment_notes_before' => '<p class="comment-notes"><span id="email-notes">' . esc_html__( 'Your email address will not be published.', 'awps' ) . '</span>' . ( $req ? ' <span class="required-field-message">' . esc_html__( 'Required fields are marked *', 'awps' ) . '</span>' : '' ) . '</p>',
			'fields'               => array(
				'author' => '<p class="comment-form-author"><label for="author">' . esc_html__( 'Name', 'awps' ) . '</label> <input id="author" name="author" type="text" value="' . esc_attr( $commenter['comment_author'] ) . '" size="30"' . ( $req ? ' aria-required="true" required' : '' ) . ' /></p>',
				'email'  => '<p class="comment-form-email"><label for="email">' . esc_html__( 'Email', 'awps' ) . '</label> <input id="email" name="email" type="email" value="' . esc_attr( $commenter['comment_author_email'] ) . '" size="30"' . ( $req ? ' aria-required="true" required' : '' ) . ' /></p>',
				'url'    => '<p class="comment-form-url"><label for="url">' . esc_html__( 'Website', 'awps' ) . '</label> <input id="url" name="url" type="url" value="' . esc_attr( $commenter['comment_author_url'] ) . '" size="30" /></p>',
			),
		)
	);
	?>

</div><!-- #comments -->