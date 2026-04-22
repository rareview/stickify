<?php
/**
 * Helpers class.
 *
 * @author Rareview <hello@rareview.com>
 *
 * @package StickyPostTypes
 */

namespace StickyPostTypes\Inc;

use StickyPostTypes\Inc\Register;

/**
 * Class Registry
 */
class Helpers {

	const STICKY_CACHE_KEY = 'sticky_post_type';

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
		return plugins_url( 'dist/' . self::asset_name( $file ), dirname( __DIR__ ) . '/sticky-post-types.php' );
	}

	/**
	 * Get sticky post types.
	 *
	 * @return array
	 */
	public static function get_sticky_post_types() {
		return (array) get_option( 'sticky_post_types_post_types', [] );
	}

	/**
	 * Get sticky posts by post type.
	 *
	 * @param string $post_type Post type to get sticky posts for.
	 *
	 * @return array
	 */
	public static function get_sticky_posts_by_type( string $post_type ): array {
		if ( empty( $post_type ) ) {
			return [];
		}

		$sticky_post_ids = get_transient( self::STICKY_CACHE_KEY . '-' . $post_type );

		if ( false === $sticky_post_ids ) {
			$current_time = time();

			$args = [
				'post_type'              => $post_type,
				'post_status'            => 'publish',
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'AND',
					[
						'key'     => Register::STICKY_META_KEY,
						'value'   => '1',
						'compare' => '=',
						'type'    => 'NUMERIC',
					],
					[
						'relation' => 'OR',
						[
							'key'     => Register::STICKY_START_META_KEY,
							'compare' => 'NOT EXISTS',
						],
						[
							'key'     => Register::STICKY_START_META_KEY,
							'value'   => '',
							'compare' => '=',
						],
						[
							'key'     => Register::STICKY_START_META_KEY,
							'value'   => 0,
							'compare' => '=',
							'type'    => 'NUMERIC',
						],
						[
							'key'     => Register::STICKY_START_META_KEY,
							'value'   => $current_time,
							'compare' => '<=',
							'type'    => 'NUMERIC',
						],
					],
					[
						'relation' => 'OR',
						[
							'key'     => Register::STICKY_UNTIL_META_KEY,
							'compare' => 'NOT EXISTS',
						],
						[
							'key'     => Register::STICKY_UNTIL_META_KEY,
							'value'   => '',
							'compare' => '=',
						],
						[
							'key'     => Register::STICKY_UNTIL_META_KEY,
							'value'   => 0,
							'compare' => '=',
							'type'    => 'NUMERIC',
						],
						[
							'key'     => Register::STICKY_UNTIL_META_KEY,
							'value'   => $current_time,
							'compare' => '>',
							'type'    => 'NUMERIC',
						],
					],
				],
			];

			$sticky_post_ids = get_posts( $args );

			// TODO: Add setting for cache duration.
			set_transient( self::STICKY_CACHE_KEY . '-' . $post_type, $sticky_post_ids, MINUTE_IN_SECONDS * 15 );
		}

		return array_map( 'absint', (array) $sticky_post_ids );
	}

	/**
	 * Delete sticky posts cache for a post type.
	 *
	 * @param string $post_type Post type to clear cache for.
	 *
	 * @return void
	 */
	public static function delete_sticky_posts_cache_by_type( string $post_type ): void {
		if ( empty( $post_type ) ) {
			return;
		}

		delete_transient( self::STICKY_CACHE_KEY . '-' . $post_type );
	}
}
