const path = require( 'path' );
const assets = './assets/src/';
const miniExtract = require( 'mini-css-extract-plugin' );
const autoprefixer = require( 'autoprefixer' );
const externals = {
	jquery: 'jQuery',
	'@eventespresso/eejs': 'eejs',
	'@eventespresso/i18n': 'eejs.i18n',
	'@wordpress/api-fetch': 'wp.apiFetch',
	'@wordpress/data': 'wp.data',
	'@wordpress/element': 'wp.element',
	'@wordpress/components': 'wp.components',
	'@wordpress/blocks': 'wp.blocks',
	'@wordpress/editor': 'wp.editor',
	'@wordpress/compose': 'wp.compose',
	'@wordpress/hooks': 'wp.hooks',
	react: 'React',
	'react-dom': 'ReactDOM',
	'react-redux': 'eejs.vendor.reactRedux',
	redux: 'eejs.vendor.redux',
	classnames: 'eejs.vendor.classnames',
	lodash: 'lodash',
	'moment-timezone': 'eejs.vendor.moment',
	cuid: 'eejs.vendor.cuid',
};

/** see below for multiple configurations.
 /** https://webpack.js.org/configuration/configuration-types/#exporting-multiple-configurations */
const config = [
	{
		configName: 'ee-paypal-smart-buttons',
		entry: {
			'paypal-smart-buttons': assets + 'ee-paypal-smart-buttons.js',
		},
		module: {
			rules: [
				{
					test: /\.js$/,
					exclude: /node_modules/,
					loader: 'babel-loader',
				},
			],
		},
		output: {
			filename: 'ee-[name].[chunkhash].dist.js',
			path: path.resolve( __dirname, 'assets/dist' ),
			library: [ 'eePayPalSmartButtons' ],
			libraryTarget: 'var',
		},
	},
];
module.exports = config;
