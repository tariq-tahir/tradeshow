<?php
/**
 * Supplier Dashboard Template
 * 
 * Frontend dashboard for logged-in suppliers/exporters
 * Shows inquiries, analytics, profile completion, and product stats
 *
 * @package AWPS
 * @subpackage Suppliers/Dashboard
 */

defined( 'ABSPATH' ) || exit;

// Get current user
$current_user = wp_get_current_user();
$user_id      = $current_user->ID;

// ─────────────────────────────────────────────────────────────
// INLINE HELPER FUNCTIONS (Move to /inc/Helpers/ later)
// ─────────────────────────────────────────────────────────────

if ( ! function_exists( 'awps_get_hidden_inquiries' ) ) {
    function awps_get_hidden_inquiries( $user_id ) {
        $hidden = get_user_meta( $user_id, 'hidden_inquiries', true );
        return is_array( $hidden ) ? $hidden : [];
    }
}

if ( ! function_exists( 'awps_get_read_inquiries' ) ) {
    function awps_get_read_inquiries( $user_id ) {
        $read = get_user_meta( $user_id, 'read_inquiries', true );
        return is_array( $read ) ? $read : [];
    }
}

if ( ! function_exists( 'awps_get_supplier_inquiry_count' ) ) {
    function awps_get_supplier_inquiry_count( $user_id, $status = 'all' ) {
        $hidden = awps_get_hidden_inquiries( $user_id );
        
        $args = [
            'post_type'      => 'inquiry',
            'post_status'    => 'publish',
            'meta_key'       => 'exporter_id',
            'meta_value'     => $user_id,
            'fields'         => 'ids',
            'nopaging'       => true,
            'post__not_in'   => $hidden,
        ];
        
        $inquiry_ids = get_posts( $args );
        
        if ( 'unread' === $status ) {
            $read          = awps_get_read_inquiries( $user_id );
            $inquiry_ids   = array_diff( $inquiry_ids, $read );
        }
        
        return count( $inquiry_ids );
    }
}

if ( ! function_exists( 'awps_get_recent_inquiries' ) ) {
    function awps_get_recent_inquiries( $user_id, $limit = 10 ) {
        $hidden = awps_get_hidden_inquiries( $user_id );
        
        return new WP_Query( [
            'post_type'      => 'inquiry',
            'post_status'    => 'publish',
            'meta_key'       => 'exporter_id',
            'meta_value'     => $user_id,
            'posts_per_page' => $limit,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'post__not_in'   => $hidden,
        ] );
    }
}

if ( ! function_exists( 'awps_delete_inquiry_frontend' ) ) {
    function awps_delete_inquiry_frontend( $inquiry_id, $user_id ) {
        $hidden = awps_get_hidden_inquiries( $user_id );
        if ( ! in_array( $inquiry_id, $hidden, true ) ) {
            $hidden[] = $inquiry_id;
            update_user_meta( $user_id, 'hidden_inquiries', $hidden );
        }
    }
}

if ( ! function_exists( 'awps_mark_inquiry_read' ) ) {
    function awps_mark_inquiry_read( $inquiry_id, $user_id ) {
        $read = awps_get_read_inquiries( $user_id );
        if ( ! in_array( $inquiry_id, $read, true ) ) {
            $read[] = $inquiry_id;
            update_user_meta( $user_id, 'read_inquiries', $read );
        }
    }
}

if ( ! function_exists( 'awps_get_top_viewed_products' ) ) {
    function awps_get_top_viewed_products( $user_id, $limit = 3 ) {
        $products = get_posts( [
            'post_type'   => 'product',
            'author'      => $user_id,
            'post_status' => 'publish',
            'numberposts' => -1,
            'fields'      => 'ids',
        ] );
        
        $products_with_views = [];
        
        foreach ( $products as $product_id ) {
            // Get real views using inline helper (last 30 days for product list)
            $views = _awps_get_date_range_views( $product_id, '_awps_product_views', 30 );
            
            $products_with_views[] = [
                'id'    => $product_id,
                'title' => get_the_title( $product_id ),
                'views' => $views,
                'url'   => get_permalink( $product_id ),
            ];
        }
        
        // Sort by views descending and limit
        usort( $products_with_views, function( $a, $b ) {
            return $b['views'] <=> $a['views'];
        } );
        
        return array_slice( $products_with_views, 0, $limit );
    }
}


if ( ! function_exists( 'awps_get_profile_views' ) ) {
    function awps_get_profile_views( $user_id, $days = 7 ) {
        $total_views = 0;
        
        // Get supplier profile views
        $supplier_posts = get_posts( [
            'post_type'   => 'supplier',
            'post_status' => 'publish',
            'meta_query'  => [ [ 'key' => '_linked_user_id', 'value' => $user_id ] ],
            'numberposts' => 1,
            'fields'      => 'ids',
        ] );
        
        if ( ! empty( $supplier_posts ) ) {
            $total_views += _awps_get_date_range_views( $supplier_posts[0], '_awps_profile_views', $days );
        }
        
        // Get all product views
        $product_ids = get_posts( [
            'post_type'   => 'product',
            'author'      => $user_id,
            'post_status' => 'publish',
            'fields'      => 'ids',
            'numberposts' => -1,
        ] );
        
        foreach ( $product_ids as $pid ) {
            $total_views += _awps_get_date_range_views( $pid, '_awps_product_views', $days );
        }
        
        return $total_views; // No caching - direct query every time (for testing)
    }
}

/**
 * Helper: Get view count for a post within a date range
 * Bulletproof version - handles all edge cases
 */
