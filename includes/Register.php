<?php
/**
 * Register class.
 *
 * @author Rareview <hello@rareview.com>
 *
 * @package Stickify
 */

namespace Stickify\Inc;

use Stickify\Inc\Helpers;
use WP_Post;
use WP_Query;

/**
 * Class Register
 */
class Register {

	public const PREFIX                    = 'stickify';
	public const STICKIFY_META_KEY         = '_' . self::PREFIX . '_sticky';
	public const STICKIFY_START_META_KEY   = '_' . self::PREFIX . '_sticky_start';
	public const STICKIFY_UNTIL_META_KEY   = '_' . self::PREFIX . '_sticky_until';
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
		add_action( 'updated_post_meta', [ $this, 'maybe_clear_stickify_cache_on_meta_change' ], 10, 4 );
		add_action( 'added_post_meta', [ $this, 'maybe_clear_stickify_cache_on_meta_change' ], 10, 4 );
		add_action( 'deleted_post_meta', [ $this, 'maybe_clear_stickify_cache_on_meta_change' ], 10, 4 );
		add_action( 'save_post', [ $this, 'maybe_clear_stickify_cache_on_save' ], 10, 2 );
		add_action( 'before_delete_post', [ $this, 'maybe_clear_stickify_cache_on_delete' ] );
		add_action( 'pre_get_posts', [ $this, 'maybe_remove_posts_from_query' ] );

