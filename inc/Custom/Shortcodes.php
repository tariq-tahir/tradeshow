<?php

namespace Awps\Custom;

/**
 * Handles registration and logic for all custom shortcodes.
 */
class Shortcodes
{
    /**
     * Register default hooks and actions for WordPress.
     *
     * @return void
     */
    public function register()
    {
        // Example: Register multiple shortcodes
        add_shortcode('suppliers_directory', [$this, 'suppliers_directory_shortcode']);
        add_shortcode('awps_login_dropdown', [$this, 'awps_login_dropdown_shortcode']);
        add_shortcode('contact_form', [$this, 'contact_form_shortcode']);
        add_action('template_redirect', [$this, 'handle_contact_form_submission']);

        add_shortcode('latest_products', [$this, 'my_custom_latest_products_shortcode']);

        // breadcrumb shortcode
        add_shortcode('awps_custom_breadcrumb', [$this, 'awps_custom_breadcrumb_shortcode']);

        add_shortcode('latest_posts', [$this, 'my_custom_latest_posts_shortcode']);

        // Add this to Shortcodes.php::register()
        add_shortcode('awps_my_account', [$this, 'awps_my_account_shortcode']);


        add_shortcode('awps_authors_list', [$this, 'awps_my_authors_list']);

    }


    /**
 * Shortcode: [awps_authors_list]
 * Displays a grid of all authors with posts
 */

    public function awps_my_authors_list($atts)
    {
    
        $atts = shortcode_atts([
            'min_posts' => 1, // Only show authors with at least X posts
            'order' => 'name', // 'name' or 'post_count'
            'columns' => 3, // Grid columns
        ], $atts);
        
        // Get all users who can publish posts
        $args = [
            'role__in' => ['administrator', 'editor', 'author', 'contributor'],
            'orderby' => $atts['order'] === 'post_count' ? 'post_count' : 'display_name',
            'order' => 'ASC',
            'fields' => 'all_with_meta',
        ];
        
        $users = get_users($args);
        $output = '<h1 style="text-align: center;margin-top: 100px;">Our Export Contributors</h1><div class="awps-authors-grid" style="display:grid; grid-template-columns:repeat(' . intval($atts['columns']) . ', 1fr); gap:20px;margin: 100px 0px">';
        
        foreach ($users as $user) {
            $post_count = count_user_posts($user->ID);
            
            // Skip if below minimum posts
            if ($post_count < $atts['min_posts']) continue;
            
            $author_url = get_author_posts_url($user->ID);
            $avatar = get_avatar($user->ID, 80, '', '', ['class' => 'rounded-full']);
            $bio = get_the_author_meta('description', $user->ID);
            
            $output .= sprintf('
                <a href="%s" style="text-decoration:none; color:inherit; display:block; padding:20px; background:#f9f9f9; border-radius:8px; text-align:center; transition:transform 0.2s; border:1px solid #eee;">
                    <div style="margin-bottom:15px;">%s</div>
                    <h3 style="margin:0 0 5px 0; font-size:1.1rem; color:#333;">%s</h3>
                    <p style="margin:0 0 10px 0; color:#666; font-size:14px;">%s</p>
                    %s
                </a>
            ',
                esc_url($author_url),
                $avatar,
                esc_html($user->display_name),
                esc_html(wp_trim_words($bio, 15)),
                $post_count ? '<span style="display:inline-block; background:#007cba; color:white; padding:4px 12px; border-radius:20px; font-size:12px;">' . $post_count . ' posts</span>' : ''
            );
        }
        
        $output .= '</div>';
        
        // If no authors found
        if (strpos($output, '<a href') === false) {
            return '<p style="text-align:center; color:#666;">No authors found.</p>';
        }
        
        return $output;
    }

    

    // Add this new method to Shortcodes.php
    public function awps_my_account_shortcode()
    {
        if (is_user_logged_in()) {
            wp_safe_redirect(home_url('/dashboard/'));
            exit;
        }
        
        if (class_exists('AWPS\Suppliers\Account')) {
            $account = new \AWPS\Suppliers\Account();
            return $account->render_account_page();
        }
        
        return '<p>Account system not initialized.</p>';
    }

