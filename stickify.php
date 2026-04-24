<?php
/**
 * Plugin Name: Stickify - Scheduled Sticky Posts
 * Description: Add, schedule, and manage sticky posts across post types.
 * Version:     1.0.0
 * Author:      Rareview
 * Author URI:  https://rareview.com
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text domain: stickify
 *
 * @package Stickify
 */

// Require Composer autoloader if it exists.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
	new Stickify\Inc\StickifyServiceProvider();
} else {
	wp_die( 'You must install Composer packages before running this plugin.' );
}
