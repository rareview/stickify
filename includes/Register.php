<?php
/**
 * Register class.
 *
 * @author Rareview® <hello@rareview.com>
 *
 * @package RareviewScheduledStickyPosts
 */

namespace RareviewScheduledStickyPosts\Inc;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use RareviewScheduledStickyPosts\Inc\Helpers;
use WP_Post;
use WP_Query;

/**
 * Class Register
 */
class Register {

	public const PLUGIN_SLUG     = 'rareview-scheduled-sticky-posts';
	public const ADMIN_PAGE_SLUG = self::PLUGIN_SLUG;
	public const HANDLE_PREFIX   = self::PLUGIN_SLUG;
	public const PREFIX          = 'rareview_scheduled_sticky_posts';

	public const RAREVIEW_SCHEDULED_STICKY_POSTS_META_KEY       = '_' . self::PREFIX . '_sticky';
	public const RAREVIEW_SCHEDULED_STICKY_POSTS_START_META_KEY = '_' . self::PREFIX . '_sticky_start';
	public const RAREVIEW_SCHEDULED_STICKY_POSTS_UNTIL_META_KEY = '_' . self::PREFIX . '_sticky_until';
	public const REST_API_CUSTOM_NAMESPACE                      = self::PLUGIN_SLUG . '/v1';

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
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_quick_edit_assets' ] );
		add_action( 'quick_edit_custom_box', [ $this, 'render_quick_edit_field' ], 10, 2 );
		add_action( 'updated_post_meta', [ $this, 'maybe_clear_rareview_scheduled_sticky_posts_cache_on_meta_change' ], 10, 3 );
		add_action( 'added_post_meta', [ $this, 'maybe_clear_rareview_scheduled_sticky_posts_cache_on_meta_change' ], 10, 3 );
		add_action( 'deleted_post_meta', [ $this, 'maybe_clear_rareview_scheduled_sticky_posts_cache_on_meta_change' ], 10, 3 );
		add_action( 'save_post', [ $this, 'maybe_clear_rareview_scheduled_sticky_posts_cache_on_save' ], 10, 2 );
		add_action( 'save_post', [ $this, 'save_quick_edit_rareview_scheduled_sticky_posts_meta' ], 20, 2 );
		add_action( 'before_delete_post', [ $this, 'maybe_clear_rareview_scheduled_sticky_posts_cache_on_delete' ] );
		add_action( 'pre_get_posts', [ $this, 'maybe_remove_posts_from_query' ] );

