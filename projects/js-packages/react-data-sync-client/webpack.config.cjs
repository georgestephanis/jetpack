const path = require( 'path' );
const jetpackWebpackConfig = require( '@automattic/jetpack-webpack-config/webpack' );

module.exports = {
	entry: './src/index.ts',
	mode: jetpackWebpackConfig.mode,
	devtool: jetpackWebpackConfig.devtool,
	module: {
		strictExportPresence: true,
		rules: [
			// Transpile JavaScript and TypeScript
			jetpackWebpackConfig.TranspileRule( {
				exclude: /node_modules\//,
			} ),
		],
	},
	optimization: {
		...jetpackWebpackConfig.optimization,
	},
	resolve: {
		...jetpackWebpackConfig.resolve,
	},
	output: {
		...jetpackWebpackConfig.output,
		path: path.resolve( __dirname, 'build' ),
		filename: 'index.js',
		uniqueName: 'ReactDataSyncClient',
		library: {
			name: 'ReactDataSyncClient',
			type: 'umd',
		},
	},
	plugins: [
		...jetpackWebpackConfig.StandardPlugins( {
			// Generate `.d.ts` files per tsconfig settings.
			ForkTSCheckerPlugin: {},
		} ),
	],
};