		add_filter( 'plugin_action_links_' . plugin_basename( dirname( __DIR__ ) . '/stickify.php' ), [ $this, 'add_settings_link_to_plugin_actions' ] );
		add_filter( 'query_loop_block_query_vars', [ $this, 'enable_stickify_for_query_loop_block' ], 10, 2 );
		add_filter( 'the_posts', [ $this, 'maybe_prepend_stickify_posts' ], 10, 2 );
		add_filter( 'is_sticky', [ $this, 'evaluate_stickify_status' ], 10, 2 );
		add_filter( 'display_post_states', [ $this, 'maybe_add_stickify_post_state_labels' ], 10, 2 );
	}

	/**
	 * Get the sticky-enabled post type for the current query.
	 *
	 * @param WP_Query $query The current query.
	 *
	 * @return string|null Supported post type or null.
	 */
	private static function get_stickify_query_post_type( WP_Query $query ): ?string {
		if ( false === $query->get( 'stickify_post_types', true ) ) {
			return null;
		}

		if ( $query->get( 'ignore_stickify_posts' ) ) {
			return null;
		}

		$is_rest_request = ( function_exists( 'wp_is_serving_rest_request' ) && wp_is_serving_rest_request() ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST );
		$is_query_loop   = (bool) $query->get( 'stickify_post_types_query_loop', false );

		if ( ! $is_rest_request && ! $is_query_loop && ( is_admin() || ! $query->is_main_query() ) ) {
			return null;
		}

		$post_types = $query->get( 'post_type' );

		if ( empty( $post_types ) ) {
			return null;
		}

		$post_types          = array_values( (array) $post_types );
		$stickify_post_types = Helpers::get_stickify_post_types();

		$matching_types = array_values(
			array_intersect( $post_types, $stickify_post_types )
		);

		// Only support single post type queries (for now).
		if ( 1 !== count( $matching_types ) ) {
			return null;
		}

		return $matching_types[0];
	}

	/**
	 * Add settings link to plugin actions on the plugins page.
	 *
	 * @param array $actions Existing plugin action links.
	 *
	 * @return array Modified plugin action links.
	 */
	public function add_settings_link_to_plugin_actions( array $actions ): array {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'options-general.php?page=stickify' ) ),
			__( 'Settings', 'stickify' )
		);

		return array_merge( [ 'settings' => $settings_link ], $actions );
	}

	/**
	 * Enable sticky post type handling for Query Loop block queries.
	 *
	 * @param array $query Query vars for the block query.
	 * @param mixed $block Parsed block context.
	 *
	 * @return array
	 */
	public function enable_stickify_for_query_loop_block( array $query, $block ): array {
		$query['stickify_post_types']            = true;
		$query['stickify_post_types_query_loop'] = true;

		return $query;
	}

	/**
	 * Register the stickify post meta.
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

		foreach ( Helpers::get_stickify_post_types() as $post_type ) {
			register_post_meta(
				$post_type,
				self::STICKIFY_META_KEY,
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
				self::STICKIFY_START_META_KEY,
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
				self::STICKIFY_UNTIL_META_KEY,
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
	 * Maybe clear stickify cache when stickify meta changes.
	 *
	 * @param int    $meta_id    Meta ID.
	 * @param int    $post_id    Post ID.
	 * @param string $meta_key   Meta key.
	 * @param mixed  $meta_value Meta value.
	 *
	 * @return void
	 */
	public function maybe_clear_stickify_cache_on_meta_change( $meta_id, $post_id, $meta_key, $meta_value ): void {
		if (
			self::STICKIFY_META_KEY !== $meta_key &&
			self::STICKIFY_START_META_KEY !== $meta_key &&
			self::STICKIFY_UNTIL_META_KEY !== $meta_key
		) {
			return;
		}

		$post_type = get_post_type( $post_id );

		if ( ! $post_type ) {
			return;
		}

		Helpers::delete_stickify_cache_by_type( $post_type );
	}

	/**
	 * Maybe clear stickify cache when a post is saved.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 *
	 * @return void
	 */
	public function maybe_clear_stickify_cache_on_save( $post_id, $post ): void {
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( empty( $post->post_type ) ) {
			return;
		}

		Helpers::delete_stickify_cache_by_type( $post->post_type );
	}

	/**
	 * Maybe clear stickify cache when a post is deleted.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	public function maybe_clear_stickify_cache_on_delete( $post_id ): void {
		$post_type = get_post_type( $post_id );

		if ( empty( $post_type ) ) {
			return;
		}

		Helpers::delete_stickify_cache_by_type( $post_type );
	}

	/**
	 * Conditionally remove sticky posts from the query.
	 *
	 * @param WP_Query $query The current query.
	 */
	public function maybe_remove_posts_from_query( $query ) {
		$post_type = self::get_stickify_query_post_type( $query );

		if ( ! $post_type ) {
			return;
		}

		$stickify_ids = Helpers::get_stickify_posts_by_type( $post_type );

		if ( empty( $stickify_ids ) ) {
			return;
		}

		$post__not_in = $query->get( 'post__not_in', [] );
		$post__not_in = array_map( 'absint', (array) $post__not_in );
		$stickify_ids   = array_map( 'absint', $stickify_ids );

		$query->set(
			'post__not_in',
			array_values( array_unique( array_merge( $post__not_in, $stickify_ids ) ) )
		);
	}

	/**
	 * Conditionally add stickify posts to the front of the query.
	 *
	 * @param WP_Post  $posts Array of post objects.
	 * @param WP_Query $query The current query.
	 *
	 * @return WP_Post|array The original or merged posts array.
	 */
	public function maybe_prepend_stickify_posts( $posts, $query ) {
		$post_type = self::get_stickify_query_post_type( $query );

		if ( ! $post_type || $query->is_singular() ) {
			return $posts;
		}

		if ( $query->get( 'paged' ) > 1 ) {
			return $posts;
		}

		$stickify_ids = Helpers::get_stickify_posts_by_type( $post_type );

		if ( empty( $stickify_ids ) ) {
			return $posts;
		}

		$stickify_posts = get_posts(
			[
				'post__in'         => $stickify_ids,
				'post_type'        => $post_type,
				'orderby'          => 'post__in',
				'posts_per_page'   => count( $stickify_ids ),
				'stickify_post_types'   => false,
				'ignore_stickify_posts' => true,
				'suppress_filters' => false, // phpcs:ignore
			]
		);

		$merged_posts = array_merge( $stickify_posts, $posts );

		// Keep the original query page size after prepending stickify posts.
		if ( count( $posts ) > 0 ) {
			return array_slice( $merged_posts, 0, count( $posts ) );
		}

		return $merged_posts;
	}

	/**
	 * Get stickify window status for a post.
	 *
	 * @param int $stickify_start Stickify start timestamp.
	 * @param int $stickify_until Stickify until timestamp.
	 *
	 * @return string One of active, upcoming, or expired.
	 */
	private static function get_stickify_window_status( int $stickify_start, int $stickify_until ): string {
		$current_time = time();

		if ( $stickify_start > 0 && $stickify_start > $current_time ) {
			return 'upcoming';
		}

		if ( $stickify_until > 0 && $stickify_until <= $current_time ) {
			return 'expired';
		}

		return 'active';
	}

	/**
	 * Add stickify schedule state labels to admin post list rows.
	 *
	 * @param array   $post_states Existing post state labels.
	 * @param WP_Post $post        Current post object.
	 *
	 * @return array
	 */
	public function maybe_add_stickify_post_state_labels( array $post_states, WP_Post $post ): array {
		$stickify_post_types = Helpers::get_stickify_post_types();

		if ( ! in_array( $post->post_type, $stickify_post_types, true ) ) {
			return $post_states;
		}

		$is_stickified = (bool) get_post_meta( $post->ID, self::STICKIFY_META_KEY, true );

		if ( ! $is_stickified ) {
			return $post_states;
		}

		$stickify_start = absint( get_post_meta( $post->ID, self::STICKIFY_START_META_KEY, true ) );
		$stickify_until = absint( get_post_meta( $post->ID, self::STICKIFY_UNTIL_META_KEY, true ) );

		if ( $stickify_start > 0 && 'upcoming' === self::get_stickify_window_status( $stickify_start, $stickify_until ) ) {
			$post_states['stickify-upcoming'] = __( 'Sticky (Upcoming)', 'stickify' );
		}

		if ( $stickify_until > 0 && 'expired' === self::get_stickify_window_status( $stickify_start, $stickify_until ) ) {
			$post_states['stickify-expired'] = __( 'Sticky (Expired)', 'stickify' );
		}

		return $post_states;
	}

	/**
	 * Determine if a post should be considered sticky, adds "- Sticky" flag in
	 * the admin and resolves boolean for frontend templates.
	 *
	 * @param bool $is_sticky The current sticky status determined by previous filters.
	 * @param int  $post_id The current post's ID.
	 *
	 * @return bool Whether the current post is sticky.
	 */
	public function evaluate_stickify_status( bool $is_sticky, int $post_id ): bool {
		$post_id = absint( $post_id );

		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		$stickify_post_types = Helpers::get_stickify_post_types();
		$post_type         = get_post_type( $post_id );

		if ( ! in_array( $post_type, $stickify_post_types, true ) ) {
			return $is_sticky;
		}

		$is_custom_sticky = (bool) get_post_meta( $post_id, self::STICKIFY_META_KEY, true );

		if ( 'post' === $post_type && ! $is_custom_sticky ) {
			return $is_sticky;
		}

		if ( ! $is_custom_sticky ) {
			return false;
		}

		$stickify_start = absint( get_post_meta( $post_id, self::STICKIFY_START_META_KEY, true ) );
		$stickify_until = absint( get_post_meta( $post_id, self::STICKIFY_UNTIL_META_KEY, true ) );

		return 'active' === self::get_stickify_window_status( $stickify_start, $stickify_until );
	}
}