		add_filter( 'plugin_action_links_' . plugin_basename( dirname( __DIR__ ) . '/rareview_scheduled_sticky_posts.php' ), [ $this, 'add_settings_link_to_plugin_actions' ] );
		add_filter( 'post_row_actions', [ $this, 'add_quick_edit_row_data' ], 10, 2 );
		add_filter( 'page_row_actions', [ $this, 'add_quick_edit_row_data' ], 10, 2 );
		add_filter( 'query_loop_block_query_vars', [ $this, 'enable_rareview_scheduled_sticky_posts_for_query_loop_block' ] );
		add_filter( 'found_posts', [ $this, 'adjust_rareview_scheduled_sticky_posts_found_posts' ], 10, 2 );
		add_filter( 'the_posts', [ $this, 'maybe_prepend_rareview_scheduled_sticky_posts_posts' ], 10, 2 );
		add_filter( 'is_sticky', [ $this, 'evaluate_rareview_scheduled_sticky_posts_status' ], 10, 2 );
		add_filter( 'display_post_states', [ $this, 'maybe_add_rareview_scheduled_sticky_posts_post_state_labels' ], 10, 2 );
	}

	/**
	 * Get the sticky-enabled post type for the current query.
	 *
	 * @param WP_Query $query The current query.
	 *
	 * @return string|null Supported post type or null.
	 */
	private static function get_rareview_scheduled_sticky_posts_query_post_type( WP_Query $query ): ?string {
		if ( false === $query->get( 'rareview_scheduled_sticky_posts_post_types', true ) ) {
			return null;
		}

		if ( $query->get( 'ignore_rareview_scheduled_sticky_posts_posts' ) ) {
			return null;
		}

		$is_rest_request = ( function_exists( 'wp_is_serving_rest_request' ) && wp_is_serving_rest_request() ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST );
		$is_query_loop   = (bool) $query->get( 'rareview_scheduled_sticky_posts_post_types_query_loop', false );

		if ( ! $is_rest_request && ! $is_query_loop && ( is_admin() || ! $query->is_main_query() ) ) {
			return null;
		}

		$post_types = $query->get( 'post_type' );

		if ( empty( $post_types ) ) {
			return null;
		}

		$post_types                                 = array_values( (array) $post_types );
		$rareview_scheduled_sticky_posts_post_types = Helpers::get_rareview_scheduled_sticky_posts_post_types();

		$matching_types = array_values(
			array_intersect( $post_types, $rareview_scheduled_sticky_posts_post_types )
		);

		// Only support single post type queries (for now).
		if ( 1 !== count( $matching_types ) ) {
			return null;
		}

		return $matching_types[0];
	}

	/**
	 * Get the current page number for a query.
	 *
	 * Some archive contexts populate `page` instead of `paged`, so we normalize
	 * here before applying sticky pagination compensation.
	 *
	 * @param WP_Query $query The current query.
	 *
	 * @return int
	 */
	private static function get_query_page_number( WP_Query $query ): int {
		$paged = max(
			1,
			(int) $query->get( 'paged' ),
			(int) $query->get( 'page' )
		);

		return $paged;
	}

	/**
	 * Get the effective page size for a query.
	 *
	 * Front-end archive queries do not always expose a normalized posts_per_page
	 * value yet at the point where pre_get_posts runs, so we fall back to the
	 * relevant WordPress settings.
	 *
	 * @param WP_Query $query The current query.
	 *
	 * @return int
	 */
	private static function get_query_posts_per_page( WP_Query $query ): int {
		$posts_per_page = (int) $query->get( 'posts_per_page' );

		if ( $posts_per_page > 0 ) {
			return $posts_per_page;
		}

		$posts_per_archive_page = (int) $query->get( 'posts_per_archive_page' );

		if ( $posts_per_archive_page > 0 ) {
			return $posts_per_archive_page;
		}

		$showposts = (int) $query->get( 'showposts' );

		if ( $showposts > 0 ) {
			return $showposts;
		}

		return max( 1, (int) get_option( 'posts_per_page', 10 ) );
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
			esc_url( admin_url( 'options-general.php?page=' . self::ADMIN_PAGE_SLUG ) ),
			__( 'Settings', 'rareview-scheduled-sticky-posts' )
		);

		return array_merge( [ 'settings' => $settings_link ], $actions );
	}

	/**
	 * Enable sticky post type handling for Query Loop block queries.
	 *
	 * @param array $query Query vars for the block query.
	 *
	 * @return array
	 */
	public function enable_rareview_scheduled_sticky_posts_for_query_loop_block( array $query ): array {
		$query['rareview_scheduled_sticky_posts_post_types']            = true;
		$query['rareview_scheduled_sticky_posts_post_types_query_loop'] = true;

		return $query;
	}

	/**
	 * Register the rareview_scheduled_sticky_posts post meta.
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

		foreach ( Helpers::get_rareview_scheduled_sticky_posts_post_types() as $post_type ) {
			register_post_meta(
				$post_type,
				self::RAREVIEW_SCHEDULED_STICKY_POSTS_META_KEY,
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
				self::RAREVIEW_SCHEDULED_STICKY_POSTS_START_META_KEY,
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
				self::RAREVIEW_SCHEDULED_STICKY_POSTS_UNTIL_META_KEY,
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
		$asset = Helpers::asset_data( 'editor' );
		wp_enqueue_script( self::HANDLE_PREFIX . '-editor-script', Helpers::asset_url( 'editor.js' ), $asset['dependencies'], $asset['version'], true );
	}

	/**
	 * Enqueue assets for post list quick edit integration.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 *
	 * @return void
	 */
	public function enqueue_quick_edit_assets( string $hook_suffix ): void {
		if ( 'edit.php' !== $hook_suffix ) {
			return;
		}

		$screen = get_current_screen();

		if ( ! $screen || empty( $screen->post_type ) ) {
			return;
		}

		if ( ! in_array( $screen->post_type, Helpers::get_rareview_scheduled_sticky_posts_post_types(), true ) ) {
			return;
		}

		$asset = Helpers::asset_data( 'quick-edit' );

		wp_enqueue_script(
			self::PREFIX . '-quick-edit-script',
			Helpers::asset_url( 'quick-edit.js' ),
			$asset['dependencies'],
			$asset['version'],
			true
		);
	}

	/**
	 * Render rareview_scheduled_sticky_posts field in the quick edit panel.
	 *
	 * @param string $column_name The current column name.
	 * @param string $post_type   The current post type.
	 *
	 * @return void
	 */
	public function render_quick_edit_field( string $column_name, string $post_type ): void {
		if ( 'post' === $post_type ) {
			return;
		}

		if ( ! in_array( $post_type, Helpers::get_rareview_scheduled_sticky_posts_post_types(), true ) ) {
			return;
		}

		static $did_render = false;

		if ( $did_render ) {
			return;
		}

		$did_render = true;

		wp_nonce_field( 'rareview_scheduled_sticky_posts_quick_edit', 'rareview_scheduled_sticky_posts_quick_edit_nonce', false );
		?>
		<fieldset class="inline-edit-col-right rareview-scheduled-sticky-posts-inline-edit-col-right">
			<div class="inline-edit-col">
				<input type="hidden" name="rareview_scheduled_sticky_posts_quick_edit_touched" value="0" />
				<label class="alignleft">
					<input type="checkbox" name="rareview_scheduled_sticky_posts_quick_edit" value="1" />
					<span class="checkbox-title"><?php esc_html_e( 'Stick it', 'rareview-scheduled-sticky-posts' ); ?></span>
				</label>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Add hidden quick edit row data for pre-filling the checkbox.
	 *
	 * @param array   $actions Existing row actions.
	 * @param WP_Post $post    Current post object.
	 *
	 * @return array
	 */
	public function add_quick_edit_row_data( array $actions, WP_Post $post ): array {
		if ( 'post' === $post->post_type ) {
			return $actions;
		}

		if ( ! in_array( $post->post_type, Helpers::get_rareview_scheduled_sticky_posts_post_types(), true ) ) {
			return $actions;
		}

		$actions['rareview_scheduled_sticky_posts_quick_edit_data'] = sprintf(
			'<span class="rareview-scheduled-sticky-posts-quick-edit-data" data-rareview-scheduled-sticky-posts="%d" style="display:none;"></span>',
			(bool) get_post_meta( $post->ID, self::RAREVIEW_SCHEDULED_STICKY_POSTS_META_KEY, true ) ? 1 : 0
		);

		return $actions;
	}

	/**
	 * Save rareview_scheduled_sticky_posts meta from quick edit updates.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 *
	 * @return void
	 */
	public function save_quick_edit_rareview_scheduled_sticky_posts_meta( int $post_id, WP_Post $post ): void {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( 'post' === $post->post_type ) {
			return;
		}

		if ( ! in_array( $post->post_type, Helpers::get_rareview_scheduled_sticky_posts_post_types(), true ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$inline_nonce = isset( $_POST['_inline_edit'] ) ? sanitize_text_field( wp_unslash( $_POST['_inline_edit'] ) ) : '';

		if ( ! $inline_nonce || ! wp_verify_nonce( $inline_nonce, 'inlineeditnonce' ) ) {
			return;
		}

		$rareview_scheduled_sticky_posts_nonce = isset( $_POST['rareview_scheduled_sticky_posts_quick_edit_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['rareview_scheduled_sticky_posts_quick_edit_nonce'] ) ) : '';

		if ( ! $rareview_scheduled_sticky_posts_nonce || ! wp_verify_nonce( $rareview_scheduled_sticky_posts_nonce, 'rareview_scheduled_sticky_posts_quick_edit' ) ) {
			return;
		}

		$rareview_scheduled_sticky_posts_touched = isset( $_POST['rareview_scheduled_sticky_posts_quick_edit_touched'] ) ? absint( wp_unslash( $_POST['rareview_scheduled_sticky_posts_quick_edit_touched'] ) ) : 0;

		if ( 1 !== $rareview_scheduled_sticky_posts_touched ) {
			return;
		}

		$is_stickified = isset( $_POST['rareview_scheduled_sticky_posts_quick_edit'] );

		update_post_meta( $post_id, self::RAREVIEW_SCHEDULED_STICKY_POSTS_META_KEY, $is_stickified );

		if ( ! $is_stickified ) {
			delete_post_meta( $post_id, self::RAREVIEW_SCHEDULED_STICKY_POSTS_START_META_KEY );
			delete_post_meta( $post_id, self::RAREVIEW_SCHEDULED_STICKY_POSTS_UNTIL_META_KEY );
		}

		if ( 'post' === $post->post_type ) {
			if ( $is_stickified ) {
				Helpers::add_core_sticky_post( $post_id );
			} else {
				Helpers::remove_core_sticky_post( $post_id );
			}
		}
	}

	/**
	 * Maybe clear rareview_scheduled_sticky_posts cache when rareview_scheduled_sticky_posts meta changes.
	 *
	 * @param int    $meta_id    Meta ID.
	 * @param int    $post_id    Post ID.
	 * @param string $meta_key   Meta key.
	 *
	 * @return void
	 */
	public function maybe_clear_rareview_scheduled_sticky_posts_cache_on_meta_change( $meta_id, $post_id, $meta_key ): void {
		if (
			self::RAREVIEW_SCHEDULED_STICKY_POSTS_META_KEY !== $meta_key &&
			self::RAREVIEW_SCHEDULED_STICKY_POSTS_START_META_KEY !== $meta_key &&
			self::RAREVIEW_SCHEDULED_STICKY_POSTS_UNTIL_META_KEY !== $meta_key
		) {
			return;
		}

		$post_type = get_post_type( $post_id );

		if ( ! $post_type ) {
			return;
		}

		Helpers::delete_rareview_scheduled_sticky_posts_cache_by_type( $post_type );
	}

	/**
	 * Maybe clear rareview_scheduled_sticky_posts cache when a post is saved.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 *
	 * @return void
	 */
	public function maybe_clear_rareview_scheduled_sticky_posts_cache_on_save( $post_id, $post ): void {
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( empty( $post->post_type ) ) {
			return;
		}

		Helpers::delete_rareview_scheduled_sticky_posts_cache_by_type( $post->post_type );
	}

	/**
	 * Maybe clear rareview_scheduled_sticky_posts cache when a post is deleted.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	public function maybe_clear_rareview_scheduled_sticky_posts_cache_on_delete( $post_id ): void {
		$post_type = get_post_type( $post_id );

		if ( empty( $post_type ) ) {
			return;
		}

		Helpers::delete_rareview_scheduled_sticky_posts_cache_by_type( $post_type );
	}

	/**
	 * Conditionally remove sticky posts from the query.
	 *
	 * @param WP_Query $query The current query.
	 */
	public function maybe_remove_posts_from_query( $query ) {
		$post_type = self::get_rareview_scheduled_sticky_posts_query_post_type( $query );

		if ( ! $post_type || $query->is_singular() || $query->is_single() || $query->is_search() ) {
			return;
		}

		$rareview_scheduled_sticky_posts_ids = Helpers::get_rareview_scheduled_sticky_posts_posts_by_type( $post_type );

		if ( empty( $rareview_scheduled_sticky_posts_ids ) ) {
			return;
		}

		$post__not_in                        = $query->get( 'post__not_in', [] );
		$post__not_in                        = array_map( 'absint', (array) $post__not_in );
		$rareview_scheduled_sticky_posts_ids = array_map( 'absint', $rareview_scheduled_sticky_posts_ids );

		$query->set(
			'post__not_in',
			array_values( array_unique( array_merge( $post__not_in, $rareview_scheduled_sticky_posts_ids ) ) )
		);

		$paged = self::get_query_page_number( $query );

		$posts_per_page = self::get_query_posts_per_page( $query );

		if ( $posts_per_page > 0 ) {
			$offset = ( ( $paged - 1 ) * $posts_per_page ) - count( $rareview_scheduled_sticky_posts_ids );

			$query->set( 'offset', max( 0, $offset ) );
		}
	}

	/**
	 * Restore the original found post count after excluding sticky posts from the SQL query.
	 *
	 * @param int      $found_posts Found posts for the current query.
	 * @param WP_Query $query       Current query object.
	 *
	 * @return int
	 */
	public function adjust_rareview_scheduled_sticky_posts_found_posts( $found_posts, $query ) {
		$post_type = self::get_rareview_scheduled_sticky_posts_query_post_type( $query );

		if ( ! $post_type || $query->is_singular() || $query->is_single() || $query->is_search() ) {
			return $found_posts;
		}

		$rareview_scheduled_sticky_posts_ids = Helpers::get_rareview_scheduled_sticky_posts_posts_by_type( $post_type );

		if ( empty( $rareview_scheduled_sticky_posts_ids ) ) {
			return $found_posts;
		}

		return (int) $found_posts + count( $rareview_scheduled_sticky_posts_ids );
	}

	/**
	 * Conditionally add rareview_scheduled_sticky_posts posts to the front of the query.
	 *
	 * @param WP_Post  $posts Array of post objects.
	 * @param WP_Query $query The current query.
	 *
	 * @return WP_Post|array The original or merged posts array.
	 */
	public function maybe_prepend_rareview_scheduled_sticky_posts_posts( $posts, $query ) {
		$post_type = self::get_rareview_scheduled_sticky_posts_query_post_type( $query );

		if ( ! $post_type || $query->is_singular() || $query->is_single() || $query->is_search() ) {
			return $posts;
		}

		$rareview_scheduled_sticky_posts_ids = Helpers::get_rareview_scheduled_sticky_posts_posts_by_type( $post_type );

		if ( empty( $rareview_scheduled_sticky_posts_ids ) ) {
			return $posts;
		}

		$paged          = self::get_query_page_number( $query );
		$posts_per_page = self::get_query_posts_per_page( $query );

		if ( $posts_per_page <= 0 ) {
			return $posts;
		}

		$sticky_start_index                       = ( $paged - 1 ) * $posts_per_page;
		$page_rareview_scheduled_sticky_posts_ids = array_slice( $rareview_scheduled_sticky_posts_ids, $sticky_start_index, $posts_per_page );

		if ( empty( $page_rareview_scheduled_sticky_posts_ids ) ) {
			return $posts;
		}

		$rareview_scheduled_sticky_posts_posts = get_posts(
			[
				'post__in'                                     => $page_rareview_scheduled_sticky_posts_ids,
				'post_type'                                    => $post_type,
				'orderby'                                      => 'post__in',
				'posts_per_page'                               => count( $page_rareview_scheduled_sticky_posts_ids ),
				'rareview_scheduled_sticky_posts_post_types'   => false,
				'ignore_rareview_scheduled_sticky_posts_posts' => true,
				'suppress_filters'                             => false, // phpcs:ignore
			]
		);

		$merged_posts = array_merge( $rareview_scheduled_sticky_posts_posts, $posts );

		// Keep the original query page size after prepending rareview_scheduled_sticky_posts posts.
		if ( count( $merged_posts ) > $posts_per_page ) {
			return array_slice( $merged_posts, 0, $posts_per_page );
		}

		return $merged_posts;
	}

	/**
	 * Get rareview_scheduled_sticky_posts window status for a post.
	 *
	 * @param int $rareview_scheduled_sticky_posts_start RareviewScheduledStickyPosts start timestamp.
	 * @param int $rareview_scheduled_sticky_posts_until RareviewScheduledStickyPosts until timestamp.
	 *
	 * @return string One of active, upcoming, or expired.
	 */
	private static function get_rareview_scheduled_sticky_posts_window_status( int $rareview_scheduled_sticky_posts_start, int $rareview_scheduled_sticky_posts_until ): string {
		$current_time = time();

		if ( $rareview_scheduled_sticky_posts_start > 0 && $rareview_scheduled_sticky_posts_start > $current_time ) {
			return 'upcoming';
		}

		if ( $rareview_scheduled_sticky_posts_until > 0 && $rareview_scheduled_sticky_posts_until <= $current_time ) {
			return 'expired';
		}

		return 'active';
	}

	/**
	 * Add rareview_scheduled_sticky_posts schedule state labels to admin post list rows.
	 *
	 * @param array   $post_states Existing post state labels.
	 * @param WP_Post $post        Current post object.
	 *
	 * @return array
	 */
	public function maybe_add_rareview_scheduled_sticky_posts_post_state_labels( array $post_states, WP_Post $post ): array {
		$rareview_scheduled_sticky_posts_post_types = Helpers::get_rareview_scheduled_sticky_posts_post_types();

		if ( ! in_array( $post->post_type, $rareview_scheduled_sticky_posts_post_types, true ) ) {
			return $post_states;
		}

		$is_stickified = (bool) get_post_meta( $post->ID, self::RAREVIEW_SCHEDULED_STICKY_POSTS_META_KEY, true );

		if ( ! $is_stickified ) {
			return $post_states;
		}

		$rareview_scheduled_sticky_posts_start = absint( get_post_meta( $post->ID, self::RAREVIEW_SCHEDULED_STICKY_POSTS_START_META_KEY, true ) );
		$rareview_scheduled_sticky_posts_until = absint( get_post_meta( $post->ID, self::RAREVIEW_SCHEDULED_STICKY_POSTS_UNTIL_META_KEY, true ) );

		if ( $rareview_scheduled_sticky_posts_start > 0 && 'upcoming' === self::get_rareview_scheduled_sticky_posts_window_status( $rareview_scheduled_sticky_posts_start, $rareview_scheduled_sticky_posts_until ) ) {
			$post_states['rareview_scheduled_sticky_posts-upcoming'] = __( 'Sticky (Upcoming)', 'rareview-scheduled-sticky-posts' );
		}

		if ( $rareview_scheduled_sticky_posts_until > 0 && 'expired' === self::get_rareview_scheduled_sticky_posts_window_status( $rareview_scheduled_sticky_posts_start, $rareview_scheduled_sticky_posts_until ) ) {
			$post_states['rareview_scheduled_sticky_posts-expired'] = __( 'Sticky (Expired)', 'rareview-scheduled-sticky-posts' );
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
	public function evaluate_rareview_scheduled_sticky_posts_status( bool $is_sticky, int $post_id ): bool {
		$post_id = absint( $post_id );

		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		$rareview_scheduled_sticky_posts_post_types = Helpers::get_rareview_scheduled_sticky_posts_post_types();
		$post_type                                  = get_post_type( $post_id );

		if ( ! in_array( $post_type, $rareview_scheduled_sticky_posts_post_types, true ) ) {
			return $is_sticky;
		}

		$is_custom_sticky = (bool) get_post_meta( $post_id, self::RAREVIEW_SCHEDULED_STICKY_POSTS_META_KEY, true );

		if ( 'post' === $post_type && ! $is_custom_sticky ) {
			return $is_sticky;
		}

		if ( ! $is_custom_sticky ) {
			return false;
		}

		$rareview_scheduled_sticky_posts_start = absint( get_post_meta( $post_id, self::RAREVIEW_SCHEDULED_STICKY_POSTS_START_META_KEY, true ) );
		$rareview_scheduled_sticky_posts_until = absint( get_post_meta( $post_id, self::RAREVIEW_SCHEDULED_STICKY_POSTS_UNTIL_META_KEY, true ) );

		return 'active' === self::get_rareview_scheduled_sticky_posts_window_status( $rareview_scheduled_sticky_posts_start, $rareview_scheduled_sticky_posts_until );
	}
}
