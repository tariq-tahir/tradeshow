<?php
/**
 * Google Analytics Manager
 *
 * Lightweight GA4 integration via custom code (no plugin).
 * Tracks pageviews, excludes admins, loads asynchronously.
 *
 * @package Awps\Custom
 */

namespace Awps\Custom;

/**
 * Google Analytics Class
 */
class GoogleAnalytics
{
	/**
	 * GA Measurement ID.
	 *
	 * 🔧 To update: just edit this value directly.
	 *
	 * Get your ID from: Google Analytics → Admin → Data Streams → Your Web Stream
	 * Format: G-XXXXXXXXXX (e.g., G-ABC123XYZ)
	 *
	 * @var string
	 */
	const GA_MEASUREMENT_ID = 'G-Z7ZCGVV3NH'; // ← REPLACE WITH YOUR ID

	/**
	 * Register default hooks and actions for WordPress.
	 *
	 * @return void
	 */
	public function register()
	{
		// Only load GA if ID is set and we're not in admin/ajax/cli.
		if ( $this->should_load_analytics() ) {
			add_action( 'wp_head', array( $this, 'output_analytics_script' ), 0 );
		}
	}

	/**
	 * Determine if Google Analytics should be loaded for this request.
	 *
	 * @return bool
	 */
	protected function should_load_analytics()
	{
		// Don't load if GA ID is empty.
		if ( ! $this->get_measurement_id() ) {
			return false;
		}

		// Don't load in admin, ajax, cron, or CLI.
		if ( is_admin() || wp_doing_ajax() || defined( 'DOING_CRON' ) || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return false;
		}

		// Exclude administrators.
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			if ( in_array( 'administrator', (array) $user->roles, true ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get the GA Measurement ID.
	 *
	 * @return string|null
	 */
	protected function get_measurement_id()
	{
		$id = trim( self::GA_MEASUREMENT_ID );

		if ( $id && 'G-XXXXXXXXXX' !== $id ) {
			return $id;
		}

		return null;
	}

	/**
	 * Output the Google Analytics 4 (GA4) script in <head>.
	 *
	 * @return void
	 */
	public function output_analytics_script()
	{
		$measurement_id = $this->get_measurement_id();

		if ( empty( $measurement_id ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'GoogleAnalytics: No Measurement ID configured.' );
			}
			return;
		}

		// Escape the ID for safe output.
		$measurement_id = esc_attr( $measurement_id );
		?>
		<!-- Google Analytics 4 (GA4) - TradeShow -->
		<script async src="<?php echo esc_url( 'https://www.googletagmanager.com/gtag/js?id=' . $measurement_id ); ?>"></script>
		<script>
		window.dataLayer = window.dataLayer || [];
		function gtag(){dataLayer.push(arguments);}
		gtag('js', new Date());
		gtag('config', '<?php echo esc_js( $measurement_id ); ?>', {
			'send_page_view': true,
			'anonymize_ip': true
		});
		</script>
		<!-- End Google Analytics 4 -->
		<?php
	}
}