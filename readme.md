# Stickify by Rareview
This is a WordPress plugin to give the ability to add a "sticky" feature to custom post types.

## Instructions
Clone this repo into a local WordPress environment's `plugins` directory.

Install necessary packages and dependencies
- `composer install`
- `npm install`

Compile the plugins code for testing
- Run `npm run watch` to watch for code changes and continuously compile the code
- Run `npm run build` to build production code

## Testing
Activate the Stickify plugin.

By default, the meta option this plugin provides will be available on Posts and Pages. To include the meta option on a custom post type, use the following filter.

```
function extend_stickify_post_types( $post_types ) {
  $post_types[] = 'testimonial'; // Your CPT name this meta should be available on

  return $post_types;
}
add_filter( 'sticky-cpts_post_types', 'extend_stickify_post_types' );
```

When viewing the archive of a post type that this applies to, posts with the meta option toggled on will appear at the start of an archive's list.
