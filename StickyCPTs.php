<?php
/*
Plugin Name: Sticky CPTs
Description: A WordPress plugin to allow sticky posts on any post type.
Version: 1.0.0
Author: Rareview
*/

// Require Composer autoloader if it exists.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
    new StickyCPTs\Inc\StickyCPTsServiceProvider();
} else {
    wp_die( 'You must install Composer packages before running this theme.' );
}

