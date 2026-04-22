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
	public const STICKY_START_META_KEY     = '_rv_sticky_post_types_start';
	public const STICKY_UNTIL_META_KEY     = '_rv_sticky_post_types_until';
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
		add_filter( 'query_loop_block_query_vars', [ $this, 'enable_sticky_for_query_loop_block' ], 10, 2 );
		add_action( 'updated_post_meta', [ $this, 'maybe_clear_sticky_cache_on_meta_change' ], 10, 4 );
		add_action( 'added_post_meta', [ $this, 'maybe_clear_sticky_cache_on_meta_change' ], 10, 4 );
		add_action( 'deleted_post_meta', [ $this, 'maybe_clear_sticky_cache_on_meta_change' ], 10, 4 );
		add_action( 'save_post', [ $this, 'maybe_clear_sticky_cache_on_save' ], 10, 2 );
		add_action( 'before_delete_post', [ $this, 'maybe_clear_sticky_cache_on_delete' ] );
		add_action( 'pre_get_posts', [ $this, 'maybe_remove_posts_from_query' ] );

		add_filter( 'the_posts', [ $this, 'maybe_prepend_sticky_posts' ], 10, 2 );
		add_filter( 'is_sticky', [ $this, 'evaluate_sticky_status' ] );
		add_filter( 'display_post_states', [ $this, 'maybe_add_sticky_post_state_labels' ], 10, 2 );
	}

	/**
	 * Get the sticky-enabled post type for the current query.
	 *
	 * @param WP_Query $query The current query.
	 *
	 * @return string|null Supported post type or null.
	 */
	private static function get_sticky_query_post_type( WP_Query $query ): ?string {
		if ( false === $query->get( 'sticky_post_types', true ) ) {
			return null;
		}

		if ( $query->get( 'ignore_sticky_posts' ) ) {
			return null;
		}

		$is_rest_request = ( function_exists( 'wp_is_serving_rest_request' ) && wp_is_serving_rest_request() ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST );
		$is_query_loop   = (bool) $query->get( 'sticky_post_types_query_loop', false );

		if ( ! $is_rest_request && ! $is_query_loop && ( is_admin() || ! $query->is_main_query() ) ) {
			return null;
		}

		$post_types = $query->get( 'post_type' );

		if ( empty( $post_types ) ) {
			return null;
		}

		$post_types        = array_values( (array) $post_types );
		$sticky_post_types = Helpers::get_sticky_post_types();

		$matching_types = array_values(
			array_intersect( $post_types, $sticky_post_types )
		);

		// Only support single post type queries (for now).
		if ( 1 !== count( $matching_types ) ) {
			return null;
		}

		return $matching_types[0];
	}

	/**
	 * Enable sticky post type handling for Query Loop block queries.
	 *
	 * @param array $query Query vars for the block query.
	 * @param mixed $block Parsed block context.
	 *
	 * @return array
	 */
	public function enable_sticky_for_query_loop_block( array $query, $block ): array {
		unset( $block );

		$query['sticky_post_types']            = true;
		$query['sticky_post_types_query_loop'] = true;

		return $query;
	}

	/**
	 * Register the sticky post meta.
	 */
	public function register_meta() {
		$base_meta_args = [
			'single'        => true,
			'show_in_rest'  => true,
			'auth_callback' => function ( $allowed, $meta_key, $post_id ) {
				if ( ! $post_id ) {
					return false;
				}

				return current_user_can( 'edit_post', $post_id );
			},
		];

		foreach ( Helpers::get_sticky_post_types() as $post_type ) {
			register_post_meta(
				$post_type,
				self::STICKY_META_KEY,
				[
					...$base_meta_args,
					'type'              => 'boolean',
					'sanitize_callback' => function ( $value ) {
						return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
					},
				]
			);

			register_post_meta(
				$post_type,
				self::STICKY_START_META_KEY,
				[
					...$base_meta_args,
					'type'              => 'integer',
					'sanitize_callback' => function ( $value ) {
						return absint( $value );
					},
				]
			);

			register_post_meta(
				$post_type,
				self::STICKY_UNTIL_META_KEY,
				[
					...$base_meta_args,
					'type'              => 'integer',
					'sanitize_callback' => function ( $value ) {
						return absint( $value );
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
	 * Maybe clear sticky cache when sticky meta changes.
	 *
	 * @param int    $meta_id    Meta ID.
	 * @param int    $post_id    Post ID.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value Meta value.
	 *
	 * @return void
	 */
	public function maybe_clear_sticky_cache_on_meta_change( $meta_id, $post_id, $meta_key, $meta_value ): void {
		if (
			self::STICKY_META_KEY !== $meta_key &&
			self::STICKY_START_META_KEY !== $meta_key &&
			self::STICKY_UNTIL_META_KEY !== $meta_key
		) {
			return;
		}

		$post_type = get_post_type( $post_id );

		if ( ! $post_type ) {
			return;
		}

		Helpers::delete_sticky_posts_cache_by_type( $post_type );
	}

	/**
	 * Maybe clear sticky cache when a post is saved.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 *
	 * @return void
	 */
	public function maybe_clear_sticky_cache_on_save( $post_id, $post ): void {
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( empty( $post->post_type ) ) {
			return;
		}

		Helpers::delete_sticky_posts_cache_by_type( $post->post_type );
	}

	/**
	 * Maybe clear sticky cache when a post is deleted.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	public function maybe_clear_sticky_cache_on_delete( $post_id ): void {
		$post_type = get_post_type( $post_id );

		if ( empty( $post_type ) ) {
			return;
		}

		Helpers::delete_sticky_posts_cache_by_type( $post_type );
	}

	/**
	 * Conditionally remove sticky posts from the query.
	 *
	 * @param WP_Query $query The current query.
	 */
	public function maybe_remove_posts_from_query( $query ) {
		$post_type = self::get_sticky_query_post_type( $query );

		if ( ! $post_type ) {
			return;
		}

		$sticky_ids = Helpers::get_sticky_posts_by_type( $post_type );

		if ( empty( $sticky_ids ) ) {
			return;
		}

		$post__not_in = $query->get( 'post__not_in', [] );
		$post__not_in = array_map( 'absint', (array) $post__not_in );
		$sticky_ids   = array_map( 'absint', $sticky_ids );

		$query->set(
			'post__not_in',
			array_values( array_unique( array_merge( $post__not_in, $sticky_ids ) ) )
		);
	}

	/**
	 * Conditionally add sticky posts to the front of the query.
	 *
	 * @param WP_Post  $posts Array of post objects.
	 * @param WP_Query $query The current query.
	 *
	 * @return WP_Post|array The original or merged posts array.
	 */
	public function maybe_prepend_sticky_posts( $posts, $query ) {
		$post_type = self::get_sticky_query_post_type( $query );

		if ( ! $post_type ) {
			return $posts;
		}

		if ( $query->get( 'paged' ) > 1 ) {
			return $posts;
		}

		$sticky_ids = Helpers::get_sticky_posts_by_type( $post_type );

		if ( empty( $sticky_ids ) ) {
			return $posts;
		}

		$sticky_posts = get_posts(
			[
				'post__in'         => $sticky_ids,
				'post_type'        => $post_type,
				'orderby'          => 'post__in',
				'posts_per_page'   => count( $sticky_ids ),
				'suppress_filters' => false, // phpcs:ignore
			]
		);

		$merged_posts = array_merge( $sticky_posts, $posts );

		// Keep the original query page size after prepending sticky posts.
		if ( count( $posts ) > 0 ) {
			return array_slice( $merged_posts, 0, count( $posts ) );
		}

		return $merged_posts;
	}

	/**
	 * Get sticky window status for a post.
	 *
	 * @param int $sticky_start Sticky start timestamp.
	 * @param int $sticky_until Sticky until timestamp.
	 *
	 * @return string One of active, scheduled, or expired.
	 */
	private static function get_sticky_window_status( int $sticky_start, int $sticky_until ): string {
		$current_time = time();

		if ( $sticky_start > 0 && $sticky_start > $current_time ) {
			return 'scheduled';
		}

		if ( $sticky_until > 0 && $sticky_until <= $current_time ) {
			return 'expired';
		}

		return 'active';
	}

	/**
	 * Add sticky schedule state labels to admin post list rows.
	 *
	 * @param array   $post_states Existing post state labels.
	 * @param WP_Post $post        Current post object.
	 *
	 * @return array
	 */
	public function maybe_add_sticky_post_state_labels( array $post_states, WP_Post $post ): array {
		$sticky_post_types = Helpers::get_sticky_post_types();

		if ( ! in_array( $post->post_type, $sticky_post_types, true ) ) {
			return $post_states;
		}

		$is_sticky = (bool) get_post_meta( $post->ID, self::STICKY_META_KEY, true );

		if ( ! $is_sticky ) {
			return $post_states;
		}

		$sticky_start = absint( get_post_meta( $post->ID, self::STICKY_START_META_KEY, true ) );
		$sticky_until = absint( get_post_meta( $post->ID, self::STICKY_UNTIL_META_KEY, true ) );

		if ( $sticky_start > 0 && 'scheduled' === self::get_sticky_window_status( $sticky_start, $sticky_until ) ) {
			$post_states['sticky-post-types-scheduled'] = __( 'Scheduled', 'sticky-post-types' );
		}

		if ( $sticky_until > 0 && 'expired' === self::get_sticky_window_status( $sticky_start, $sticky_until ) ) {
			$post_states['sticky-post-types-expired'] = __( 'Expired', 'sticky-post-types' );
		}

		return $post_states;
	}

	/**
	 * Determine if a post should be considered sticky, adds "- Sticky" flag in
	 * the admin and resolves boolean for frontend templates.
	 *
	 * @param int $post_id The current post's ID.
	 *
	 * @return bool Whether the current post is sticky.
	 */
	public function evaluate_sticky_status( int $post_id ): bool {
		$post_id = absint( $post_id );

		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		$sticky_post_types = Helpers::get_sticky_post_types();
		$post_type         = get_post_type( $post_id );

		if ( ! in_array( $post_type, $sticky_post_types, true ) ) {
			return false;
		}

		$is_sticky = (bool) get_post_meta( $post_id, self::STICKY_META_KEY, true );

		if ( ! $is_sticky ) {
			return false;
		}

		$sticky_start = absint( get_post_meta( $post_id, self::STICKY_START_META_KEY, true ) );
		$sticky_until = absint( get_post_meta( $post_id, self::STICKY_UNTIL_META_KEY, true ) );

		return 'active' === self::get_sticky_window_status( $sticky_start, $sticky_until );
	}
}