if ( ! function_exists( '_awps_get_date_range_views' ) ) {
    function _awps_get_date_range_views( $post_id, $meta_key, $days ) {
        $views_data = get_post_meta( $post_id, $meta_key, true );
        
        // Handle empty or invalid data
        if ( empty( $views_data ) || ! is_array( $views_data ) ) {
            return 0;
        }
        
        $total = 0;
        
        // Build date range (last $days days, format: Y-m-d)
        $date_range = [];
        for ( $i = 0; $i < $days; $i++ ) {
            $date_range[] = date( 'Y-m-d', strtotime( "-{$i} days" ) );
        }
        
        // Sum views for matching dates (normalize any stored format)
        foreach ( $views_data as $stored_date => $count ) {
            // Normalize stored date to Y-m-d format for comparison
            $normalized = date( 'Y-m-d', strtotime( $stored_date ) );
            
            if ( in_array( $normalized, $date_range, true ) && is_numeric( $count ) ) {
                $total += (int) $count;
            }
        }
        
        return $total;
    }
}


if ( ! function_exists( 'awps_get_visitor_countries' ) ) {
    function awps_get_visitor_countries( $user_id, $limit = 5 ) {
        return [
            'United Arab Emirates' => 42,
            'Saudi Arabia'         => 28,
            'United States'        => 19,
            'United Kingdom'       => 15,
            'Pakistan'             => 12,
        ];
    }
}

if ( ! function_exists( 'awps_get_top_viewed_products' ) ) {
    function awps_get_top_viewed_products( $user_id, $limit = 3 ) {
        $products = get_posts( [
            'post_type'   => 'product',
            'author'      => $user_id,
            'post_status' => 'publish',
            'numberposts' => -1, // Get ALL products, then sort by views
            'fields'      => 'ids',
        ] );
        
        $products_with_views = [];
        
        foreach ( $products as $product_id ) {
            // Get real views from Stats class or post meta
            if ( class_exists( '\AWPS\Suppliers\Stats' ) ) {
                $views = \AWPS\Suppliers\Stats::get_views( $product_id, '_awps_product_views' );
            } else {
                // Fallback: check old 'views' meta or date-based meta
                $views_data = get_post_meta( $product_id, '_awps_product_views', true );
                $views = is_array( $views_data ) ? array_sum( $views_data ) : (int) get_post_meta( $product_id, 'views', true );
            }
            
            $products_with_views[] = [
                'id'    => $product_id,
                'title' => get_the_title( $product_id ),
                'views' => $views,
                'url'   => get_permalink( $product_id ),
            ];
        }
        
        // Sort by views descending and limit
        usort( $products_with_views, function( $a, $b ) {
            return $b['views'] <=> $a['views'];
        } );
        
        return array_slice( $products_with_views, 0, $limit );
    }
}

if ( ! function_exists( 'awps_calculate_profile_completion' ) ) {
    function awps_calculate_profile_completion( $user_id ) {
        // ✅ Find the linked supplier post ID first
        $supplier_posts = get_posts( [
            'post_type'   => 'supplier',
            'post_status' => 'any',
            'meta_query'  => [
                [
                    'key'   => '_linked_user_id',
                    'value' => $user_id,
                ],
            ],
            'numberposts' => 1,
            'fields'      => 'ids',
        ] );
        
        if ( empty( $supplier_posts ) ) {
            return 0; // No supplier post linked yet
        }
        
        $post_id = $supplier_posts[0];
        
        // ✅ Now check POST meta (not user meta)
        $fields = [
            'company_logo'    => 2,
            'banner_image'    => 2,
            'company_name'    => 2,
            'address'         => 1,
            'state'           => 1,
            'city'            => 1,
            'phone'           => 1,
            'website'         => 1,
            'facebook'        => 1,
            'instagram'       => 1,
            'linkedin'        => 1,
            'exports_to'      => 1,
            'annual_capacity' => 1,
            'payment_terms'   => 1,
            'languages'       => 1,
            'lead_time'       => 1,
            'packaging'       => 1,
            'contact_person'  => 1,
            'designation'     => 1,
            'whatsapp'        => 1,
            'skype'           => 1,
            'working_hours'   => 1,
            'about_company'   => 2,
        ];
        
        $filled_weight = 0;
        $total_weight  = 0;
        
        foreach ( $fields as $field => $weight ) {
            $total_weight += $weight;
            $value = get_post_meta( $post_id, $field, true );
            
            if ( is_array( $value ) ) {
                $value = ! empty( $value ) ? implode( ',', $value ) : '';
            }
            
            if ( in_array( $field, [ 'exports_to', 'payment_terms', 'languages', 'packaging' ], true ) ) {
                if ( ! empty( $value ) && count( array_filter( array_map( 'trim', explode( ',', (string) $value ) ) ) ) > 0 ) {
                    $filled_weight += $weight;
                }
            } elseif ( ! empty( $value ) && (string) $value !== '0' && trim( (string) $value ) !== '' ) {
                $filled_weight += $weight;
            }
        }
        
        return $total_weight > 0 ? round( ( $filled_weight / $total_weight ) * 100 ) : 0;
    }
}

