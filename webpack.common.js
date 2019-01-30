const path = require( 'path' );
const assets = './assets/src/';
const externals = {
	paypal: 'paypal',
	jquery: 'jQuery',
	'@eventespresso/eejs': 'eejs',
	'@eventespresso/i18n': 'eejs.i18n',
};
/** see below for multiple configurations.
 /** https://webpack.js.org/configuration/configuration-types/#exporting-multiple-configurations */
const config = [
	{
		configName: 'ee-paypal-smart-buttons',
		entry: {
			'paypal-smart-buttons': assets + 'ee-paypal-smart-buttons.js',
		},
		externals: externals,
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
		},
	},
];
module.exports = config;
