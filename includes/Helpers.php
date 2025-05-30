<?php
/**
 * Helpers class.
 *
 * @author Rareview <hello@rareview.com>
 *
 * @package Sticky CPTs
 */

namespace StickyCPTs\Inc;

use StickyCPTs\Inc\Register;

/**
 * Class Registry
 */
class Helpers {

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
            $directory        = WP_CONTENT_DIR . '/plugins/sticky-cpts/dist';
            static::$manifest = json_decode( file_get_contents( "{$directory}/manifest.json" ), true );
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
        return \set_url_scheme( WP_CONTENT_URL . '/plugins/sticky-cpts/dist/' . self::asset_name( $file ) );
    }

    /**
	 * Get sticky post types.
	 *
	 * @return array
	 */
	public static function get_sticky_cpts_types() {
		return apply_filters( Register::PREFIX . '_post_types', Register::POST_TYPES );
	}

    /**
     * Get sticky posts by post type.
     * 
     * @param string $post_type Post type to get sticky posts for.
     * 
     * @return array
     */
    public static function get_sticky_posts_by_type( $post_type ) {
        $args = [
            'post_type'      => $post_type,
            'meta_query'    => [
                [
                    'key'     => Register::STICKY_META_KEY,
                    'value'   => true,
                    'compare' => '=',
                ],
            ],
        ];

        $sticky_posts = get_posts( $args );
        $sticky_post_ids = wp_list_pluck( $sticky_posts, 'ID' );

        return $sticky_post_ids;
    }
}