if ( ! function_exists( 'awps_get_missing_profile_fields' ) ) {
    function awps_get_missing_profile_fields( $user_id ) {
        // ✅ Find the linked supplier post ID first
        $supplier_posts = get_posts( [
            'post_type'   => 'supplier',
            'post_status' => 'any',
            'meta_query'  => [
                [
                    'key'   => '_linked_user_id',
                    'value' => $user_id,
                ],
            ],
            'numberposts' => 1,
            'fields'      => 'ids',
        ] );
        
        if ( empty( $supplier_posts ) ) {
            return array_keys( [
                'company_logo'   => __( 'Company Logo', 'awps' ),
                'banner_image'   => __( 'Banner Image', 'awps' ),
                'company_name'   => __( 'Company Name', 'awps' ),
                'about_company'  => __( 'About Company', 'awps' ),
                'phone'          => __( 'Phone Number', 'awps' ),
                'whatsapp'       => __( 'WhatsApp', 'awps' ),
                'exports_to'     => __( 'Export Destinations', 'awps' ),
                'payment_terms'  => __( 'Payment Terms', 'awps' ),
                'contact_person' => __( 'Contact Person', 'awps' ),
            ] );
        }
        
        $post_id = $supplier_posts[0];
        
        // ✅ Now check POST meta (not user meta)
        $required = [
            'company_logo'   => __( 'Company Logo', 'awps' ),
            'banner_image'   => __( 'Banner Image', 'awps' ),
            'company_name'   => __( 'Company Name', 'awps' ),
            'about_company'  => __( 'About Company', 'awps' ),
            'phone'          => __( 'Phone Number', 'awps' ),
            'whatsapp'       => __( 'WhatsApp', 'awps' ),
            'exports_to'     => __( 'Export Destinations', 'awps' ),
            'payment_terms'  => __( 'Payment Terms', 'awps' ),
            'contact_person' => __( 'Contact Person', 'awps' ),
        ];
        
        $missing = [];
        foreach ( $required as $key => $label ) {
            $value = get_post_meta( $post_id, $key, true );
            
            if ( is_array( $value ) ) {
                $value = ! empty( $value ) ? implode( ',', $value ) : '';
            }
            
            if ( in_array( $key, [ 'exports_to', 'payment_terms' ], true ) ) {
                if ( empty( $value ) || count( array_filter( array_map( 'trim', explode( ',', (string) $value ) ) ) ) === 0 ) {
                    $missing[] = $label;
                }
            } elseif ( empty( $value ) || trim( (string) $value ) === '' || (string) $value === '0' ) {
                $missing[] = $label;
            }
        }
        
        return $missing;
    }
}

if ( ! function_exists( 'awps_get_supplier_product_count' ) ) {
    function awps_get_supplier_product_count( $user_id ) {
        return count(
            get_posts( [
                'post_type'   => 'product',
                'author'      => $user_id,
                'post_status' => 'publish',
                'fields'      => 'ids',
                'nopaging'    => true,
            ] )
        );
    }
}

if ( ! function_exists( 'awps_calculate_products_completion' ) ) {
    function awps_calculate_products_completion( $user_id ) {
        $products = get_posts( [
            'post_type'   => 'product',
            'author'      => $user_id,
            'post_status' => 'publish',
            'numberposts' => -1,
            'fields'      => 'ids',
        ] );
        
        if ( empty( $products ) ) {
            return 0;
        }
        
        $fields = [
            'post_title',
            'post_content',
            '_thumbnail_id',
            '_awps_product_gallery',
            'product_cat',
            '_awps_product_made_in_pakistan',
            '_awps_product_hs_code',
            '_awps_product_moq',
            '_awps_product_packaging',
            '_awps_product_lead_time',
            '_awps_product_shipping',
            '_awps_product_payment_terms',
            '_awps_product_incoterms',
            '_awps_product_key_benefits',
            '_awps_product_quality_control',
            '_awps_product_sample_policies',
            '_awps_product_certifications',
            '_awps_product_factory_video',
            'rank_math_title',
            'rank_math_description',
        ];
        
        $total_completion = 0;
        
        foreach ( $products as $product_id ) {
            $filled = 0;
            
            foreach ( $fields as $field ) {
                if ( 'product_cat' === $field ) {
                    $terms = wp_get_post_terms( $product_id, 'product_cat', [ 'fields' => 'ids' ] );
                    if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                        $filled++;
                    }
                    continue;
                }
                
                if ( in_array( $field, [ 'post_title', 'post_content' ], true ) ) {
                    $value = 'post_title' === $field ? get_the_title( $product_id ) : get_post_field( 'post_content', $product_id );
                    if ( ! empty( $value ) && '' !== trim( $value ) ) {
                        $filled++;
                    }
                    continue;
                }
                
                $value = get_post_meta( $product_id, $field, true );
                
                if ( is_array( $value ) ) {
                    $value = ! empty( $value ) ? implode( ',', $value ) : '';
                }
                
                if ( in_array(
                    $field,
                    [
                        '_awps_product_packaging',
                        '_awps_product_shipping',
                        '_awps_product_payment_terms',
                        '_awps_product_incoterms',
                        '_awps_product_key_benefits',
                        '_awps_product_quality_control',
                        '_awps_product_sample_policies',
                    ],
                    true
                ) ) {
                    if ( ! empty( $value ) && count( array_filter( array_map( 'trim', explode( ',', (string) $value ) ) ) ) > 0 ) {
                        $filled++;
                    }
                } elseif ( '_awps_product_certifications' === $field ) {
                    if ( ! empty( $value ) && count( array_filter( array_map( 'intval', explode( ',', (string) $value ) ) ) ) > 0 ) {
                        $filled++;
                    }
                } elseif ( ! empty( $value ) && '0' !== (string) $value && '' !== trim( (string) $value ) ) {
                    $filled++;
                }
            }
            
            $product_completion = count( $fields ) > 0 ? ( $filled / count( $fields ) ) * 100 : 0;
            $total_completion  += $product_completion;
        }
        
        return round( $total_completion / count( $products ) );
    }
}

// ─────────────────────────────────────────────────────────────
// HANDLE DELETE & MARK READ ACTIONS
// ─────────────────────────────────────────────────────────────

if ( isset( $_GET['action'], $_GET['inquiry_id'] ) && 'delete_inquiry' === $_GET['action'] ) {
    $inquiry_id = intval( $_GET['inquiry_id'] );
    $nonce      = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
    
    if ( wp_verify_nonce( $nonce, 'delete_inquiry_' . $inquiry_id ) ) {
        awps_delete_inquiry_frontend( $inquiry_id, $user_id );
        wp_safe_redirect( remove_query_arg( [ 'action', 'inquiry_id', '_wpnonce' ] ) );
        exit;
    }
}

