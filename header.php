<?php
/**
 * The header for AWPS Theme - Bootstrap 5 version
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header id="masthead" class="site-header">

    <!-- 🔹 Top Bar -->
    <div class="bg-light border-bottom py-2">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <nav class="navbar navbar-expand">
                <?php
                wp_nav_menu([
                    'theme_location' => 'topmenu',
                    'menu_class'     => 'navbar-nav me-auto mb-2 mb-lg-0',
                    'container'      => false,
                    'fallback_cb'    => false,
                ]);
                ?>
            </nav>
            <div class="text-end small">
                <a href="tel:+923001211566" class="text-decoration-none text-dark">
                    📞 +92-300-121-1566
                </a>
            </div>
        </div>
    </div>

    <!-- 🔹 Main Header -->
    <div class="container-fluid py-3">
        <div class="row align-items-center">
            <div class="col-md-3 col-6">
                <div class="site-branding">
                    <?php the_custom_logo(); ?>
                </div>
            </div>

            <div class="col-md-6 d-none d-md-block">
                <?php get_product_search_form(); ?>
            </div>

            <div class="col-md-3 col-6 text-end">
                <div class="d-flex justify-content-end align-items-center gap-2">
                    <?php if ( is_user_logged_in() ) : ?>
                        <a href="<?php echo esc_url( wp_logout_url() ); ?>" class="btn btn-outline-secondary btn-sm">Logout</a>
                    <?php else : ?>
                        <a href="<?php echo esc_url( wp_login_url() ); ?>" class="btn btn-primary btn-sm">Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 🔹 Primary Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark" role="navigation">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#primaryNavbar" aria-controls="primaryNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <?php
            if ( has_nav_menu( 'primary' ) ) :
                wp_nav_menu([
                    'theme_location' => 'primary',
                    'menu_id'        => 'primary-menu',
                    'container'      => 'div',
                    'container_class'=> 'collapse navbar-collapse',
                    'container_id'   => 'primaryNavbar',
                    'menu_class'     => 'navbar-nav me-auto mb-2 mb-lg-0',
                    'fallback_cb'    => false,
                ]);
            endif;
            ?>
        </div>
    </nav>

</header>

<main id="content" class="site-content">
