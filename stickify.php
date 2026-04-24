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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/Helpers.php';
require_once __DIR__ . '/includes/Register.php';
require_once __DIR__ . '/includes/Rest.php';
require_once __DIR__ . '/includes/Settings.php';
require_once __DIR__ . '/includes/StickifyServiceProvider.php';

new Stickify\Inc\StickifyServiceProvider();
