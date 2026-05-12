const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		editor: './assets/js/editor.js',
		'admin-settings': './assets/js/admin-settings.js',
		'quick-edit': './assets/js/quick-edit.js',
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'dist' ),
	},
};