// ─────────────────────────────────────────────────────────────
// DASHBOARD DATA PREPARATION
// ─────────────────────────────────────────────────────────────

$company_name        = get_user_meta( $user_id, 'company_name', true ) ?: $current_user->display_name;
$total_products      = awps_get_supplier_product_count( $user_id );
$total_inquiries     = awps_get_supplier_inquiry_count( $user_id, 'all' );
$unread_inquiries    = awps_get_supplier_inquiry_count( $user_id, 'unread' );
$profile_views       = awps_get_profile_views( $user_id, 7 );
$completion_pct      = awps_calculate_profile_completion( $user_id );
$missing_fields      = awps_get_missing_profile_fields( $user_id );
$products_completion = awps_calculate_products_completion( $user_id );

$visitor_countries   = awps_get_visitor_countries( $user_id, 5 );
$top_products        = awps_get_top_viewed_products( $user_id, 3 );

$prev_week_views     = awps_get_profile_views( $user_id, 14 ) - $profile_views;
$views_change_pct    = $prev_week_views > 0 ? round( ( ( $profile_views - $prev_week_views ) / $prev_week_views ) * 100 ) : 0;

get_header();
?>


<?php
// 🔍 TEMPORARY DEBUG: Show raw meta values
if ( current_user_can( 'manage_options' ) && isset( $_GET['debug_views'] ) ) {
    echo '<div style="background:#fff; border:2px solid #dc3545; padding:20px; margin:20px; font-family:monospace; font-size:12px;">';
    echo '<h3 style="margin:0 0 10px 0;">🔍 View Meta Debug</h3>';
    
    // Get supplier post
    $supplier_posts = get_posts( [
        'post_type' => 'supplier',
        'meta_query' => [ [ 'key' => '_linked_user_id', 'value' => $user_id ] ],
        'numberposts' => 1,
        'fields' => 'ids',
    ] );
    
    if ( ! empty( $supplier_posts ) ) {
        $sid = $supplier_posts[0];
        echo '<strong>Supplier Post #' . $sid . '</strong><br>';
        echo 'Raw meta: <pre>' . print_r( get_post_meta( $sid, '_awps_profile_views', true ), true ) . '</pre>';
        echo 'Calculated (7d): ' . _awps_get_date_range_views( $sid, '_awps_profile_views', 7 ) . '<br><br>';
    }
    
    // Get products
    $products = get_posts( [
        'post_type' => 'product',
        'author' => $user_id,
        'post_status' => 'publish',
        'fields' => 'ids',
        'nopaging' => true,
    ] );
    
    echo '<strong>Products (' . count( $products ) . ')</strong><br>';
    foreach ( $products as $pid ) {
        echo 'Product #' . $pid . ': <pre>' . print_r( get_post_meta( $pid, '_awps_product_views', true ), true ) . '</pre>';
        echo 'Calculated (7d): ' . _awps_get_date_range_views( $pid, '_awps_product_views', 7 ) . '<br>';
    }
    
    echo '</div>';
}
?>

