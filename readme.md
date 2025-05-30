# Wordpress Plugin Starter by Rareview
This is a simple starter for building awesome WordPress plugins.

It provides infrastructure for easily incorporating features we regularly 
develop in our starter theme based projects, and converting them into WP plugins.

## Instructions
This is a template repository, so you can use it by clicking the "Use this template" button above.<br>
In your newly created repo, please follow these steps:

- Rename the plugin main root folder to Your Plugin Name
- Update plugin description in the main StickyCPTs.php file
- Update plugin description in the package.json file 
- Search and replace Sticky CPTs with Your Plugin Name
- Search and replace sticky-posts with your-plugin-name
- Search and replace StickyCPTs with YourPluginName
- Rename the main StickyCPTs.php file to YourPluginName.php
- Rename the StickyCPTsServiceProvider.php file to YourPluginNameServiceProvider.php
- Run `composer install`
- Run `npm install`
- Run `npm run watch` to watch for code changes and continuously compile the code
- Run `npm run build` to build production code
