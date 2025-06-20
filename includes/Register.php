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

/**
 * Class Register
 */
class Register {

    const PREFIX = 'sticky-cpts';
    const STICKY_META_KEY = '_rv_sticky_cpts';
    const REST_API_CUSTOM_NAMESPACE = 'sticky-cpts/v1';

    const POST_TYPES = [];

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
        add_filter( 'the_posts', [ $this, 'prepend_sticky_posts' ], 10, 2 );
        add_filter( 'is_sticky', [ $this, 'evauluate_sticky_status' ], 10, 2 );
    }

    public function register_meta() {
        foreach ( Helpers::get_sticky_cpts_types() as $post_type ) {
            register_meta(
                'post',
                '_rv_sticky_cpts',
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
        wp_enqueue_script( self::PREFIX . '-editor-script', Helpers::asset_url( 'editor.js' ), ['wp-blocks', 'wp-dom-ready', 'wp-edit-post'], Helpers::version(), true );
    }
    
    public function maybe_prepend_sticky_posts( $query ) {
        if ( ! $query->is_main_query() || is_admin() || ! $query->is_post_type_archive( Helpers::get_sticky_cpts_types() ) ) {
            return;
        }

        if ( $query->get('paged') > 1 ) {
            return;
        }

        $sticky_posts = Helpers::get_sticky_posts_by_type( $query->get( 'post_type' ) );
        
        if ( ! empty( $sticky_posts ) ) {
            $this->sticky_posts_to_prepend = $sticky_posts;
        }
    }

    public function prepend_sticky_posts( $posts, $query ) {
        $sticky_post_types = Helpers::get_sticky_cpts_types();

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

        $sticky_posts = get_posts( [
            'post__in'         => $sticky_ids,
            'post_type'        => $query->get( 'post_type' ),
            'orderby'          => 'post__in',
            'posts_per_page'   => count( $sticky_ids ),
            'suppress_filters' => false,
        ] );

        return array_merge( $sticky_posts, $posts );
    }

    public function evauluate_sticky_status( $is_sticky, $post_id ) {
        $post_id = absint( $post_id );

        if ( ! $post_id ) {
            $post_id = get_the_ID();
        }

        $post_type = get_post_type( $post_id );

        if ( ! in_array( $post_type, Helpers::get_sticky_cpts_types(), true ) ) {
            return $is_sticky;
        }

        return (bool) get_post_meta( $post_id, self::STICKY_META_KEY, true );
    }
}