<main id="supplier-dashboard" class="site-main awps-dashboard">
    <div class="container">
        
        <!-- Header -->
        <header class="dashboard-header">
            <h1><?php esc_html_e( '📊 Exporter Dashboard', 'awps' ); ?></h1>
        </header>

        <!-- Quick Stats Bar -->
        <div class="dashboard-stats">
            <!-- Total Inquiries -->
            <div class="stat-card stat-inquiries">
                <div class="stat-value"><?php echo (int) $total_inquiries; ?></div>
                <div class="stat-label"><?php esc_html_e( 'Total Inquiries', 'awps' ); ?></div>
            </div>
            
            <!-- Unread Inquiries -->
            <div class="stat-card stat-unread">
                <div class="stat-value" id="unread-count"><?php echo (int) $unread_inquiries; ?></div>
                <div class="stat-label"><?php esc_html_e( 'Unread', 'awps' ); ?></div>
            </div>
            
            <!-- Profile Views -->
            <div class="stat-card stat-views">
                <div class="stat-value"><?php echo (int) $profile_views; ?></div>
                <div class="stat-label"><?php esc_html_e( 'Profile Views (7d)', 'awps' ); ?></div>
            </div>
            
            <!-- Published Products -->
            <div class="stat-card stat-products">
                <div class="stat-value"><?php echo (int) $total_products; ?></div>
                <div class="stat-label"><?php esc_html_e( 'Products', 'awps' ); ?></div>
            </div>
        </div>

        <!-- Recent Inquiries Section -->
        <section class="dashboard-section">
            <h2><?php esc_html_e( '📬 Recent Inquiries', 'awps' ); ?></h2>
            
            <div class="inquiries-table-wrapper">
                <?php
                $recent_inquiries = awps_get_recent_inquiries( $user_id, 10 );
                $read_inquiries   = awps_get_read_inquiries( $user_id );
                
                if ( $recent_inquiries->have_posts() ) :
                    ?>
                    <table class="inquiries-table">
                        <thead>
                            <tr>
                                <th scope="col"><?php esc_html_e( 'Buyer', 'awps' ); ?></th>
                                <th scope="col"><?php esc_html_e( 'Product', 'awps' ); ?></th>
                                <th scope="col"><?php esc_html_e( 'Date', 'awps' ); ?></th>
                                <th scope="col"><?php esc_html_e( 'Status', 'awps' ); ?></th>
                                <th scope="col" class="text-right"><?php esc_html_e( 'Actions', 'awps' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ( $recent_inquiries->have_posts() ) : $recent_inquiries->the_post();
                                $inquiry_id   = get_the_ID();
                                $buyer_name   = get_post_meta( $inquiry_id, 'buyer_name', true );
                                $buyer_email  = get_post_meta( $inquiry_id, 'buyer_email', true );
                                $product_id   = get_post_meta( $inquiry_id, 'product_id', true );
                                $product_name = $product_id ? get_the_title( $product_id ) : __( 'General Inquiry', 'awps' );
                                $product_url  = $product_id ? get_permalink( $product_id ) : '#';
                                $inquiry_date = get_the_date( 'M j, Y' );
                                $inquiry_code = get_post_meta( $inquiry_id, 'inquiry_code', true );
                                $replied      = get_post_meta( $inquiry_id, 'replied', true );
                                $company      = get_post_meta( $inquiry_id, 'company_name', true );
                                $country      = get_post_meta( $inquiry_id, 'buyer_country', true );
                                $qty          = get_post_meta( $inquiry_id, 'buyer_qty_formatted', true );
                                $message      = get_post_meta( $inquiry_id, 'buyer_message', true );
                                $port         = get_post_meta( $inquiry_id, 'buyer_destination_port', true );
                                $shipment     = get_post_meta( $inquiry_id, 'buyer_shipment_type', true );
                                $whatsapp     = get_post_meta( $inquiry_id, 'buyer_whatsapp', true );
                                
                                $is_read = in_array( $inquiry_id, $read_inquiries, true );
                                ?>
                                <tr class="inquiry-row <?php echo $is_read ? 'is-read' : ''; ?>" data-inquiry-id="<?php echo (int) $inquiry_id; ?>">
                                    <td class="buyer-cell">
                                        <strong><?php echo esc_html( $buyer_name ); ?></strong><br>
                                        <span class="buyer-company"><?php echo esc_html( $company ); ?></span><br>
                                        <small class="buyer-email"><?php echo esc_html( $buyer_email ); ?></small>
                                    </td>
                                    <td class="product-cell">
                                        <a href="<?php echo esc_url( $product_url ); ?>" class="product-link"><?php echo esc_html( $product_name ); ?></a>
                                    </td>
                                    <td class="date-cell"><?php echo esc_html( $inquiry_date ); ?></td>
                                    <td class="status-cell">
                                        <?php if ( $replied ) : ?>
                                            <span class="status-badge status-replied"><?php esc_html_e( '✓ Replied', 'awps' ); ?></span>
                                        <?php elseif ( $is_read ) : ?>
                                            <span class="status-badge status-read"><?php esc_html_e( '○ Read', 'awps' ); ?></span>
                                        <?php else : ?>
                                            <span class="status-badge status-new"><?php esc_html_e( '● New', 'awps' ); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="actions-cell text-right">
                                        <button type="button" 
                                                class="button button-small btn-details" 
                                                data-inquiry-id="<?php echo (int) $inquiry_id; ?>"
                                                aria-expanded="false"
                                                aria-controls="inquiry-details-<?php echo (int) $inquiry_id; ?>">
                                            <?php esc_html_e( 'Details', 'awps' ); ?>
                                        </button>
                                        <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'delete_inquiry', 'inquiry_id' => $inquiry_id ] ), 'delete_inquiry_' . $inquiry_id ) ); ?>" 
                                           class="button button-small button-danger btn-delete"
                                           onclick="return confirm('<?php echo esc_js( __( 'Delete this inquiry from your dashboard?', 'awps' ) ); ?>');">
                                            <?php esc_html_e( 'Delete', 'awps' ); ?>
                                        </a>
                                    </td>
                                </tr>
                                
                                <!-- Hidden Details Row -->
                                <tr id="inquiry-details-<?php echo (int) $inquiry_id; ?>" class="inquiry-details-row" hidden>
                                    <td colspan="5">
                                        <div class="inquiry-details-card">
                                            <h4>
                                                <?php
                                                printf(
                                                    /* translators: %s: Inquiry code */
                                                    esc_html__( 'Inquiry Details - %s', 'awps' ),
                                                    esc_html( $inquiry_code )
                                                );
                                                ?>
                                            </h4>
                                            
                                            <div class="details-grid">
                                                <div class="detail-item">
                                                    <strong><?php esc_html_e( 'Company:', 'awps' ); ?></strong>
                                                    <p><?php echo esc_html( $company ); ?></p>
                                                </div>
                                                <div class="detail-item">
                                                    <strong><?php esc_html_e( 'Country:', 'awps' ); ?></strong>
                                                    <p><?php echo esc_html( $country ); ?></p>
                                                </div>
                                                <?php if ( $whatsapp ) : ?>
                                                <div class="detail-item">
                                                    <strong><?php esc_html_e( 'WhatsApp:', 'awps' ); ?></strong>
                                                    <p><?php echo esc_html( $whatsapp ); ?></p>
                                                </div>
                                                <?php endif; ?>
                                                <div class="detail-item">
                                                    <strong><?php esc_html_e( 'Destination Port:', 'awps' ); ?></strong>
                                                    <p><?php echo esc_html( $port ); ?></p>
                                                </div>
                                                <div class="detail-item">
                                                    <strong><?php esc_html_e( 'Shipment Type:', 'awps' ); ?></strong>
                                                    <p><?php echo esc_html( $shipment ); ?></p>
                                                </div>
                                                <div class="detail-item">
                                                    <strong><?php esc_html_e( 'Quantity:', 'awps' ); ?></strong>
                                                    <p><?php echo esc_html( $qty ); ?></p>
                                                </div>
                                            </div>
                                            
                                            <div class="message-section">
                                                <strong><?php esc_html_e( 'Message:', 'awps' ); ?></strong>
                                                <p class="inquiry-message"><?php echo esc_html( $message ); ?></p>
                                            </div>
                                            
                                            <div class="details-actions">
                                                <button type="button" class="button button-small btn-close" data-inquiry-id="<?php echo (int) $inquiry_id; ?>">
                                                    <?php esc_html_e( 'Close', 'awps' ); ?>
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                            endwhile;
                            ?>
                        </tbody>
                    </table>
                    <?php
                    wp_reset_postdata();
                else :
                    ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox" aria-hidden="true"></i>
                        <p><strong><?php esc_html_e( 'No inquiries yet', 'awps' ); ?></strong></p>
                        <p><?php esc_html_e( 'Keep optimizing your profile and products to attract more buyers!', 'awps' ); ?></p>
                    </div>
                    <?php
                endif;
                ?>
            </div>
        </section>

        <!-- Profile & Products Completion -->
        <section class="dashboard-section">
            <div class="completion-grid">
                <!-- Profile Completion Card -->
                <div class="completion-card">
                    <h3>
                        <i class="fas fa-building" aria-hidden="true"></i> 
                        <?php esc_html_e( 'Company Profile Completion', 'awps' ); ?>
                    </h3>
                    
                    <div class="progress-bar" role="progressbar" aria-valuenow="<?php echo (int) $completion_pct; ?>" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-fill" style="width: <?php echo (int) $completion_pct; ?>%; background: <?php echo $completion_pct >= 80 ? '#28a745' : ( $completion_pct >= 50 ? '#ffc107' : '#dc3545' ); ?>;"></div>
                    </div>
                    
                    <p class="progress-label">
                        <strong><?php echo (int) $completion_pct; ?>% <?php esc_html_e( 'Complete', 'awps' ); ?></strong>
                        <?php
                        if ( $completion_pct >= 80 ) :
                            echo '<span class="progress-status status-excellent">' . esc_html__( '✅ Excellent!', 'awps' ) . '</span>';
                        elseif ( $completion_pct >= 50 ) :
                            echo '<span class="progress-status status-good">' . esc_html__( '🔄 Good progress', 'awps' ) . '</span>';
                        else :
                            echo '<span class="progress-status status-needs-work">' . esc_html__( '⚠️ Add more details', 'awps' ) . '</span>';
                        endif;
                        ?>
                    </p>
                    
                    <?php if ( ! empty( $missing_fields ) ) : ?>
                        <p class="missing-fields">
                            <?php
                            printf(
                                /* translators: %s: List of missing fields */
                                esc_html__( 'Missing: %s', 'awps' ),
                                esc_html( implode( ', ', $missing_fields ) )
                            );
                            ?>
                        </p>
                        <a href="<?php echo esc_url( home_url( '/dashboard/?tab=company-profile' ) ); ?>" class="button button-small">
                            <?php esc_html_e( 'Complete Profile →', 'awps' ); ?>
                        </a>
                    <?php else : ?>
                        <p class="progress-status status-complete">
                            <?php esc_html_e( '✓ All key fields completed!', 'awps' ); ?>
                        </p>
                    <?php endif; ?>
                </div>
                
                <!-- Products Completion Card -->
                <div class="completion-card">
                    <h3>
                        <i class="fas fa-boxes" aria-hidden="true"></i> 
                        <?php esc_html_e( 'Products Completion', 'awps' ); ?>
                    </h3>
                    
                    <div class="progress-bar" role="progressbar" aria-valuenow="<?php echo (int) $products_completion; ?>" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-fill" style="width: <?php echo (int) $products_completion; ?>%; background: <?php echo $products_completion >= 80 ? '#28a745' : ( $products_completion >= 50 ? '#ffc107' : '#dc3545' ); ?>;"></div>
                    </div>
                    
                    <p class="progress-label">
                        <strong><?php echo (int) $products_completion; ?>% <?php esc_html_e( 'Average Complete', 'awps' ); ?></strong>
                        <span class="product-count">(<?php echo (int) $total_products; ?> <?php echo _n( 'product', 'products', $total_products, 'awps' ); ?>)</span>
                    </p>
                    
                    <?php if ( $total_products > 0 ) : ?>
                        <p class="completion-hint">
                            <?php if ( $products_completion < 80 ) : ?>
                                <?php esc_html_e( 'Improve product details to attract more buyers', 'awps' ); ?>
                            <?php else : ?>
                                <?php esc_html_e( 'Great job! Your products are well-optimized', 'awps' ); ?>
                            <?php endif; ?>
                        </p>
                        <a href="<?php echo esc_url( home_url( '/dashboard/?tab=products' ) ); ?>" class="button button-small">
                            <?php esc_html_e( 'Manage Products →', 'awps' ); ?>
                        </a>
                    <?php else : ?>
                        <p class="completion-hint">
                            <?php esc_html_e( 'Add your first product to get started', 'awps' ); ?>
                        </p>
                        <a href="<?php echo esc_url( home_url( '/dashboard/?tab=products&action=add' ) ); ?>" class="button button-small">
                            <?php esc_html_e( '+ Add Product', 'awps' ); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Helpful Tips Section -->
        <section class="dashboard-section">
            <div class="pro-tips">
                <h3><?php esc_html_e( '💡 Pro Tips for More Inquiries', 'awps' ); ?></h3>
                <ul>
                    <li><?php esc_html_e( 'Add high-quality product images to increase profile views by up to 3x', 'awps' ); ?></li>
                    <li><?php esc_html_e( 'Include certifications (ISO, Halal, etc.) to build buyer trust', 'awps' ); ?></li>
                    <li><?php esc_html_e( 'Respond to inquiries within 24 hours to improve your response rate', 'awps' ); ?></li>
                    <li><?php esc_html_e( 'Use specific keywords in product titles (e.g., "Premium Basmati Rice 1121")', 'awps' ); ?></li>
                </ul>
            </div>
        </section>

    </div>
