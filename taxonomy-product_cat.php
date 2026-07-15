<?php
/**
 * Product category archive template.
 * Uses Yoast breadcrumb, left sidebar categories, product grid.
 * WooCommerce-free implementation for custom 'product' CPT.
 *
 * @package awps
 */

get_header(); 

// Get current category term
$current_term = get_queried_object();
$term_name = $current_term->name ?? __('Products', 'awps');
?>

<div class="container">

    <!-- Breadcrumbs -->
    <div class="awps-breadcrumbs">
        <nav class="custom-breadcrumb">
            <a href="<?php echo home_url(); ?>">Home</a> &raquo;
            <a href="<?php echo home_url() . '/products/' ?>">All Categories</a> &raquo;
            <?php echo $term_name;  ?>
        </nav>
    </div>

    <div class="category-archive-layout">

        <!-- Sidebar: Categories -->
        <aside class="category-sidebar">
            <h5 class="widget-title">CATEGORIES</h5>
            <ul class="category-list">
                <li><a href="<?php echo esc_url( home_url('/products') ); ?>">‹ ALL CATEGORIES</a></li>

                <!-- Show Current Category -->
                <?php if ( $current_term && ! is_wp_error( $current_term ) ) : ?>
                    <li class="current-category"><a href="<?php echo esc_url( get_term_link( $current_term ) ); ?>" class="active"><?php echo esc_html( $current_term->name ); ?></a></li>
                <?php endif; ?>

                <!-- Show Subcategories -->
                <?php
                if ( $current_term && ! is_wp_error( $current_term ) ) {
                    $child_terms = get_terms( array(
                        'taxonomy'   => 'product_cat',
                        'parent'     => $current_term->term_id,
                        'hide_empty' => true,
                    ) );

                    foreach ( $child_terms as $term ) {
                        $active_class = ( is_tax( 'product_cat', $term->slug ) ) ? 'active' : '';
                        echo '<li class="cat-item"><a href="' . esc_url( get_term_link( $term ) ) . '" class="' . $active_class . '">' . esc_html( $term->name ) . '</a></li>';
                    }
                }
                ?>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="category-main-content">

            <!-- Header Bar -->
            <div class="products-header d-flex justify-content-between align-items-center mb-4 p-3 bg-light rounded">
                <span><?php printf( _n( '%d Product found', '%d Products found', $wp_query->found_posts, 'awps' ), $wp_query->found_posts ); ?></span>

                <!-- Sort Dropdown -->
                <select class="form-select" onchange="window.location.href=this.value;">
                    <option value="<?php echo esc_url( add_query_arg( 'orderby', 'date', get_pagenum_link() ) ); ?>" <?php selected( get_query_var('orderby'), 'date' ); ?>>
                        <?php _e('Sort by latest', 'awps'); ?>
                    </option>
                    <option value="<?php echo esc_url( add_query_arg( 'orderby', 'title', get_pagenum_link() ) ); ?>" <?php selected( get_query_var('orderby'), 'title' ); ?>>
                        <?php _e('Sort by name', 'awps'); ?>
                    </option>
                </select>
            </div>

            <!-- Product Grid -->
            <div class="products-grid">
                <?php if ( have_posts() ) : ?>
                    <?php while ( have_posts() ) : the_post(); ?>
                        <?php get_template_part( 'views/content-product-category' ); ?>
                    <?php endwhile; ?>
                <?php else : ?>
                    <div class="no-products text-center py-5">
                        <p class="lead"><?php _e('No products found in this category.', 'awps'); ?></p>
                        <a href="<?php echo esc_url( home_url('/product') ); ?>" class="btn btn-primary mt-3">
                            <?php _e('Browse All Products', 'awps'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php custom_navigation(); ?>

        </main>

    </div>

</div><!-- .container -->

<?php get_footer(); ?>