    /**
     * Shortcode to display Latest Products (Non-WooCommerce Version)
     * Usage: [latest_products limit="4" columns="4"]
     */
    function my_custom_latest_products_shortcode($atts) {
        // 1. Define default attributes
        $atts = shortcode_atts(array(
            'limit'   => 6,
            'columns' => 6,
            'orderby' => 'date',
            'order'   => 'DESC',
            'type'    => 'product', // Change this if your CPT slug is different
        ), $atts);

        // 2. Build the Query Arguments
        $args = array(
            'post_type'      => $atts['type'],
            'posts_per_page' => intval($atts['limit']),
            'orderby'        => $atts['orderby'],
            'order'          => $atts['order'],
            'post_status'    => 'publish',
        );

        // 3. Execute the Query
        $query = new \WP_Query($args);

        // 4. Start Output Buffering
        ob_start();

        if ($query->have_posts()) {
            ?>
            <div class="latest-products-wrapper columns-<?php echo esc_attr($atts['columns']); ?>">
                <ul class="products-grid columns-<?php echo esc_attr($atts['columns']); ?>">
                    <?php while ($query->have_posts()) : $query->the_post(); ?>
                        <li class="product-item">
                            <div class="product-card">
                                <!-- Product Image -->
                                <div class="product-image">
                                    <?php if (has_post_thumbnail()) : ?>
                                        <a href="<?php the_permalink(); ?>">
                                            <?php the_post_thumbnail('medium'); ?>
                                        </a>
                                    <?php else : ?>
                                        <a href="<?php the_permalink(); ?>">
                                            <img src="<?php echo esc_url(get_template_directory_uri()); ?>/images/placeholder.png" alt="<?php the_title_attribute(); ?>" />
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <!-- Product Info -->
                                <div class="product-info">
                                    <h3 class="product-title">
                                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                    </h3>
                                    
                                </div>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            </div>
            <?php
            // Reset Post Data
            wp_reset_postdata();
        } else {
            echo '<p>No products found.</p>';
        }

        return ob_get_clean();
    }

    /**
     * Shortcode: [suppliers_directory]
     *
     * Displays a paginated directory of enabled exporters (from CPT, not users).
     *
     * @return string
     */
    function suppliers_directory_shortcode()
    {
        if (is_admin()) {
            return '';
        }

        // Get current page number
        $paged = max(1, (int) get_query_var('paged'));

        // Query exporter CPT posts
        $args = [
            'post_type'      => 'supplier',
            'post_status'    => 'publish',
            'posts_per_page' => 12,
            'paged'          => $paged,
            'meta_query'     => [
                [
                    'key'     => 'company_status',
                    'value'   => 'enabled',
                    'compare' => '='
                ]
            ],
            'orderby'        => 'title',
            'order'          => 'ASC'
        ];

        $query = new \WP_Query($args);
        $total_pages = $query->max_num_pages;

        ob_start();
        ?>
        <section class="suppliers-directory">
            <div class="container">
                <div class="supplier-grid">
                    <?php if ($query->have_posts()) : ?>
                        <?php while ($query->have_posts()) : $query->the_post(); ?>
                            <?php
                            // Make $post available in card.php
                            global $post;
                            $card_path = get_theme_file_path('views/suppliers/card.php');
                            if (file_exists($card_path)) {
                                include $card_path;
                            } else {
                                echo '<p>No card template.</p>';
                            }
                            ?>
                        <?php endwhile; ?>
                        <?php wp_reset_postdata(); ?>
                    <?php else : ?>
                        <p class="no-results">No suppliers found.</p>
                    <?php endif; ?>
                </div>

                <?php if ($total_pages > 1) : ?>
                    <div class="pagination">
                        <?php
                        echo paginate_links([
                            'total'   => $total_pages,
                            'current' => $paged,
                            'base'    => trailingslashit(get_permalink()) . '%_%',
                            'format'  => 'page/%#%/',
                            'prev_text' => '&laquo; Prev',
                            'next_text' => 'Next &raquo;',
                            'type'      => 'list',
                        ]);
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: [awps_login_dropdown]
     *
     * Displays a login/logout dropdown with user links.
     *
     * @return string
     */
    function awps_login_dropdown_shortcode() {
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            $username = esc_html($current_user->display_name);
            $dashboard_url = home_url('/dashboard/');
            $products_url = add_query_arg('tab', 'products', $dashboard_url);
            $change_password_url = add_query_arg('tab', 'change-password', $dashboard_url);
            $logout_url = wp_logout_url(home_url('/my-account/'));

            ob_start();
            ?>
            <div class="awps-login-dropdown">
                <span class="awps-welcome">Hi, <?= $username; ?> 👋</span>
                <div class="awps-dropdown-menu">
                    <a href="<?= esc_url($dashboard_url); ?>">Dashboard</a>
                    <a href="<?= esc_url($products_url); ?>">My Products</a>
                    <a href="<?= esc_url($change_password_url); ?>">Change Password</a>
                    <a href="<?= esc_url($logout_url); ?>">Logout</a>
                </div>
            </div>
            <?php
            return ob_get_clean();
        } else {
            $login_url = home_url('/my-account/');
            return '<a href="' . esc_url($login_url) . '" class="awps-login-link">Login</a>';
        }
    }

