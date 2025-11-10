<?php
/**
 * Plugin Name: Sticky Post Types
 * Description: Make any post type sticky
 * Version:     1.0.0
 * Author:      Rareview
 * Author URI:  https://rareview.com
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text domain: sticky-post-types
 *
 * @package StickyPostTypes
 */

// Require Composer autoloader if it exists.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
	new StickyPostTypes\Inc\StickyPostTypesServiceProvider();
} else {
	wp_die( 'You must install Composer packages before running this plugin.' );
}
