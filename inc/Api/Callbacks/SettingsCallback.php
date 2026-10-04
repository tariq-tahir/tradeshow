<?php
/**
 * Callbacks for Settings API
 *
 * @package awps
 */

namespace Awps\Api\Callbacks;

/**
 * Settings API Callbacks Class
 */
class SettingsCallback
{
	/**
	 * Admin index.
	 */
	public function admin_index() 
	{
		return require_once( get_template_directory() . '/views/admin/index.php' );
	}

	/**
	 * Admin faq.
	 */
	public function admin_faq() 
	{
		echo '<div class="wrap"><h1>' . esc_html__( 'FAQ Page', 'awps' ) . '</h1></div>';
	}

	/**
	 * Awps options group.
	 *
	 * @param string $input The input.
	 */
	public function awps_options_group( $input ) 
	{
		return $input;
	}

	/**
	 * Awps admin index.
	 */
	public function awps_admin_index() 
	{
		echo esc_html__( 'Customize this Theme Settings section and add description and instructions', 'awps' );
	}

	/**
	 * First name.
	 */
	public function first_name()
	{
		$first_name = esc_attr( get_option( 'first_name' ) );
		$placeholder = esc_attr__( 'First Name', 'awps' );
                echo '<input id="first_name" type="text" class="regular-text" name="first_name" value="' . $first_name . '" placeholder="' . $placeholder . '" />';
	}
}