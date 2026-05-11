<?php
/**
 * Plugin Name: Rareview Scheduled Sticky Posts
 * Description: Add, schedule, and manage sticky posts across post types.
 * Version:     1.0.0
 * Author:      Rareview®
 * Author URI:  https://rareview.com
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: rareview-scheduled-sticky-posts
 *
 * @package RareviewScheduledStickyPosts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/Helpers.php';
require_once __DIR__ . '/includes/Register.php';
require_once __DIR__ . '/includes/Rest.php';
require_once __DIR__ . '/includes/Settings.php';
require_once __DIR__ . '/includes/RareviewScheduledStickyPostsServiceProvider.php';

new RareviewScheduledStickyPosts\Inc\RareviewScheduledStickyPostsServiceProvider();
