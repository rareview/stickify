<?php
/**
 * REST class.
 *
 * @author Rareview <hello@rareview.com>
 *
 * @package Sticky CPTs
 */

namespace StickyCPTs\Inc;

use StickyCPTs\Inc\Register;
use StickyCPTs\Inc\Helpers;

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
        add_action( 'rest_api_init', [ $this, 'sticky_cpts_endpoint' ] );
    }

    /**
	 * Register the feed posts endpoint.
	 *
	 * @return void
	 */
	public function sticky_cpts_endpoint() {
		register_rest_route(
			Register::REST_API_CUSTOM_NAMESPACE,
			'/post-types',
			[
				'methods'             => 'GET',
				'callback'            => function () {
					$post_types = Helpers::get_sticky_cpts_types();

					return $post_types;
				},
				'permission_callback' => '__return_true',
			]
		);
	}
}
