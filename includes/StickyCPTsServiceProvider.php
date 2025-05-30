<?php
/**
 * Sticky CPTs service provider.
 *
 * @author Rareview <hello@rareview.com>
 *
 * @package Sticky CPTs
 */

namespace StickyCPTs\Inc;

/**
 *
 * Plugin service provider.
 */
class StickyCPTsServiceProvider {

    /**
     * The plugin features that should be bootstrapped.
     *
     * @var array
     */
    public static array $services = [
        Register::class,
        Rest::class,
    ];

    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function __construct() {
        foreach ( self::$services as $service ) {
            new $service();
        }
    }
}
