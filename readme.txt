=== Stickify ===

Contributors: rareview
Tags: sticky posts, custom post types, gutenberg, query loop, editorial workflow
Requires at least: 6.0
Tested up to: 6.9
Stable tag: 1.0.0
Requires PHP: 8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Make any post type sticky

== Description ==

Stickify brings sticky post behavior to post types beyond standard WordPress Posts.

Choose which public post types should support sticky content, then manage sticky state directly from the block editor. Editors can optionally schedule when a post becomes sticky and when that sticky period expires.

Stickify also includes an admin settings screen for enabling post types, adjusting sticky cache duration, clearing cached sticky query data, and reviewing or removing sticky behavior from posts in bulk.

Features include:

* Sticky support for public post types beyond core posts
* Block editor sidebar controls for sticky state
* Optional sticky start and end dates
* Support for Query Loop block output
* Admin tools for cache clearing and sticky post cleanup

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/stickify` directory, or install the plugin through the WordPress plugins screen.
2. Activate the plugin through the Plugins screen in WordPress.
3. Go to `Settings > Stickify`.
4. Select the public post types that should support sticky behavior.
5. Save your changes.
6. Edit a supported post in the block editor and use the Stickify Settings panel to enable sticky behavior.

== Frequently Asked Questions ==

= Which post types can use Stickify? =

Any public post type shown on the Stickify settings screen can be enabled, except for excluded built-in types such as attachments and pages.

= Where do I enable sticky behavior for a post? =

Open a supported post in the block editor and use the Stickify Settings panel in the document sidebar.

= Can I schedule sticky behavior? =

Yes. You can optionally set a start date and an end date for each sticky post.

= Does this work with normal WordPress sticky posts? =

Yes. For the default Posts post type, Stickify respects native WordPress sticky behavior while adding scheduling and management tools.

= Can I clear sticky settings in bulk? =

Yes. The Stickify settings screen includes tools to review sticky posts, clear cached sticky query results, and remove sticky behavior from selected posts.

== Upgrade Notice ==

= 1.0.0 =

Initial public release of Stickify.

== Changelog ==

= 1.0.0 =

* Initial release
