<?php
namespace AWPS\Suppliers;

class Routes
{
    public function register()
    {
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('template_redirect', [$this, 'load_templates']);
        add_action('after_switch_theme', 'flush_rewrite_rules');
    }

    public function add_rewrite_rules()
    {


        // Single: /exporter/slug/
        add_rewrite_rule('^supplier/([^/]+)/?$', 'index.php?supplier=$matches[1]', 'top');
    }

    public function add_query_vars($vars)
    {
        $vars[] = 'supplier'; // WordPress will auto-resolve this for CPT
        return $vars;
    }

    public function load_templates()
    {
        // Handle single supplier profile
        if ($slug = get_query_var('supplier')) {
            // WordPress automatically queries the 'supplier' CPT when 'supplier' query var is set
            $posts = get_posts([
                'name'           => $slug,
                'post_type'      => 'supplier',
                'post_status'    => 'publish',
                'numberposts'    => 1,
            ]);

            if (!empty($posts)) {
                $post = $posts[0];
                // Make post available globally (optional)
                $GLOBALS['awps_current_supplier_post'] = $post;
                setup_postdata($post);
                include get_theme_file_path('views/suppliers/profile.php');
                wp_reset_postdata();
                exit;
            } else {
                // No supplier found → 404 or redirect
                wp_redirect(home_url('/suppliers/'));
                exit;
            }
        }

        // Note: The archive (/suppliers/) is handled by WordPress automatically
        // via the default CPT archive template: archive-supplier.php
        // If you want a custom one, create: archive-supplier.php in your theme
    }
}