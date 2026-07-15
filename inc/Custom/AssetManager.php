<?php

namespace Awps\Custom;

/**
 * Manages enqueuing, dequeuing, and cleaning up of frontend assets.
 * Optimizes performance by removing unnecessary scripts, styles, and HTML tags.
 */
class AssetManager
{
    /**
     * Register all hooks and actions.
     *
     * @return void
     */
    public function register()
    {
        // Enqueue custom assets (e.g., Cropper.js)
        add_action('wp_enqueue_scripts', [$this, 'enqueue_custom_assets']);

        // Remove WordPress emoji
        add_action('init', [$this, 'disable_wp_emoji']);

        // Remove block library & global styles
        add_action('wp_enqueue_scripts', [$this, 'remove_block_library_css'], 100);

        // Remove speculation rules
        add_filter('wp_speculation_rules', '__return_empty_array');
        add_action('template_redirect', [$this, 'start_speculation_rules_removal_buffer']);

        // Final HTML cleanup
        add_action('template_redirect', [$this, 'start_html_cleanup_buffer']);

        // Remove meta tags and links
        add_action('after_setup_theme', [$this, 'remove_unwanted_head_tags']);
    }

    /**
     * Enqueue custom frontend assets.
     */
    public function enqueue_custom_assets()
    {
        wp_enqueue_style('cropper-css', get_template_directory_uri() . '/assets/dist/css/cropper.min.css');
        wp_enqueue_script('cropper-js', get_template_directory_uri() . '/assets/dist/js/cropper.min.js', [], null, true);
    }

    


    /**
     * Disable WordPress emoji scripts and styles.
     */
    public function disable_wp_emoji()
    {
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_filter('the_content_feed', 'wp_staticize_emoji');
        remove_filter('comment_text_rss', 'wp_staticize_emoji');
        remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
        add_filter('emoji_svg_url', '__return_false');
    }

    /**
     * Remove WordPress block library and global styles.
     */
    public function remove_block_library_css()
    {
        wp_dequeue_style('wp-block-library');
        wp_dequeue_style('wp-block-library-theme');
        wp_dequeue_style('global-styles');
        wp_dequeue_style('classic-theme-styles');
    }



    /**
     * Buffer output to remove <script type="speculationrules">.
     */
    public function start_speculation_rules_removal_buffer()
    {
        if (!is_admin() && !wp_doing_ajax() && !wp_is_json_request()) {
            ob_start(function ($buffer) {
                return preg_replace('#<script type="speculationrules">.*?</script>#s', '', $buffer);
            });
        }
    }

    /**
     * Final HTML cleanup: remove empty Customizer CSS  noscript.
     */
    public function start_html_cleanup_buffer()
    {
        if (!is_admin() && !wp_doing_ajax()) {
            ob_start(function ($html) {
                // Remove empty Customizer CSS blocks
                $html = preg_replace('#<!--Customizer CSS-->.*?<style[^>]*>\s*</style>.*?<!--/Customizer CSS-->#s', '', $html);
                return $html;
            });
        }
    }

    /**
     * Remove unwanted meta tags and links from <head>.
     */
    public function remove_unwanted_head_tags()
    {
        // Remove generator
        remove_action('wp_head', 'wp_generator');

        // Remove RSS feeds
        remove_action('wp_head', 'feed_links', 2);
        remove_action('wp_head', 'feed_links_extra', 3);

        // Remove RSD / XML-RPC
        remove_action('wp_head', 'rsd_link');

        // Remove shortlink
        remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0);
        remove_action('template_redirect', 'wp_shortlink_header', 11, 0);

        // Remove oEmbed discovery
        remove_action('wp_head', 'wp_oembed_add_discovery_links');
    }
}