<?php
/**
 * The sidebar containing the main widget area
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package awps
 */

// Bail early if no widgets are active in this sidebar
if ( ! is_active_sidebar( 'awps-sidebar' ) ) {
	return;
}

// Customize preview hook (for live preview in Customizer)
if ( is_customize_preview() ) {
	?>
	<div id="awps-sidebar-control" class="customize-sidebar-control" aria-hidden="true"></div>
	<?php
}

// Allow filtering of sidebar classes for flexibility
$sidebar_classes = apply_filters( 'awps_sidebar_classes', array( 'widget-area', 'sidebar-primary' ) );
$sidebar_attrs   = apply_filters(
	'awps_sidebar_attributes',
	array(
		'id'    => 'secondary',
		'class' => implode( ' ', array_map( 'sanitize_html_class', $sidebar_classes ) ),
		'role'  => 'complementary',
		'aria-labelledby' => 'sidebar-heading',
	)
);
?>

<aside
	<?php
	foreach ( $sidebar_attrs as $attr => $value ) {
		echo esc_attr( $attr ) . '="' . esc_attr( $value ) . '" ';
	}
	?>
>
	<?php
	// Optional sidebar heading for accessibility/screen readers
	if ( apply_filters( 'awps_show_sidebar_heading', false ) ) :
		?>
		<h2 id="sidebar-heading" class="screen-reader-text">
			<?php esc_html_e( 'Sidebar', 'awps' ); ?>
		</h2>
		<?php
	endif;
	?>

	<?php dynamic_sidebar( 'awps-sidebar' ); ?>

</aside><!-- #secondary -->