# Stickify

Stickify is a WordPress plugin that adds sticky post behavior to post types beyond the default Posts post type.

It gives editors a sidebar control in the block editor to mark supported content as sticky, optionally schedule when the sticky state starts, and optionally set when it expires. On the front end, matching posts are moved to the top of eligible archive queries.

[Launch Stickify Playground](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/rareview/stickify/main/playground/blueprint.json)

## How It Works

- Enable sticky behavior for one or more public post types in **Settings > Stickify**.
- Open a supported post in the block editor and enable **Stickify Settings**.
- Optionally set a start date and an end date for scheduled stickiness.
- On archives and Query Loop block output for that post type, sticky content is prepended to the results.
- Use the Stickify settings screen to clear cached sticky query results or bulk remove sticky behavior from posts.

## Features

- Sticky support for public post types beyond core posts
- Block editor sidebar controls for sticky state
- Optional scheduled start and end dates
- Settings screen for selecting enabled post types
- Cache length control for sticky query results
- Bulk sticky-post management from the admin settings screen
- Preserves native WordPress sticky behavior for regular posts

## Development Environment

Required tools:

- WordPress 6.0+
- PHP 8.0+
- Node.js 20.17.0+
- npm 10+
- Composer

Recommended local setup:

- A local WordPress environment such as Local, wp-env, Laravel Herd, or a standard LAMP/LEMP stack
- Clone this repository into your WordPress installation's `wp-content/plugins/` directory

## Getting Started

1. Clone this repository into `wp-content/plugins/stickify`.
2. Install PHP dependencies with `composer install`.
3. Install JavaScript dependencies with `npm install`.
4. Build the plugin assets with `npm run build`.
5. Activate the plugin in WordPress.

For active development, run `npm run watch` to rebuild editor and admin assets as files change.

## Available Scripts

- `npm run watch` builds assets in development mode and watches for changes
- `npm run build` creates a production asset build
- `npm run lint` runs JS, CSS, JSON, and PHP linting
- `npm run build:zip` creates a distributable plugin zip for Playground or release testing
- `composer lint` runs PHP_CodeSniffer
- `composer format` runs PHPCBF

## Using The Plugin

1. Go to **Settings > Stickify**.
2. Select the public post types that should support sticky behavior.
3. Optionally adjust the cache length for sticky query results.
4. Save settings.
5. Edit a supported post in the block editor.
6. Open the **Stickify Settings** panel in the document sidebar.
7. Enable sticky behavior and optionally choose start and end dates.
8. View the relevant archive or Query Loop output to confirm the post has been promoted.

## Notes

- Pages and attachments are intentionally excluded from the settings UI.
- Stickify currently applies to queries that resolve to a single enabled post type.
- Supported post types must expose custom fields for the editor controls to appear.

## Playground

The `playground/` directory contains a WordPress Playground blueprint and sample content used for quick testing and demos.
