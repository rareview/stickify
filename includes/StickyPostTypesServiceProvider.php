<?php
/**
 * Sticky Post Types service provider.
 *
 * @author Rareview <hello@rareview.com>
 *
 * @package StickyPostTypes
 */

namespace StickyPostTypes\Inc;

/**
 *
 * Plugin service provider.
 */
class StickyPostTypesServiceProvider {

	/**
	 * The plugin features that should be bootstrapped.
	 *
	 * @var array
	 */
	public static array $services = [
		Register::class,
		Rest::class,
		Settings::class,
	];

	/**
	 * Boot the service provider.
	 *
	 * @return void
	 */
	public function __construct() {
		foreach ( self::$services as $service ) {
			new $service();
		}
	}
}
