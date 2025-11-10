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

	const STICKY_META_KEY  = '_sticky_post_type';
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
			$directory        = WP_CONTENT_DIR . '/plugins/sticky-post-types/dist';
			static::$manifest = json_decode( wp_remote_get( "{$directory}/manifest.json" ), true );
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
		return \set_url_scheme( WP_CONTENT_URL . '/plugins/sticky-post-types/dist/' . self::asset_name( $file ) );
	}

	/**
	 * Get sticky post types.
	 *
	 * @return array
	 */
	public static function get_sticky_post_types_types() {
		return get_option( 'sticky_post_types_post_types', [] );
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
			$args = [
				'post_type'  => $post_type,
				'meta_query' => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					[
						'key'     => Register::STICKY_META_KEY,
						'value'   => true,
						'compare' => '=',
					],
				],
			];

			$sticky_posts    = get_posts( $args );
			$sticky_post_ids = wp_list_pluck( $sticky_posts, 'ID' );

			set_transient( self::STICKY_CACHE_KEY . '-' . $post_type, $sticky_post_ids, DAY_IN_SECONDS );
		}

		return $sticky_post_ids;
	}
}