    /**
     * Shortcode: [awps_custom_breadcrumb]
     *
     * Displays Rank Math SEO breadcrumbs if available.
     *
     * @return string
     */
    public function awps_custom_breadcrumb_shortcode()
    {
        if ( is_front_page() || is_page( 'my-account' ) ) {
            return '';
        }

        ob_start();

        echo '<nav class="custom-breadcrumb">';
        echo '<a href="' . home_url() . '">Home</a> &raquo; ';

        if ( is_category() ) {

            $category = get_queried_object();
            echo single_cat_title('', false);

        } elseif ( is_single() ) {

            $category = get_the_category();
            if ( !empty($category) ) {
                echo '<a href="' . get_category_link($category[0]->term_id) . '">' . $category[0]->name . '</a> &raquo; ';
            }
            echo get_the_title();

        } elseif ( is_page() ) {

            global $post;

            if ( $post->post_parent ) {

                $ancestors = get_post_ancestors( $post->ID );
                $ancestors = array_reverse( $ancestors );

                foreach ( $ancestors as $ancestor ) {
                    echo '<a href="' . get_permalink($ancestor) . '">' . get_the_title($ancestor) . '</a> &raquo; ';
                }
            }

            echo get_the_title();

        } elseif ( is_singular() ) {

            $post_type = get_post_type();

            if ( $post_type != 'post' && $post_type != 'page' ) {

                $post_type_obj = get_post_type_object($post_type);

                echo '<a href="' . get_post_type_archive_link($post_type) . '">abc';
                echo $post_type_obj->labels->name;
                echo '</a> &raquo; ';
            }

            echo get_the_title();

        } elseif ( is_search() ) {

            echo 'Search results for "' . get_search_query() . '"';

        } elseif ( is_tag() ) {

            echo single_tag_title('', false);

        } elseif ( is_author() ) {

            echo 'Author: ' . get_the_author();

        } elseif ( is_archive() ) {

            echo post_type_archive_title('', false);

        } elseif ( is_home() ) {

               echo 'Blog';
        }

        echo '</nav>';

        return ob_get_clean();
    }

    

    /**
     * Shortcode: [contact_form]
     *
     * Displays contact form, handles submission, saves to inquiry CPT, sends email.
     *
     * @return string
     */
    public function contact_form_shortcode()
    {
        // Display form
        ob_start();
        include get_theme_file_path('views/contact-form.php');
        return ob_get_clean();
    }

