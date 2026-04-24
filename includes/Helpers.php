<?php
/**
 * Helpers class.
 *
 * @author Rareview <hello@rareview.com>
 *
 * @package Stickify
 */

namespace Stickify\Inc;

use Stickify\Inc\Register;

/**
 * Class Registry
 */
class Helpers {

	const STICKIFY_CACHE_KEY = 'stickify_post_type';

	/**
	 * Plugin assets manifest.
	 *
	 * @var array
	 */
	protected static $manifest;

	/**
	 * Plugin version.
	 *
	 * @return string Template version.
	 */
	public static function version() {
		return '1.0.0';
	}

	/**
	 * Get the name of the asset file from the generated manifest file.
	 *
	 * @param string $file Asset file to retrieve.
	 *
	 * @return string Asset name.
	 */
	public static function asset_name( $file ) {
		if ( ! static::$manifest ) {
			$manifest_file = dirname( __DIR__ ) . '/dist/manifest.json';

			if ( ! file_exists( $manifest_file ) || ! is_readable( $manifest_file ) ) {
				static::$manifest = [];
			} else {
				if ( function_exists( 'wp_json_file_decode' ) ) {
					$decoded_manifest = wp_json_file_decode( $manifest_file, [ 'associative' => true ] );
					static::$manifest = is_array( $decoded_manifest ) ? $decoded_manifest : [];
				} else {
					$manifest_contents = file_get_contents( $manifest_file );

					if ( false === $manifest_contents ) {
						static::$manifest = [];
					} else {
						$decoded_manifest = json_decode( $manifest_contents, true );
						static::$manifest = is_array( $decoded_manifest ) ? $decoded_manifest : [];
					}
				}
			}
		}

		if ( ! isset( static::$manifest[ $file ] ) ) {
			return $file;
		}

		return static::$manifest[ $file ];
	}

	/**
	 * Gets the assets url, useful for defining asset source files.
	 *
	 * @param string $file Asset file to retrieve.
	 *
	 * @return string Asset url.
	 */
	public static function asset_url( $file ) {
		return plugins_url( 'dist/' . self::asset_name( $file ), dirname( __DIR__ ) . '/stickify.php' );
	}

	/**
	 * Get sticky post types.
	 *
	 * @return array
	 */
	public static function get_stickify_post_types() {
		return (array) get_option( 'stickify_post_types', [] );
	}

	/**
	 * Get sticky cache length in minutes.
	 *
	 * @return int
	 */
	public static function get_stickify_cache_length(): int {
		$cache_length = absint( get_option( 'stickify_cache_length', 15 ) );

		return max( 1, $cache_length );
	}

	/**
	 * Get sticky posts by post type.
	 *
	 * @param string $post_type Post type to get sticky posts for.
	 *
	 * @return array
	 */
	public static function get_stickify_posts_by_type( string $post_type ): array {
		if ( empty( $post_type ) ) {
			return [];
		}

		$stickify_post_ids = get_transient( self::STICKIFY_CACHE_KEY . '-' . $post_type );

		if ( false === $stickify_post_ids ) {
			$current_time = time();

			$args = [
				'post_type'              => $post_type,
				'post_status'            => 'publish',
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'stickify_post_types'    => false,
				'ignore_stickify_posts'  => true,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'AND',
					[
						'key'     => Register::STICKIFY_META_KEY,
						'value'   => '1',
						'compare' => '=',
						'type'    => 'NUMERIC',
					],
					[
						'relation' => 'OR',
						[
							'key'     => Register::STICKIFY_START_META_KEY,
							'compare' => 'NOT EXISTS',
						],
						[
							'key'     => Register::STICKIFY_START_META_KEY,
							'value'   => '',
							'compare' => '=',
						],
						[
							'key'     => Register::STICKIFY_START_META_KEY,
							'value'   => 0,
							'compare' => '=',
							'type'    => 'NUMERIC',
						],
						[
							'key'     => Register::STICKIFY_START_META_KEY,
							'value'   => $current_time,
							'compare' => '<=',
							'type'    => 'NUMERIC',
						],
					],
					[
						'relation' => 'OR',
						[
							'key'     => Register::STICKIFY_UNTIL_META_KEY,
							'compare' => 'NOT EXISTS',
						],
						[
							'key'     => Register::STICKIFY_UNTIL_META_KEY,
							'value'   => '',
							'compare' => '=',
						],
						[
							'key'     => Register::STICKIFY_UNTIL_META_KEY,
							'value'   => 0,
							'compare' => '=',
							'type'    => 'NUMERIC',
						],
						[
							'key'     => Register::STICKIFY_UNTIL_META_KEY,
							'value'   => $current_time,
							'compare' => '>',
							'type'    => 'NUMERIC',
						],
					],
				],
			];

			$stickify_post_ids = get_posts( $args );

			set_transient( self::STICKIFY_CACHE_KEY . '-' . $post_type, $stickify_post_ids, MINUTE_IN_SECONDS * self::get_stickify_cache_length() );
		}

		return array_map( 'absint', (array) $stickify_post_ids );
	}

	/**
	 * Delete stickify posts cache for a post type.
	 *
	 * @param string $post_type Post type to clear cache for.
	 *
	 * @return void
	 */
	public static function delete_stickify_cache_by_type( string $post_type ): void {
		if ( empty( $post_type ) ) {
			return;
		}

		delete_transient( self::STICKIFY_CACHE_KEY . '-' . $post_type );
	}

	/**
	 * Remove a post ID from core sticky posts.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	public static function remove_core_sticky_post( int $post_id ): void {
		$post_id  = absint( $post_id );
		$stickies = get_option( 'sticky_posts', [] );

		if ( ! is_array( $stickies ) || ! $post_id ) {
			return;
		}

		$stickies = array_values(
			array_filter(
				array_map( 'absint', $stickies ),
				function ( $sticky_id ) use ( $post_id ) {
					return $sticky_id !== $post_id;
				}
			)
		);

		update_option( 'sticky_posts', $stickies );
	}

	/**
	 * Add a post ID to core sticky posts.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	public static function add_core_sticky_post( int $post_id ): void {
		$post_id  = absint( $post_id );
		$stickies = get_option( 'sticky_posts', [] );

		if ( ! is_array( $stickies ) ) {
			$stickies = [];
		}

		$stickies = array_map( 'absint', $stickies );

		if ( ! $post_id || in_array( $post_id, $stickies, true ) ) {
			return;
		}

		$stickies[] = $post_id;

		update_option( 'sticky_posts', $stickies );
	}
}
