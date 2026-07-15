<?php
/**
 * Supplier/Product Statistics Tracker
 * 
 * Handles view counting, engagement metrics, and analytics for suppliers/products.
 * 
 * @package AWPS
 * @subpackage Suppliers
 */

namespace AWPS\Suppliers;

/**
 * Stats class for tracking product/supplier engagement
 */
class Stats {

    /**
     * Register hooks and filters
     */
    public function register() {
        // Track product views
        add_action( 'wp', array( $this, 'track_product_view' ), 20 );
        
        // Track supplier profile views
        add_action( 'wp', array( $this, 'track_supplier_profile_view' ), 20 );
    }

    /**
     * Track product view with date-based storage and rate limiting
     */
    public function track_product_view() {
        if ( ! is_singular( 'product' ) ) {
            return;
        }

        $post_id = get_the_ID();
        if ( ! $post_id ) {
            return;
        }

        if ( wp_doing_ajax() || defined( 'DOING_AUTOSAVE' ) || defined( 'REST_REQUEST' ) ) {
            return;
        }

        // Rate limit: 1 view per session per hour
        $viewed_key = 'awps_viewed_product_' . $post_id;
        if ( get_transient( $viewed_key ) ) {
            return;
        }

        // Get today's date
        $today = current_time( 'Y-m-d' );
        
        // Get current views data
        $views_data = get_post_meta( $post_id, '_awps_product_views', true );
        if ( ! is_array( $views_data ) ) {
            $views_data = [];
        }
        
        if ( ! isset( $views_data[ $today ] ) ) {
            $views_data[ $today ] = 0;
        }
        
        $views_data[ $today ]++;
        update_post_meta( $post_id, '_awps_product_views', $views_data );
        
        set_transient( $viewed_key, true, HOUR_IN_SECONDS );
    }

    /**
     * Track supplier profile view with date-based storage and rate limiting
     */
    public function track_supplier_profile_view() {
        if ( ! is_singular( 'supplier' ) ) {
            return;
        }

        $post_id = get_the_ID();
        if ( ! $post_id ) {
            return;
        }

        if ( wp_doing_ajax() || defined( 'DOING_AUTOSAVE' ) || defined( 'REST_REQUEST' ) ) {
            return;
        }

        // Skip admin views
        if ( current_user_can( 'manage_options' ) ) {
            return;
        }

        // Rate limit: 1 view per session per hour
        $viewed_key = 'awps_viewed_supplier_' . $post_id;
        if ( get_transient( $viewed_key ) ) {
            return;
        }

        // Get today's date
        $today = current_time( 'Y-m-d' );
        
        // Get current views data
        $views_data = get_post_meta( $post_id, '_awps_profile_views', true );
        if ( ! is_array( $views_data ) ) {
            $views_data = [];
        }
        
        if ( ! isset( $views_data[ $today ] ) ) {
            $views_data[ $today ] = 0;
        }
        
        $views_data[ $today ]++;
        update_post_meta( $post_id, '_awps_profile_views', $views_data );
        
        set_transient( $viewed_key, true, HOUR_IN_SECONDS );
    }

    /**
     * Get combined views for a supplier: profile + all their products
     * 
     * @param int $user_id WordPress user ID (supplier account).
     * @param int $days Number of days to look back (default: 7).
     * @return int Total combined views.
     */
    public static function get_supplier_total_views( $user_id, $days = 7 ) {
        $total_views = 0;
        
        // ─────────────────────────────────────────────────────────
        // 1. Get supplier profile views (if published)
        // ─────────────────────────────────────────────────────────
        $supplier_posts = get_posts( [
            'post_type'   => 'supplier',
            'post_status' => 'publish',
            'meta_query'  => [
                [
                    'key'   => '_linked_user_id',
                    'value' => $user_id,
                ],
            ],
            'numberposts' => 1,
            'fields'      => 'ids',
        ] );
        
        if ( ! empty( $supplier_posts ) ) {
            $profile_views = self::get_date_range_views( $supplier_posts[0], '_awps_profile_views', $days );
            $total_views += $profile_views;
        }
        
        // ─────────────────────────────────────────────────────────
        // 2. Get ALL product views for this supplier (by post author)
        // ─────────────────────────────────────────────────────────
        $product_ids = get_posts( [
            'post_type'   => 'product',
            'post_status' => 'publish',
            'author'      => $user_id,
            'fields'      => 'ids',
            'numberposts' => -1,
        ] );
        
        foreach ( $product_ids as $product_id ) {
            $product_views = self::get_date_range_views( $product_id, '_awps_product_views', $days );
            $total_views += $product_views;
        }
        
        return $total_views;
    }

    /**
     * Helper: Get view count for a post within a date range
     * 
     * @param int $post_id Post ID.
     * @param string $meta_key Meta key storing views data.
     * @param int $days Number of days to look back.
     * @return int Total views in range.
     */
    private static function get_date_range_views( $post_id, $meta_key, $days ) {
        $views_data = get_post_meta( $post_id, $meta_key, true );
        
        if ( ! is_array( $views_data ) ) {
            return 0;
        }
        
        $total = 0;
        $date_range = [];
        
        // Build date range array
        for ( $i = 0; $i < $days; $i++ ) {
            $date_range[] = date( 'Y-m-d', strtotime( "-{$i} days" ) );
        }
        
        // Sum views for dates in range
        foreach ( $views_data as $date => $count ) {
            if ( in_array( $date, $date_range, true ) && is_numeric( $count ) ) {
                $total += (int) $count;
            }
        }
        
        return $total;
    }

    /**
     * Get view count for a single post (legacy helper)
     * 
     * @param int $post_id Post ID.
     * @param string $meta_key Meta key (default: 'views').
     * @return int Total all-time views.
     */
    public static function get_views( $post_id, $meta_key = 'views' ) {
        $views_data = get_post_meta( $post_id, $meta_key, true );
        
        if ( ! is_array( $views_data ) ) {
            return (int) $views_data;
        }
        
        return array_sum( $views_data );
    }

    /**
     * Reset view data for a post
     * 
     * @param int $post_id Post ID.
     * @param string $meta_key Meta key to delete.
     * @return bool Success.
     */
    public static function reset_views( $post_id, $meta_key = 'views' ) {
        return delete_post_meta( $post_id, $meta_key );
    }
}