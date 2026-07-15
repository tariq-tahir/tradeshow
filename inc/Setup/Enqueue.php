<?php

namespace Awps\Setup;

class Enqueue 
{
    public function register() 
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function enqueue_scripts() 
    {

        

        // CSS
		wp_enqueue_style('main', mix('css/style.css'), [], '1.0.0', 'all');

		// 🔹 1. Enqueue Cropper.js FIRST (custom handle)
		if (strpos($_SERVER['REQUEST_URI'], '/dashboard/') !== false) {
            
			wp_enqueue_script('cropper-js', 'https://cdn.jsdelivr.net/npm/cropperjs@1.6.1/dist/cropper.min.js', [], '1.6.1', true);
			wp_enqueue_style('cropper-css', 'https://cdn.jsdelivr.net/npm/cropperjs@1.6.1/dist/cropper.min.css');

			wp_enqueue_style('wp-jquery-ui-dialog'); // Includes spinner styles
		}

		// 🔹 2. Enqueue main script WITH dependencies
		$deps = ['jquery'];
		if (strpos($_SERVER['REQUEST_URI'], '/dashboard/') !== false) {
			wp_enqueue_media(); // This auto-enqueues media scripts (no handle needed)
			wp_enqueue_script('jquery-ui-autocomplete'); // Handle: 'jquery-ui-autocomplete'
			$deps[] = 'jquery-ui-autocomplete';
			$deps[] = 'cropper-js';

            wp_enqueue_script('main', mix('js/app.js'), $deps, '1.0.0', true);
		}

    	wp_enqueue_script('main', mix('js/app.js'), $deps, '1.0.0', true);

        // BrowserSync (dev only)
        if (getenv('APP_ENV') === 'development') {
            wp_enqueue_script('__bs_script__', getenv('WP_SITEURL') . ':3000/browser-sync/browser-sync-client.js', [], null, true);
        }

        // Comment reply
        if (is_singular() && comments_open() && get_option('thread_comments')) {
            wp_enqueue_script('comment-reply');
        }
    }	
}