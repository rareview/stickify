<?php
/**
 * REST class.
 *
 * @author Rareview <hello@rareview.com>
 *
 * @package StickyPostTypes
 */

namespace StickyPostTypes\Inc;

use StickyPostTypes\Inc\Register;
use StickyPostTypes\Inc\Helpers;

/**
 * Class Rest
 */
class Rest {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_rest_endpoints();
	}

	/**
	 * Register editor assets.
	 *
	 * @return void
	 */
	public function register_rest_endpoints() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			Register::REST_API_CUSTOM_NAMESPACE,
			'/post-types',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_sticky_post_types' ],
				'permission_callback' => '__return_true',
			]
		);

		register_rest_route(
			Register::REST_API_CUSTOM_NAMESPACE,
			'/cache/clear',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'clear_sticky_caches' ],
				'permission_callback' => [ $this, 'can_manage_sticky_settings' ],
			]
		);
	}

	/**
	 * Return enabled sticky post types.
	 *
	 * @return array
	 */
	public function get_sticky_post_types() {
		return Helpers::get_sticky_post_types();
	}

	/**
	 * Clear sticky post caches for all enabled post types.
	 *
	 * @return array<string, int>
	 */
	public function clear_sticky_caches(): array {
		$cleared_count = 0;

		foreach ( Helpers::get_sticky_post_types() as $post_type ) {
			Helpers::delete_sticky_posts_cache_by_type( $post_type );
			++$cleared_count;
		}

		return [
			'cleared' => $cleared_count,
		];
	}

	/**
	 * Check whether the current user can manage plugin settings.
	 *
	 * @return bool
	 */
	public function can_manage_sticky_settings(): bool {
		return current_user_can( 'manage_options' );
	}
}
