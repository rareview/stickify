<?php
/**
 * RareviewScheduledStickyPosts service provider.
 *
 * @author Rareview® <hello@rareview.com>
 *
 * @package RareviewScheduledStickyPosts
 */

namespace RareviewScheduledStickyPosts\Inc;

/**
 *
 * Plugin service provider.
 */
class RareviewScheduledStickyPostsServiceProvider {

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
