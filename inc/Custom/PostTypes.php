<?php

namespace Awps\Custom;

/**
 * Custom
 * use it to write your custom functions.
 */
class PostTypes
{
	/**
     * register default hooks and actions for WordPress
     * @return
     */
	public function register() {
		add_action( 'init', array( $this, 'custom_post_type'), 10 , 4 );
		add_action( 'after_switch_theme', array( $this, 'rewrite_flush') );	
	}	
	
  /**
    * Create Custom Post Types
    * @return The registered post type object, or an error object
    */
    public function custom_post_type()
    {
		/**
		 * Add the post types and their details
		 */
		$custom_posts = array(
			array(
				'slug' => 'certification',
				'singular' => __('Certification', 'awps'),
				'plural' => __('Certifications', 'awps'),
				'menu_icon' => 'dashicons-awards',
				'menu_position' => 18,
				'text_domain' => 'awps',
				'supports' => array( 'title', 'thumbnail', 'editor' /* 'excerpt', 'author', 'comments'*/ ),
				'description' => __('Certifications Custom Post Type', 'awps'),
				'public' => true,
				'publicly_queryable' => true,
				'show_ui' => true,
				'show_in_menu' => true,
				'query_var' => true,
				'capability_type' => 'post',
				'has_archive' => true,
				'hierarchical' => false,
				'show_in_rest' => true,
			),
			array(
				'slug' => 'inquiry',
				'singular' => __('Inquiry', 'awps'),
				'plural'  => __('Inquiries', 'awps'),
				'menu_icon' => 'dashicons-book-alt',
				'menu_position' => 18,
				'text_domain' => 'awps',
				'supports' => array( 'title',  /*'editor', 'thumbnail' , 'excerpt',  'author', 'comments'*/ ),
				'description' => __('Inquiries Custom Post Type', 'awps'),
				'public' => false,
				'publicly_queryable' => false,
				'show_ui' => true,
				'show_in_menu' => true,
				'query_var' => false,
				'capability_type' => 'post',
				'has_archive' => true,
				'hierarchical' => false,
				'show_in_rest' => false,
			),
			array(
				'slug' => 'supplier',
				'singular' => __('Supplier', 'awps'),
				'plural' => __('Suppliers', 'awps'),
				'menu_icon' => 'dashicons-businessman',
				'menu_position' => 18,
				'text_domain' => 'awps',
				'supports' => array( 'title', 'editor', 'custom-fields', 'thumbnail', 'excerpt', 'author', 'comments' ),
				'description' => __('Suppliers Custom Post Type', 'awps'),
				'public' => true,
				'publicly_queryable' => true,
				'show_ui' => true,
				'show_in_menu' => true,
				'query_var' => true,
				'capability_type' => 'post',
				'has_archive' => false,
				'hierarchical' => true,
				'show_in_rest' => true,
				'rewrite' => array( 'slug' => 'supplier' ),
			),
			array(
				'slug' => 'product',
				'singular' => __('Supplier Product', 'awps'),
				'plural' => __('Supplier Products', 'awps'),
				'menu_icon' => 'dashicons-image-filter',
				'menu_position' => 18,
				'text_domain' => 'awps',
				'supports' => array( 'title', 'editor', 'custom-fields', 'thumbnail', 'excerpt', 'author', 'comments' ),
				'description' => __('Supplier Products Custom Post Type', 'awps'),
				'public' => true,
				'publicly_queryable' => true,
				'show_ui' => true,
				'show_in_menu' => true,
				'query_var' => true,
				'capability_type' => 'post',
				'has_archive' => true,
				'hierarchical' => false,
				'show_in_rest' => true,
			),
		);

		foreach ( $custom_posts as $custom_post ) {
			$labels = array(
				'name'               => _x( $custom_post['plural'], 'post type general name', $custom_post['text_domain'] ),
				'singular_name'      => _x( $custom_post['singular'], 'post type singular name', $custom_post['text_domain'] ),
				'menu_name'          => _x( $custom_post['plural'], 'admin menu', $custom_post['text_domain'] ),
				'name_admin_bar'     => _x( $custom_post['singular'], 'add new on admin bar', $custom_post['text_domain'] ),
				'add_new'            => sprintf( _x( 'Add New %s', 'post type add new label', 'awps' ), $custom_post['singular'] ),
				'add_new_item'       => sprintf( __( 'Add New %s', 'awps' ), $custom_post['singular'] ),
				'new_item'           => sprintf( __( 'New %s', 'awps' ), $custom_post['singular'] ),
				'edit_item'          => sprintf( __( 'Edit %s', 'awps' ), $custom_post['singular'] ),
				'view_item'          => sprintf( __( 'View %s', 'awps' ), $custom_post['singular'] ),
				'view_items'         => sprintf( __( 'View %s', 'awps' ), $custom_post['plural'] ),
				'all_items'          => sprintf( __( 'All %s', 'awps' ), $custom_post['plural'] ),
				'search_items'       => sprintf( __( 'Search %s', 'awps' ), $custom_post['plural'] ),
				'parent_item_colon'  => sprintf( __( 'Parent %s', 'awps' ), $custom_post['plural'] ),
				'not_found'          => sprintf( __( 'No %s found.', 'awps' ), $custom_post['plural'] ),
				'not_found_in_trash' => sprintf( __( 'No %s found in Trash.', 'awps' ), $custom_post['plural'] ),
			);
			$args = array(
				'labels'             => $labels,
				'description'        => $custom_post['description'],
				'public'             => $custom_post['public'],
				'publicly_queryable' => $custom_post['publicly_queryable'],
				'show_ui'            => $custom_post['show_ui'],
				'show_in_menu'       => $custom_post['show_in_menu'],
				'menu_icon'          => $custom_post['menu_icon'],
				'query_var'          => $custom_post['query_var'],
				'rewrite'            => array( 'slug' => $custom_post['slug'] ),
				'capability_type'    => $custom_post['capability_type'],
				'has_archive'        => $custom_post['has_archive'],
				'hierarchical'       => $custom_post['hierarchical'],
				'menu_position'      => $custom_post['menu_position'],
				'supports'           => $custom_post['supports'],
				'show_in_rest'       => $custom_post['show_in_rest'],
			);

			register_post_type( $custom_post['slug'], $args );
		}





		// Register Product Categories Taxonomy for Supplier Products
$cat_labels = array(
    'name'                       => _x('Product Categories', 'Taxonomy General Name', 'awps'),
    'singular_name'              => _x('Product Category', 'Taxonomy Singular Name', 'awps'),
    'menu_name'                  => __('Categories', 'awps'),
    'all_items'                  => __('All Categories', 'awps'),
    'parent_item'                => __('Parent Category', 'awps'),
    'parent_item_colon'          => __('Parent Category:', 'awps'),
    'new_item_name'              => __('New Category Name', 'awps'),
    'add_new_item'               => __('Add New Category', 'awps'),
    'edit_item'                  => __('Edit Category', 'awps'),
    'update_item'                => __('Update Category', 'awps'),
    'view_item'                  => __('View Category', 'awps'),
    'separate_items_with_commas' => __('Separate categories with commas', 'awps'),
    'add_or_remove_items'        => __('Add or remove categories', 'awps'),
    'choose_from_most_used'      => __('Choose from the most used', 'awps'),
    'popular_items'              => __('Popular Categories', 'awps'),
    'search_items'               => __('Search Categories', 'awps'),
    'not_found'                  => __('No categories found', 'awps'),
    'no_terms'                   => __('No categories', 'awps'),
    'items_list'                 => __('Categories list', 'awps'),
    'items_list_navigation'      => __('Categories list navigation', 'awps'),
);
$cat_args = array(
    'labels'            => $cat_labels,
    'hierarchical'      => true, // Categories (not tags)
    'public'            => true,
    'show_ui'           => true,
    'show_admin_column' => true,
    'show_in_nav_menus' => true,
    'show_tagcloud'     => false,
    'query_var'         => true,
    'rewrite'           => array('slug' => 'product-category'),
    'show_in_rest'      => true, // Required for block editor support
    
);
register_taxonomy('product_cat', array('product'), $cat_args);
	}

  /**
    * Flush Rewrite on CPT activation
    * @return empty
    */
    public function rewrite_flush()
    {
        // Flush the rewrite rules only on theme activation
        flush_rewrite_rules();
    }
}