</main>

<!-- ✅ INLINE JS FOR INQUIRY TOGGLE (kept exactly as working) -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const userId = <?php echo (int) $user_id; ?>;
    
    // Get stored read inquiries from localStorage (per-user)
    function getReadInquiries() {
        try {
            return JSON.parse(localStorage.getItem('awps_read_inquiries_' + userId) || '[]');
        } catch (e) {
            console.error('localStorage parse error:', e);
            return [];
        }
    }
    
    // Save read inquiry to localStorage (idempotent)
    function saveReadInquiry(inquiryId) {
        let read = getReadInquiries();
        if (!read.includes(inquiryId)) {
            read.push(inquiryId);
            localStorage.setItem('awps_read_inquiries_' + userId, JSON.stringify(read));
            return true;
        }
        return false;
    }
    
    // Mark inquiry as read (idempotent)
    function markInquiryRead(inquiryId) {
        if (!getReadInquiries().includes(inquiryId)) {
            saveReadInquiry(inquiryId);
            
            // Update UI: status badge
            const row = document.querySelector('.inquiry-row[data-inquiry-id="' + inquiryId + '"]');
            if (row) {
                const statusCell = row.querySelector('.status-cell');
                if (statusCell && !statusCell.innerHTML.includes('Read') && !statusCell.innerHTML.includes('Replied')) {
                    statusCell.innerHTML = '<span class="status-badge status-read">○ <?php echo esc_js( __( 'Read', 'awps' ) ); ?></span>';
                    row.classList.add('is-read');
                }
            }
            
            // Update unread count
            updateUnreadCount(-1);
        }
    }
    
    // Update the unread count in stats bar
    function updateUnreadCount(delta) {
        const unreadStat = document.getElementById('unread-count');
        if (unreadStat) {
            let count = parseInt(unreadStat.textContent.trim()) || 0;
            count = Math.max(0, count + delta);
            unreadStat.textContent = count;
        }
    }
    
    // Sync unread count based on visible badges + localStorage
    function syncUnreadCount() {
        const readInquiries = getReadInquiries();
        const unreadStat = document.getElementById('unread-count');
        if (!unreadStat) return;
        
        let actualNewCount = 0;
        const rows = document.querySelectorAll('.inquiry-row');
        
        rows.forEach(function(row) {
            const statusBadge = row.querySelector('.status-badge.status-new');
            if (statusBadge) {
                const inquiryId = parseInt(row.dataset.inquiryId);
                if (!readInquiries.includes(inquiryId)) {
                    actualNewCount++;
                }
            }
        });
        
        unreadStat.textContent = actualNewCount;
    }
    
    // Show inquiry details and mark as read if new
    function showInquiryDetails(inquiryId) {
        const detailsRow = document.getElementById('inquiry-details-' + inquiryId);
        const triggerBtn = document.querySelector('.btn-details[data-inquiry-id="' + inquiryId + '"]');
        
        if (detailsRow && triggerBtn) {
            const isHidden = detailsRow.hidden;
            detailsRow.hidden = !isHidden;
            triggerBtn.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
            
            // Mark as read only when opening (not closing)
            if (isHidden && !getReadInquiries().includes(inquiryId)) {
                markInquiryRead(inquiryId);
            }
        }
    }
    
    // Hide inquiry details
    function hideInquiryDetails(inquiryId) {
        const detailsRow = document.getElementById('inquiry-details-' + inquiryId);
        const triggerBtn = document.querySelector('.btn-details[data-inquiry-id="' + inquiryId + '"]');
        
        if (detailsRow && triggerBtn) {
            detailsRow.hidden = true;
            triggerBtn.setAttribute('aria-expanded', 'false');
        }
    }
    
    // Event delegation for details buttons
    document.getElementById('supplier-dashboard').addEventListener('click', function(e) {
        // Details button
        if (e.target.closest('.btn-details')) {
            e.preventDefault();
            const btn = e.target.closest('.btn-details');
            const inquiryId = parseInt(btn.dataset.inquiryId);
            showInquiryDetails(inquiryId);
        }
        
        // Close button
        if (e.target.closest('.btn-close')) {
            e.preventDefault();
            const btn = e.target.closest('.btn-close');
            const inquiryId = parseInt(btn.dataset.inquiryId);
            hideInquiryDetails(inquiryId);
        }
    });
    
    // On page load: restore "Read" status AND sync the unread count
    const readInquiries = getReadInquiries();
    
    readInquiries.forEach(function(inquiryId) {
        const row = document.querySelector('.inquiry-row[data-inquiry-id="' + inquiryId + '"]');
        if (row) {
            const statusCell = row.querySelector('.status-cell');
            if (statusCell && !statusCell.innerHTML.includes('Read') && !statusCell.innerHTML.includes('Replied')) {
                statusCell.innerHTML = '<span class="status-badge status-read">○ <?php echo esc_js( __( 'Read', 'awps' ) ); ?></span>';
                row.classList.add('is-read');
            }
        }
    });
    
    syncUnreadCount();
});
</script>

