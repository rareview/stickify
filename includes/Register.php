<?php
/**
 * Register class.
 *
 * @author Rareview <hello@rareview.com>
 *
 * @package StickyPostTypes
 */

namespace StickyPostTypes\Inc;

use StickyPostTypes\Inc\Helpers;
use WP_Post;
use WP_Query;

/**
 * Class Register
 */
class Register {

	public const PREFIX                    = 'sticky-post-types';
	public const STICKY_META_KEY           = '_rv_sticky_post_types';
	public const REST_API_CUSTOM_NAMESPACE = self::PREFIX . '/v1';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_editor_assets();
	}

	/**
	 * Register editor assets.
	 *
	 * @return void
	 */
	public function register_editor_assets() {
		add_action( 'init', [ $this, 'register_meta' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
		add_action( 'pre_get_posts', [ $this, 'maybe_remove_posts_from_query' ] );
		add_filter( 'the_posts', [ $this, 'maybe_prepend_sticky_posts' ], 10, 2 );
		add_filter( 'is_sticky', [ $this, 'evauluate_sticky_status' ] );
	}

	/**
	 * Register the sticky post meta.
	 */
	public function register_meta() {
		foreach ( Helpers::get_sticky_post_types_types() as $post_type ) {
			register_meta(
				'post',
				'_rv_sticky_post_types',
				[
					'object_subtype'    => $post_type,
					'type'              => 'boolean',
					'single'            => true,
					'show_in_rest'      => true,
					'auth_callback'     => function () {
						return current_user_can( 'edit_posts' );
					},
					'default'           => false,
					'sanitize_callback' => function ( $value ) {
						return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
					},
				]
			);
		}
	}

	/**
	 * Enqueue editor assets.
	 *
	 * @return void
	 */
	public function enqueue_editor_assets() {
		wp_enqueue_script( self::PREFIX . '-editor-script', Helpers::asset_url( 'editor.js' ), [ 'wp-blocks', 'wp-dom-ready', 'wp-edit-post' ], Helpers::version(), true );
	}

	/**
	 * Conditionally remove sticky posts from the query.
	 *
	 * @param WP_Query $query The current query.
	 */
	public function maybe_remove_posts_from_query( $query ) {
		$sticky_post_types = Helpers::get_sticky_post_types_types();

		if (
			is_admin() ||
			! $query->is_main_query() ||
			! in_array( $query->get( 'post_type' ), $sticky_post_types, true )
		) {
			return;
		}

		$sticky_ids = Helpers::get_sticky_posts_by_type( $query->get( 'post_type' ) );

		if ( empty( $sticky_ids ) ) {
			return;
		}

		$query->set( 'post__not_in', $sticky_ids );
	}

	/**
	 * Coditionally add sticky posts to the front of the query.
	 *
	 * @param WP_Post  $posts Array of post objects.
	 * @param WP_Query $query The current query.
	 *
	 * @return WP_Post|array The original or merged posts array.
	 */
	public function maybe_prepend_sticky_posts( $posts, $query ) {
		$sticky_post_types = Helpers::get_sticky_post_types_types();

		if (
			is_admin() ||
			! $query->is_main_query() ||
			! in_array( $query->get( 'post_type' ), $sticky_post_types, true ) ||
			$query->get( 'paged' ) > 1
		) {
			return $posts;
		}

		$sticky_ids = Helpers::get_sticky_posts_by_type( $query->get( 'post_type' ) );

		if ( empty( $sticky_ids ) ) {
			return $posts;
		}

		$sticky_posts = get_posts(
			[
				'post__in'         => $sticky_ids,
				'post_type'        => $query->get( 'post_type' ),
				'orderby'          => 'post__in',
				'posts_per_page'   => count( $sticky_ids ),
				'suppress_filters' => false, // phpcs:ignore
			]
		);

		return array_merge( $sticky_posts, $posts );
	}

	/**
	 * Determine if a post should be considered sticky, adds "- Sticky" flag in
	 * the admin and resolves boolean for frontend templates.
	 *
	 * @param int $post_id The current post's ID.
	 *
	 * @return bool Whether the current post is sticky.
	 */
	public function evauluate_sticky_status( int $post_id ): bool {
		$post_id = absint( $post_id );

		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		$sticky_post_types = Helpers::get_sticky_post_types_types();
		$post_type         = get_post_type( $post_id );

		if ( ! in_array( $post_type, $sticky_post_types, true ) ) {
			return false;
		}

		return (bool) get_post_meta( $post_id, self::STICKY_META_KEY, true );
	}
}
