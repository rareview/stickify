<?php
/**
 * REST class.
 *
 * @author Rareview <hello@rareview.com>
 *
 * @package StickyPostTypes
 */

namespace StickyPostTypes\Inc;

use WP_Post;
use WP_REST_Request;

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

		register_rest_route(
			Register::REST_API_CUSTOM_NAMESPACE,
			'/sticky-posts',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_sticky_posts' ],
				'permission_callback' => [ $this, 'can_manage_sticky_settings' ],
			]
		);

		register_rest_route(
			Register::REST_API_CUSTOM_NAMESPACE,
			'/sticky-posts/clear',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'clear_sticky_posts' ],
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
	 * Return sticky posts grouped by enabled post type.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_sticky_posts( WP_REST_Request $request ): array {
		$enabled_post_types = Helpers::get_sticky_post_types();
		$requested_type     = sanitize_key( (string) $request->get_param( 'post_type' ) );

		if ( ! empty( $requested_type ) ) {
			if ( ! in_array( $requested_type, $enabled_post_types, true ) ) {
				return [];
			}

			$enabled_post_types = [ $requested_type ];
		}

		$response = [];

		foreach ( $enabled_post_types as $post_type ) {
			$post_type_object = get_post_type_object( $post_type );
			$post_ids         = get_posts(
				[
					'post_type'              => $post_type,
					'post_status'            => [ 'publish', 'future', 'draft', 'pending', 'private' ],
					'posts_per_page'         => -1,
					'fields'                 => 'ids',
					'orderby'                => 'date',
					'order'                  => 'DESC',
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
					'meta_query'             => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						[
							'key'     => Register::STICKY_META_KEY,
							'value'   => '1',
							'compare' => '=',
							'type'    => 'NUMERIC',
						],
					],
				]
			);

			$posts = array_map(
				function ( $post_id ) {
					$post = get_post( $post_id );

					if ( ! ( $post instanceof WP_Post ) ) {
						return null;
					}

					$title = get_the_title( $post );

					return [
						'id'          => $post->ID,
						'title'       => ! empty( $title ) ? $title : __( '(no title)', 'sticky-post-types' ),
						'status'      => $post->post_status,
						'editLink'    => get_edit_post_link( $post->ID, '' ),
						'stickyStart' => absint( get_post_meta( $post->ID, Register::STICKY_START_META_KEY, true ) ),
						'stickyUntil' => absint( get_post_meta( $post->ID, Register::STICKY_UNTIL_META_KEY, true ) ),
					];
				},
				$post_ids
			);

			$response[ $post_type ] = [
				'label' => $post_type_object ? $post_type_object->labels->singular_name : $post_type,
				'posts' => array_values( array_filter( $posts ) ),
			];
		}

		return $response;
	}

	/**
	 * Clear sticky behavior for one or more posts.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return array<string, mixed>
	 */
	public function clear_sticky_posts( WP_REST_Request $request ): array {
		$post_ids = array_values(
			array_filter(
				array_map( 'absint', (array) $request->get_param( 'post_ids' ) )
			)
		);

		$enabled_post_types = Helpers::get_sticky_post_types();
		$affected_types     = [];
		$cleared_count      = 0;

		foreach ( $post_ids as $post_id ) {
			$post = get_post( $post_id );

			if ( ! ( $post instanceof WP_Post ) ) {
				continue;
			}

			if ( ! in_array( $post->post_type, $enabled_post_types, true ) ) {
				continue;
			}

			delete_post_meta( $post_id, Register::STICKY_META_KEY );
			delete_post_meta( $post_id, Register::STICKY_START_META_KEY );
			delete_post_meta( $post_id, Register::STICKY_UNTIL_META_KEY );

			$affected_types[ $post->post_type ] = true;
			++$cleared_count;
		}

		foreach ( array_keys( $affected_types ) as $post_type ) {
			Helpers::delete_sticky_posts_cache_by_type( $post_type );
		}

		return [
			'cleared'   => $cleared_count,
			'postTypes' => array_keys( $affected_types ),
		];
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