<!-- ✅ MINIMAL CSS (can move to SASS later) -->
<style>
/* Dashboard Layout */
.awps-dashboard {
    padding: 30px 0;
    min-height: 60vh;
}
.awps-dashboard .container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Header */
.dashboard-header h1 {
    margin: 0 0 8px 0;
    font-size: 24px;
    font-weight: 700;
    color: #23282d;
}

/* Stats Grid */
.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 30px;
}
.stat-card {
    background: #fff;
    padding: 18px 20px;
    border-radius: 8px;
    border-left: 4px solid #007cba;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.stat-card.stat-unread { border-left-color: #dc3545; }
.stat-card.stat-views { border-left-color: #28a745; }
.stat-card.stat-products { border-left-color: #6c757d; }
.stat-value {
    font-size: 26px;
    font-weight: 700;
    line-height: 1;
    color: #007cba;
}
.stat-card.stat-unread .stat-value { color: #dc3545; }
.stat-card.stat-views .stat-value { color: #28a745; }
.stat-card.stat-products .stat-value { color: #6c757d; }
.stat-label {
    font-size: 13px;
    color: #666;
    margin-top: 4px;
}

/* Table Styles */
.inquiries-table-wrapper {
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
}
.inquiries-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}
.inquiries-table thead {
    background: #f8f9fa;
}
.inquiries-table th {
    text-align: left;
    padding: 14px 18px;
    font-weight: 600;
    font-size: 13px;
    color: #555;
    border-bottom: 1px solid #eee;
}
.inquiries-table td {
    padding: 14px 18px;
    border-bottom: 1px solid #f0f0f0;
    vertical-align: top;
}
.inquiry-row:hover {
    background: #f8f9fa;
}
.inquiry-row.is-read {
    opacity: 0.85;
}
.buyer-company,
.buyer-email {
    color: #666;
    font-size: 13px;
}
.buyer-email {
    color: #007cba;
}
.product-link {
    color: #007cba;
    text-decoration: none;
    font-weight: 500;
}
.product-link:hover {
    text-decoration: underline;
}
.text-right {
    text-align: right;
}

/* Status Badges */
.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}
.status-replied {
    background: #d4edda;
    color: #155724;
}
.status-read {
    background: #e2e3e5;
    color: #383d41;
}
.status-new {
    background: #fff3cd;
    color: #856404;
}

/* Buttons */
.button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    background: #007cba;
    color: #fff !important;
    border: none;
    border-radius: 4px;
    padding: 8px 16px;
    font-size: 13px;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: background 0.2s;
}
.button:hover {
    background: #005a87;
}
.button-small {
    padding: 7px 14px;
    font-size: 12px;
}
.button-danger {
    background: #dc3545;
}
.button-danger:hover {
    background: #c82333;
}

/* Inquiry Details */
.inquiry-details-row[hidden] {
    display: none;
}
.inquiry-details-card {
    background: #fff;
    padding: 20px;
    border-radius: 6px;
    border: 1px solid #e0e0e0;
}
.inquiry-details-card h4 {
    margin: 0 0 15px 0;
    color: #007cba;
    font-size: 14px;
}
.details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 15px;
}
.detail-item strong {
    color: #555;
    font-size: 12px;
    display: block;
}
.detail-item p {
    margin: 4px 0;
}
.message-section {
    margin-top: 15px;
}
.message-section strong {
    color: #555;
    font-size: 12px;
    display: block;
}
.inquiry-message {
    margin: 8px 0;
    padding: 12px;
    background: #f8f9fa;
    border-left: 3px solid #007cba;
    border-radius: 0 4px 4px 0;
    white-space: pre-wrap;
}
.details-actions {
    margin-top: 15px;
    text-align: right;
}

/* Empty State */
.empty-state {
    padding: 30px;
    text-align: center;
    color: #666;
}
.empty-state i {
    font-size: 40px;
    color: #ccc;
    margin-bottom: 12px;
    display: block;
}
.empty-state p {
    margin: 0 0 8px 0;
    font-weight: 500;
}
.empty-state p:last-child {
    margin: 0;
    font-size: 13px;
}

/* Completion Cards */
.completion-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 22px;
    margin: 30px 0px;
}
.completion-card {
    background: #fff;
    padding: 22px;
    border-radius: 8px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
}
.completion-card h3 {
    margin: 0 0 18px 0;
    font-size: 14px;
    font-weight: 600;
    color: #333;
    display: flex;
    align-items: center;
    gap: 8px;
}
.completion-card h3 i {
    color: #6c757d;
}
.progress-bar {
    background: #e9ecef;
    border-radius: 8px;
    height: 12px;
    margin-bottom: 14px;
    overflow: hidden;
}
.progress-fill {
    height: 100%;
    transition: width 0.4s ease;
    border-radius: 8px;
}
.progress-label {
    margin: 0 0 8px 0;
    font-size: 13px;
}
.progress-label strong {
    color: #23282d;
}
.progress-status {
    margin-left: 6px;
}
.status-excellent { color: #28a745; }
.status-good { color: #ffc107; }
.status-needs-work { color: #dc3545; }
.status-complete {
    color: #28a745;
    font-weight: 500;
}
.missing-fields {
    margin: 0 0 12px 0;
    font-size: 12px;
    color: #666;
}
.completion-hint {
    margin: 0 0 12px 0;
    font-size: 12px;
    color: #666;
}
.product-count {
    color: #666;
    margin-left: 6px;
}

/* Pro Tips */
.pro-tips {
    background: #e7f3ff;
    border-left: 4px solid #007cba;
    padding: 18px 22px;
    border-radius: 0 8px 8px 0;
}
.pro-tips h3 {
    margin: 0 0 10px 0;
    font-size: 14px;
    font-weight: 600;
    color: #005a87;
}
.pro-tips ul {
    margin: 0;
    padding-left: 20px;
    font-size: 13px;
    color: #333;
    line-height: 1.6;
}

/* Responsive */
@media (max-width: 768px) {
    .dashboard-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    .inquiries-table {
        font-size: 13px;
    }
    .inquiries-table th,
    .inquiries-table td {
        padding: 10px 12px !important;
    }
}
@media (max-width: 480px) {
    .dashboard-stats {
        grid-template-columns: 1fr;
    }
}
</style>

<?php get_footer(); ?>