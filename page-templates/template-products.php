<?php
/**
 * Template Name: Products Archive
 * Description: Custom archive for Supplier Products CPT
 *
 * @package AWPS
 */

defined( 'ABSPATH' ) || exit;

get_header();

// Get current page number for pagination
$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;

// Custom query for Supplier Products
$args = array(
    'post_type'      => 'product',
    'posts_per_page' => 24,
    'paged'          => $paged,
    'post_status'    => 'publish',
    'orderby'        => 'date',
    'order'          => 'DESC',
);

$query = new WP_Query( $args );

// Get current term for active state highlighting (if viewing a category)
$current_term = null;
if ( is_tax( 'product_cat' ) ) {
    $current_term = get_queried_object();
}
?>

<div class="container template-products">
    
    <!-- Breadcrumbs (Matches Home/Search Pages) -->
    <nav class="awps-breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'awps' ); ?>">
        <?php echo do_shortcode( '[awps_custom_breadcrumb]' ); ?>
    </nav>

    <div class="category-archive-layout">
        
        <!-- Sidebar: Collapsible Categories (Matches Home/Search Sidebar Style) -->
        <aside class="category-sidebar" aria-label="<?php esc_attr_e( 'Product Categories', 'awps' ); ?>">
            <h5 class="widget-title"><?php esc_html_e( 'CATEGORIES', 'awps' ); ?></h5>
            
            <ul class="category-list" role="tree">
                <?php
                // Get top-level categories (parent = 0)
                $top_categories = get_terms( array(
                    'taxonomy'   => 'product_cat',
                    'parent'     => 0,
                    'hide_empty' => true,
                    'orderby'    => 'name',
                    'order'      => 'ASC',
                ) );

                if ( ! empty( $top_categories ) && ! is_wp_error( $top_categories ) ) :
                    foreach ( $top_categories as $cat ) :
                        $is_current     = is_tax( 'product_cat', $cat->slug );
                        $child_terms    = get_terms( array(
                            'taxonomy'   => 'product_cat',
                            'parent'     => $cat->term_id,
                            'hide_empty' => true,
                        ) );
                        $has_children   = ! empty( $child_terms ) && ! is_wp_error( $child_terms );
                        $child_ids      = $has_children ? wp_list_pluck( $child_terms, 'term_id' ) : array();
                        $is_active_branch = $is_current || ( $has_children && $current_term && in_array( $current_term->term_id, $child_ids, true ) );
                        $target_id      = 'cat-' . esc_attr( $cat->term_id );
                        $category_link  = get_term_link( $cat ); // ✅ Get category archive URL
                        ?>
                        <li class="category-item <?php echo $is_active_branch ? 'active-branch' : ''; ?>" role="treeitem" aria-expanded="<?php echo $is_active_branch ? 'true' : 'false'; ?>">
                            
                            <?php if ( $has_children ) : ?>
                                <!-- Has children: Show toggle button -->
                                <button type="button" 
                                        class="category-toggle <?php echo $is_active_branch ? 'expanded' : ''; ?>" 
                                        data-target="<?php echo esc_attr( $target_id ); ?>" 
                                        aria-expanded="<?php echo $is_active_branch ? 'true' : 'false'; ?>"
                                        aria-controls="<?php echo esc_attr( $target_id ); ?>">
                                    <?php echo esc_html( $cat->name ); ?>
                                    <span class="toggle-icon" aria-hidden="true">
                                        <i class="fas fa-chevron-down"></i>
                                    </span>
                                </button>

                                <!-- Subcategory list -->
                                <ul id="<?php echo esc_attr( $target_id ); ?>" 
                                    class="subcategory-list <?php echo $is_active_branch ? 'open' : ''; ?>"
                                    role="group">
                                    <?php foreach ( $child_terms as $sub ) : ?>
                                        <li class="<?php echo is_tax( 'product_cat', $sub->slug ) ? 'active' : ''; ?>" role="treeitem">
                                            <a href="<?php echo esc_url( get_term_link( $sub ) ); ?>" 
                                            <?php echo is_tax( 'product_cat', $sub->slug ) ? 'aria-current="page"' : ''; ?>>
                                                <?php echo esc_html( $sub->name ); ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else : ?>
                                <!-- ✅ NO children: Show direct link to category archive -->
                                <a href="<?php echo esc_url( $category_link ); ?>" 
                                class="category-toggle <?php echo $is_current ? 'active' : ''; ?>"
                                <?php echo $is_current ? 'aria-current="page"' : ''; ?>>
                                    <?php echo esc_html( $cat->name ); ?>
                                </a>
                            <?php endif; ?>
                            
                        </li>
                    <?php endforeach; ?>
                <?php else : ?>
                    <li class="category-item">
                        <p class="text-muted"><?php esc_html_e( 'No categories found.', 'awps' ); ?></p>
                    </li>
                <?php endif; ?>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="category-main-content" role="main">
            
            <!-- Header Bar (Matches Home/Search Header Style) -->
            <div class="products-header d-flex justify-content-between align-items-center mb-4 p-3 bg-light rounded">
                <span aria-live="polite">
                    <?php
                    printf(
                        /* translators: %s: Number of products */
                        _n( '%s product', '%s products', $query->found_posts, 'awps' ),
                        '<strong>' . number_format_i18n( $query->found_posts ) . '</strong>'
                    );
                    ?>
                </span>

                <!-- Sort Dropdown (Matches Home/Search Sort Style) -->
                <label for="product-sort" class="visually-hidden"><?php esc_html_e( 'Sort products by', 'awps' ); ?></label>
                <select class="form-select" id="product-sort" onchange="window.location.href=this.value;" aria-label="<?php esc_attr_e( 'Sort products', 'awps' ); ?>">
                    <option value="<?php echo esc_url( add_query_arg( 'orderby', 'date', get_pagenum_link() ) ); ?>" <?php selected( get_query_var( 'orderby' ), 'date' ); ?>>
                        <?php esc_html_e( 'Sort by latest', 'awps' ); ?>
                    </option>
                    <option value="<?php echo esc_url( add_query_arg( 'orderby', 'title', get_pagenum_link() ) ); ?>" <?php selected( get_query_var( 'orderby' ), 'title' ); ?>>
                        <?php esc_html_e( 'Sort by name', 'awps' ); ?>
                    </option>
                </select>
            </div>

            <!-- Product Grid (Matches Home/Search Grid Style) -->
            <div class="products-grid" role="list">
                <?php if ( $query->have_posts() ) : ?>
                    <?php while ( $query->have_posts() ) : $query->the_post(); ?>
                        <?php get_template_part( 'views/content-product-category' ); ?>
                    <?php endwhile; ?>
                    
                    <!-- Pagination (MATCHES HOME/SEARCH PAGE NAVIGATION) -->
                    <?php if ( $query->max_num_pages > 1 ) : ?>
                        <nav class="pagination-wrapper" aria-label="<?php esc_attr_e( 'Product pagination', 'awps' ); ?>">
                            <?php
                            // Generate pagination links with consistent parameters (matches home/search)
                            $pagination_args = array(
                                'base'         => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
                                'format'       => '?paged=%#%',
                                'current'      => max( 1, $paged ),
                                'total'        => $query->max_num_pages,
                                'prev_text'    => __( '&laquo; Previous', 'awps' ),
                                'next_text'    => __( 'Next &raquo;', 'awps' ),
                                'type'         => 'list', // Outputs <ul><li> for styling
                                'end_size'     => 1,
                                'mid_size'     => 2,
                            );
                            
                            $pagination = paginate_links( $pagination_args );
                            
                            if ( $pagination ) :
                                // Wrap in nav and add custom classes for styling (matches home/search)
                                echo '<ul class="pagination">' . wp_kses( $pagination, array(
                                    'ul' => array( 'class' => array() ),
                                    'li' => array( 'class' => array() ),
                                    'a' => array( 
                                        'href' => array(),
                                        'class' => array(),
                                        'aria-label' => array(),
                                    ),
                                    'span' => array( 'class' => array(), 'aria-current' => array() ),
                                ) ) . '</ul>';
                            endif;
                            ?>
                        </nav>
                    <?php endif; ?>
                    
                <?php else : ?>
                    <!-- Empty State (Matches Home/Search Empty State) -->
                    <div class="no-products text-center py-5 bg-light rounded" role="status">
                        <p class="lead text-muted">
                            <?php esc_html_e( 'No products found. Suppliers are adding new products daily.', 'awps' ); ?>
                        </p>
                        <a href="<?php echo esc_url( home_url( '/pakistan-exporters/' ) ); ?>" class="btn btn-primary mt-3">
                            <?php esc_html_e( 'Browse Verified Suppliers', 'awps' ); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

        </main>
    </div>
</div><!-- .container -->


<script>
document.addEventListener('DOMContentLoaded', function() {
    // ✅ ONLY select toggle BUTTONS (not links) by checking for data-target attribute
    const toggles = document.querySelectorAll('.category-toggle[data-target]');
    
    toggles.forEach(function(toggle) {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-target');
            const subList = document.getElementById(targetId);
            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            const newExpanded = !isExpanded;
            
            // Toggle current item
            this.classList.toggle('expanded', newExpanded);
            this.setAttribute('aria-expanded', newExpanded ? 'true' : 'false');
            
            if (subList) {
                subList.classList.toggle('open', newExpanded);
                // Optional: manage focus for accessibility
                if (newExpanded) {
                    const firstLink = subList.querySelector('a');
                    if (firstLink) firstLink.focus();
                }
            }
        });
    });
});
</script>

<?php get_footer(); ?>