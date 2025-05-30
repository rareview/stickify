<?php
/**
 * Register class.
 *
 * @author Rareview <hello@rareview.com>
 *
 * @package Sticky CPTs
 */

namespace StickyCPTs\Inc;

use StickyCPTs\Inc\Helpers;
use WP_Query;

/**
 * Class Registry
 */
class Register {

    const PREFIX = 'sticky-cpts';
    const STICKY_META_KEY = '_rv_sticky_cpts';
    const REST_API_CUSTOM_NAMESPACE = 'sticky-cpts/v1';

    const POST_TYPES = [
        'post',
    ];

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
        add_filter( 'posts_orderby', [ $this, 'add_sticky_ordering' ], 10, 2 );
    }

    public function register_meta() {
        foreach ( Helpers::get_sticky_cpts_types() as $post_type ) {
            register_meta(
                'post',
                '_rv_sticky_cpts',
                [
                    'type'         => 'boolean',
                    'single'       => true,
                    'show_in_rest' => true,
                    'auth_callback' => function () {
                        return current_user_can( 'edit_posts' );
                    },
                    'default'      => false,
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
        wp_enqueue_script( self::PREFIX . '-editor-script', Helpers::asset_url( 'editor.js' ), ['wp-blocks', 'wp-dom-ready', 'wp-edit-post'], Helpers::version(), true );
    }

    /**
     * Filter sticky posts in the main query.
     * 
     * @param string $orderby Current orderby clause.
     * @param WP_Query $query Current WP_Query object.
     * 
     * @return string Modified orderby clause.
     */
    function add_sticky_ordering( string $orderby, WP_Query $query ) {
        if ( is_admin() || ! $query->is_main_query() ) {
            return $orderby;
        }
    
        if ( ! in_array( $query->get( 'post_type' ), Helpers::get_sticky_cpts_types(), true ) ) {
            return $orderby;
        }
    
        global $wpdb;
    
        // Join wp_postmeta as alias so we can test for meta key presence
        $orderby = "
            (SELECT COUNT(meta_id) FROM {$wpdb->postmeta} m 
                WHERE m.post_id = {$wpdb->posts}.ID 
                AND m.meta_key = '" . esc_sql( self::STICKY_META_KEY ) . "'
                AND m.meta_value = 1) DESC,
            {$wpdb->posts}.post_date DESC
        ";
    
        return $orderby;
    }
}
