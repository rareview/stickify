const path = require( 'path' );
const glob = require( 'glob' );
const ExtractCSS = require( 'mini-css-extract-plugin' );
const { WebpackManifestPlugin } = require( 'webpack-manifest-plugin' );

const options = { publicPath: '' };

module.exports = (env, argv) => {
	const isProd = argv.mode === 'production';

	return {
		mode: isProd ? 'production' : 'development',
		entry: {
			editor: './assets/js/editor.js',
			'admin-settings': './assets/js/admin-settings.js',
		},
		output: {
			filename: '[name].min.js',
			path: path.resolve(__dirname, 'dist'),
		},
		devtool: isProd ? false : 'source-map',
		plugins : [
			new ExtractCSS( { filename : `[name].min.css` } ),
			new WebpackManifestPlugin(options),
		],
		module : {
			rules : [
				{
					test    : /\.js$/,
					exclude : /node_modules/,
					use     : {
						loader  : 'babel-loader',
						options : {
							'presets': [
								[
									/**
									 * @link https://babeljs.io/docs/en/babel-preset-env#corejs
									 */
									'@babel/preset-env',
									{
										useBuiltIns: 'usage',
										corejs: {
											version: 3,
											proposals: true
										},
									}
								],
								'@babel/preset-react',
								'@wordpress/default'
							],
						},
					},
				},

				{
					test    : /\.scss$/i,
					include : glob.sync( process.cwd( ) + '/!(dist|node_modules)/' ),
					use     : [
						ExtractCSS.loader,
						{
							loader  : 'css-loader',
							options : {
								sourceMap : !isProd,
								url       : false,
							},
						},
						{
							loader  : 'sass-loader',
							options : {
								sourceMap   : !isProd,
								sassOptions : {
									includePaths : glob.sync( process.cwd( ) + '/!(dist|node_modules)/' ),
									indentType   : 'tab',
									indentWidth  : 1,
									outputStyle  : isProd ? 'compressed' : 'expanded',
								},
							},
						},
					],
				},
			],
		},
		optimization: {
			removeEmptyChunks: true
		},
	};
};
