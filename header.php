<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package awps
 */

 ?><!DOCTYPE html>
 <html <?php language_attributes(); ?>>
 <head>
	 
	 <meta charset="<?php bloginfo( 'charset' ); ?>">
	 <meta name="viewport" content="width=device-width, initial-scale=1">
	 <link rel="profile" href="http://gmpg.org/xfn/11">
	 <link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">
	 <?php wp_head(); ?>

 </head>
 
 
 <body <?php body_class(); ?>>
	<?php wp_body_open(); ?>

<div id="page" class="site">

	<header id="masthead" class="site-header">

		<?php
		// Top Bar - Desktop Only
		if ( ! wp_is_mobile() ) :
		?>
		<div class="top-bar desktop-only">
			
			<div class="container">
				<div class="welcome-text">
					<?php dynamic_sidebar( "header-top-bar-left" ); ?>
				</div>
				<div class="top-links">
					<?php dynamic_sidebar( "header-top-bar-right" ); ?>
				</div>
			</div>
		</div>
		<?php endif; ?>

		<div class="main-header">
			<div class="container">
				<div class="site-branding">
					<?php
					if ( has_custom_logo() ) :
						the_custom_logo();
					else :
						?>
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-logo">
							<img src="<?php echo get_theme_file_uri( '/assets/dist/images/logo.png' ); ?>"
								alt="<?php bloginfo( 'name' ); ?>"
								width="211"
								height="32">
						</a>
						<?php
					endif;
					?>
				</div><!-- .site-branding -->

				<div class="header-search">
					<?php get_search_form(); ?>
				</div><!-- .header-search -->

				<div class="header-login">
					<?php 
					if (function_exists('do_shortcode')) {
						echo do_shortcode('[awps_login_dropdown]');
					}
					?>
				</div><!-- .header-login -->
			</div><!-- .container -->
		</div><!-- .main-header -->

		<!-- ✅ NAVIGATION IS NOW A DIRECT SIBLING OF main-header -->
		<nav id="site-navigation" class="main-navigation">
			<div class="container">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'menu_id'        => 'primary-menu',
						'container'      => false,
						'menu_class'     => 'main-menu',
						'walker'         => new \Awps\Core\WalkerNav(),
					)
				);
				?>

				<button class="mobile-menu-toggle" aria-controls="primary-menu" aria-expanded="false">
					<span class="mobile-toggle-line"></span>
					<span class="mobile-toggle-line"></span>
					<span class="mobile-toggle-line"></span>
				</button>
			</div><!-- .container -->
		</nav><!-- #site-navigation -->
 
	</header> 
 
	<main id="content" class="site-content">