    /**
     * Handle contact form submission with spam protection
     */
    public function handle_contact_form_submission()
    {
        // Only process POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        // Verify nonce
        if (!isset($_POST['awps_contact_form_nonce']) || !wp_verify_nonce($_POST['awps_contact_form_nonce'], 'awps_contact_form')) {
            return;
        }

        // ════════════════════════════════════════════════════════════════
        // 🔒 SPAM FILTER #1: HONEYPOT (blocks 70% of bots)
        // ════════════════════════════════════════════════════════════════
        if (!empty($_POST['website_url'])) {
            // Bot filled hidden field - silently redirect to fake success
            wp_redirect(add_query_arg('form_success', '1', wp_get_referer()));
            exit;
        }

        // ════════════════════════════════════════════════════════════════
        // 🔒 SPAM FILTER #2: TIME-BASED CHECK (blocks 20% of fast bots)
        // ════════════════════════════════════════════════════════════════
        $submit_time = intval($_POST['submit_time'] ?? 0);
        // Only enforce if JS ran (value > 0). Skip for no-JS users to avoid false positives
        if ($submit_time > 0 && $submit_time < 3) {
            wp_redirect(add_query_arg('form_success', '1', wp_get_referer()));
            exit;
        }

        // ════════════════════════════════════════════════════════════════
        // 🔒 SPAM FILTER #3: RATE LIMITING (blocks 9% of repeat spammers)
        // ════════════════════════════════════════════════════════════════
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $rate_key = 'contact_form_rate_' . md5($ip);
        $attempts = get_transient($rate_key);

        if ($attempts !== false && $attempts >= 2) {
            wp_redirect(add_query_arg('form_error', 'rate_limit', wp_get_referer()));
            exit;
        }

        // Increment counter (only for non-bot submissions)
        if ($attempts === false) {
            set_transient($rate_key, 1, 60); // 60 seconds expiry
        } else {
            set_transient($rate_key, $attempts + 1, 60);
        }

        // ════════════════════════════════════════════════════════════════
        // ✅ VALIDATION (after spam filters)
        // ════════════════════════════════════════════════════════════════
        $name = sanitize_text_field($_POST['name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $subject = sanitize_text_field($_POST['subject'] ?? '');
        $message = sanitize_textarea_field($_POST['message'] ?? '');

        if (empty($name) || empty($email) || empty($message)) {
            wp_redirect(add_query_arg('form_error', 'required', wp_get_referer()));
            exit;
        }

        if (!is_email($email)) {
            wp_redirect(add_query_arg('form_error', 'email', wp_get_referer()));
            exit;
        }

        // ════════════════════════════════════════════════════════════════
        // 💾 SAVE INQUIRY
        // ════════════════════════════════════════════════════════════════
        $inquiry_id = wp_insert_post([
            'post_type' => 'inquiry',
            'post_title' => $subject ?: 'Contact Form Inquiry',
            'post_status' => 'publish',
            'post_author' => 1,
            'post_content' => $message,
        ]);

        if ($inquiry_id) {
            // Use EXISTING meta keys from your Inquiries system
            update_post_meta($inquiry_id, 'buyer_name', $name);      // Shows as "Contact Person"
            update_post_meta($inquiry_id, 'buyer_email', $email);    // Shows as "Email"
            update_post_meta($inquiry_id, 'source', 'contact_form');
            
            // Optional tracking
            update_post_meta($inquiry_id, 'contact_date', current_time('mysql'));
            update_post_meta($inquiry_id, 'contact_ip', $ip);
            
        }

        // ════════════════════════════════════════════════════════════════
        // 📧 SEND EMAIL TO ADMIN
        // ════════════════════════════════════════════════════════════════
        $admin_email = get_option('admin_email');
        $headers = [
            'From: ' . $name . ' <' . $email . '>',
            'Reply-To: ' . $email,
            'Content-Type: text/html; charset=UTF-8'
        ];

        $email_body = '<h3>New Contact Form Inquiry</h3>';
        $email_body .= '<p><strong>Name:</strong> ' . esc_html($name) . '</p>';
        $email_body .= '<p><strong>Email:</strong> ' . esc_html($email) . '</p>';
        $email_body .= '<p><strong>Subject:</strong> ' . esc_html($subject) . '</p>';
        $email_body .= '<p><strong>Message:</strong></p>';
        $email_body .= '<p>' . nl2br(esc_html($message)) . '</p>';
        $email_body .= '<p><em>Sent from: ' . home_url() . ' | IP: ' . esc_html($ip) . '</em></p>';

        wp_mail($admin_email, 'New Contact: ' . $subject, $email_body, $headers);

        // ════════════════════════════════════════════════════════════════
        // ✅ SUCCESS REDIRECT
        // ════════════════════════════════════════════════════════════════
        wp_redirect(add_query_arg('form_success', '1', wp_get_referer()));
        exit;
    }


    /**
     * Shortcode to display Latest Blog Posts
     * Usage: [latest_posts limit="3" columns="3" show_excerpt="true"]
     */
    public function my_custom_latest_posts_shortcode($atts) {
        // 1. Define default attributes
        $atts = shortcode_atts(array(
            'limit'       => 3,
            'columns'     => 3,
            'show_excerpt' => 'true',
            'orderby'     => 'date',
            'order'       => 'DESC',
        ), $atts);

        // 2. Build the Query Arguments (Post Type = 'post')
        $args = array(
            'post_type'      => 'post',
            'posts_per_page' => intval($atts['limit']),
            'orderby'        => $atts['orderby'],
            'order'          => $atts['order'],
            'post_status'    => 'publish',
        );

        // 3. Execute the Query
        $query = new \WP_Query($args);

        // 4. Start Output Buffering
        ob_start();

        if ($query->have_posts()) {
            ?>
            <div class="latest-posts-wrapper columns-<?php echo esc_attr($atts['columns']); ?>">
                <ul class="posts-grid columns-<?php echo esc_attr($atts['columns']); ?>">
                    <?php while ($query->have_posts()) : $query->the_post(); ?>
                        <li class="post-item">
                            <article class="post-card">
                                <!-- Post Image -->
                                <div class="post-image">
                                    <?php if (has_post_thumbnail()) : ?>
                                        <a href="<?php the_permalink(); ?>">
                                            <?php the_post_thumbnail('medium'); ?>
                                        </a>
                                    <?php else : ?>
                                        <a href="<?php the_permalink(); ?>">
                                            <img src="<?php echo esc_url(get_template_directory_uri()); ?>/images/placeholder.png" alt="<?php the_title_attribute(); ?>" />
                                        </a>
                                    <?php endif; ?>
                                    
                                    <!-- Post Category Badge -->
                                    <div class="post-category">
                                        <?php 
                                        $categories = get_the_category();
                                        if (!empty($categories)) {
                                            echo esc_html($categories[0]->name);
                                        }
                                        ?>
                                    </div>
                                </div>

                                <!-- Post Info -->
                                <div class="post-info">
                                    <!-- Post Meta (Date) -->
                                    <div class="post-meta">
                                        <span class="post-date">
                                            <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
                                                <?php echo esc_html( get_the_date() ); ?>
                                            </time>
                                        </span>
                                        
                                        <span class="post-author">
                                            <?php esc_html_e( 'by', 'awps' ); ?> 
                                            <a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>" 
                                            class="author-link"
                                            rel="author">
                                                <?php echo esc_html( get_the_author() ); ?>
                                            </a>
                                        </span>
                                    </div>

                                    <!-- Post Title -->
                                    <h3 class="post-title">
                                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                    </h3>
                                    
                                    <!-- Post Excerpt -->
                                    <?php if ($atts['show_excerpt'] === 'true') : ?>
                                        <div class="post-excerpt">
                                            <?php echo wp_trim_words(get_the_excerpt(), 20, '...'); ?>
                                        </div>
                                    <?php endif; ?>

                                    
                                </div>
                            </article>
                        </li>
                    <?php endwhile; ?>
                </ul>
            </div>
            <?php
            // Reset Post Data
            wp_reset_postdata();
        } else {
            echo '<p>No blog posts found.</p>';
        }

        return ob_get_clean();
    }
    
}