<?php

namespace Awps\Custom;

/**
 * Widgets Manager
 * 
 * Registers widget areas (sidebars) and custom widget types.
 */
class Widgets
{
	/**
	 * Register default hooks and actions for WordPress
	 *
	 * @return void
	 */
	public function register()
	{
		add_action( 'widgets_init', array( $this, 'registerSidebars' ) );
		add_action( 'widgets_init', array( $this, 'registerWidgetTypes' ) );
	}

	/**
	 * Register widget areas (sidebars) for header, footer, etc.
	 *
	 * @return void
	 */
	public function registerSidebars()
	{
		// Footer columns (5 areas)
		for ( $i = 1; $i <= 5; $i++ ) {
			register_sidebar(
				array(
					'name'          => sprintf( __( 'Footer Column %d', 'awps' ), $i ),
					'id'            => "footer-column-$i",
					'description'   => sprintf( __( 'Widgets in this area will be shown in footer column %d.', 'awps' ), $i ),
					'before_widget' => '<div class="footer-column-widget">',
					'after_widget'  => '</div>',
					'before_title'  => '<h3 class="widget-title">',
					'after_title'   => '</h3>',
				)
			);
		}

		// Header widget area (if needed for dynamic top-bar content)
		register_sidebar(
			array(
				'name'          => __( 'Header Top Bar Left', 'awps' ),
				'id'            => 'header-top-bar-left',
				'description'   => __( 'Widgets for the desktop-only top bar left section.', 'awps' ),
				'before_widget' => '<div class="top-bar-left-widget">',
				'after_widget'  => '</div>',
				'before_title'  => '<span class="widget-title">',
				'after_title'   => '</span>',
			)
		);

		// Header widget area (if needed for dynamic top-bar content)
		register_sidebar(
			array(
				'name'          => __( 'Header Top Bar Right', 'awps' ),
				'id'            => 'header-top-bar-right',
				'description'   => __( 'Widgets for the desktop-only top bar right section.', 'awps' ),
				'before_widget' => '<div class="top-bar-right-widget">',
				'after_widget'  => '</div>',
				'before_title'  => '<span class="widget-title">',
				'after_title'   => '</span>',
			)
		);

		// Add more areas as needed: sidebar, product-page, etc.
	}

	/**
	 * Register custom widget types from inc/Api/Widgets/
	 *
	 * @return void
	 */
	public function registerWidgetTypes()
	{
		// Auto-register widgets from your existing namespace
		$widgets = array(
			\Awps\Api\Widgets\LatestProductsWidget::class,
			\Awps\Api\Widgets\TextWidget::class,
			// Add more as you create them:
			// \Awps\Api\Widgets\SocialLinksWidget::class,
			// \Awps\Api\Widgets\NewsletterWidget::class,
		);

		foreach ( $widgets as $widget_class ) {
			if ( class_exists( $widget_class ) ) {
				register_widget( $widget_class );
			}
		}
	}
}