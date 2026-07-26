<?php
/**
 *
 * This theme uses PSR-4 and OOP logic instead of procedural coding
 * Every function, hook and action is properly divided and organized inside related folders and files
 * Use the file `config/custom/custom.php` to write your custom functions
 *
 * @package awps
 */

namespace Awps;

final class Init
{
	/**
	 * Store all the classes inside an array
	 * @return array Full list of classes
	 */
	public static function get_services()
	{
		return [
			Core\Tags::class,
			Core\Sidebar::class,
			Setup\Setup::class,
			Setup\Menus::class,
			Setup\Enqueue::class,
			Custom\PostTypes::class,
			Custom\Admin::class,
			Custom\Extras::class,
			Custom\GoogleAnalytics::class,
			Custom\Shortcodes::class,
			Custom\Widgets::class,
			Custom\ProductManagement::class,
			Custom\AssetManager::class,
			Custom\MediaAndUploads::class,
			Custom\SiteTweaks::class,
			Api\Customizer::class,
			Api\Gutenberg::class,
			Api\MobileApi::class,
			Api\Widgets\TextWidget::class,
			Api\Widgets\LatestProductsWidget::class,
			Plugins\ThemeJetpack::class,
			Plugins\Acf::class,
			Suppliers\Account::class,
			Suppliers\CompanyProfile::class,
			Suppliers\Dashboard::class,
			Suppliers\ProfileFields::class,
			Suppliers\Products::class,
			Suppliers\SupplierProfile::class,
			Suppliers\SupplierProducts::class,
			Suppliers\InquiryManager::class,
			Suppliers\SupplierSync::class,
			Suppliers\Roles::class,
			Suppliers\Routes::class,
			Suppliers\Stats::class,
			Products\ProductMeta::class
		];
	}

	/**
	 * Loop through the classes, initialize them, and call the register() method if it exists
	 * @return
	 */
	public static function register_services()
	{
		foreach ( self::get_services() as $class ) {
			$service = self::instantiate( $class );
			if ( method_exists( $service, 'register') ) {
				$service->register();
			}
		}
	}

	/**
	 * Initialize the class
	 * @param  class $class 		class from the services array
	 * @return class instance 		new instance of the class
	 */
	private static function instantiate( $class )
	{
		return new $class();
	}

}
