<?php

namespace Awps\Custom;

/**
 * Handles media library restrictions, image upload processing,
 * and attachment utilities.
 */
class MediaAndUploads
{
    /**
     * Register all hooks and actions.
     *
     * @return void
     */
    public function register()
    {
        // Restrict media library to current user's uploads (frontend only)
        add_filter('ajax_query_attachments_args', [$this, 'restrict_media_library_to_user']);

        // Automatically scale down large image uploads
        add_filter('big_image_size_threshold', [$this, 'set_max_image_size']);
    }

    /**
     * Restrict media library AJAX queries to current user's uploads (on frontend).
     *
     * @param array $query
     * @return array
     */
    public function restrict_media_library_to_user($query)
    {
        if (!is_admin() && is_user_logged_in()) {
            $user_id = get_current_user_id();
            if ($user_id) {
                // Ensure only current user's media is shown
                $query['author'] = $user_id;
            }
        }
        return $query;
    }

    /**
     * Set maximum image size threshold to auto-scale large uploads.
     *
     * @param int $threshold
     * @return int
     */
    public function set_max_image_size($threshold)
    {
        return 1200; // Max width/height in pixels
    }

    /**
     * Utility: Get attachment ID from image URL.
     *
     * ⚠️ Note: This method is not hooked; it's a helper you can call statically
     * if needed elsewhere (e.g., in importers or custom logic).
     *
     * @param string $url
     * @return int|false
     */
    public static function url_to_attachment_id($url)
    {
        global $wpdb;
        $url = esc_url_raw($url);
        if (!$url) {
            return false;
        }
        return $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE guid = %s",
            $url
        ));
    }
}