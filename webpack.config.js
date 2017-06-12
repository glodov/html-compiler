const path = require('path');
const webpack = require('webpack');

const __dirSource = path.resolve(__dirname, './public/dev/src');
const __dirJs     = path.resolve(__dirSource, './js');
const __dirSass   = path.resolve(__dirSource, './sass');
const __dirAssets = path.resolve(__dirname, './public/dev/assets');

let config = {
  context: __dirSource,
  entry: {
    app: [
    	path.resolve(__dirJs, './index.js')
    ],
  },
  output: {
  	path: path.resolve(__dirAssets, './js'),
    filename: 'bundle.js',
  },
	externals: {
		jquery: 'jQuery',
		bootstrap: true		
	},
	module: {
		rules: [],
		loaders: []
	},
	plugins: []
};


const babelRule = {
	test: /\.js$/,
	exclude: [/node_modules/],
	use: [
		{
			loader: 'babel-loader',
			options: {
				cacheDirectory: true,
				presets: [
					[
						"env", 
						{
							targets: {
								browsers: [
									"last 2 versions", 
									"safari >= 7"
								]
							}
						}
					]
				]
			}
		}
	]
};
config.module.rules.push(babelRule);

// no emit plugin
config.plugins.push(new webpack.NoEmitOnErrorsPlugin());

// uglify plugin
if ('production' === process.env.NODE_ENV) {
	config.plugins.push(new webpack.optimize.UglifyJsPlugin({sourceMap: true}));
}

module.exports = config;