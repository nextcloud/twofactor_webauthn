/*
 * @copyright 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Michael Blumenstein <M.Flower@gmx.de>
 * @author 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

const webpack = require('webpack')
const path = require('path')
const { VueLoaderPlugin } = require('vue-loader')

module.exports = {
	entry: {
		challenge: path.join(__dirname, 'main-challenge.js'),
		settings: path.join(__dirname, 'main-settings.js'),
		'login-setup': path.join(__dirname, 'main-login-setup.js'),
	},
	output: {
		path: path.resolve(__dirname, '../js'),
		publicPath: '/js/',
	},
	module: {
		rules: [
			{
				test: /\.css$/,
				use: ['vue-style-loader', 'css-loader'],
			},
			{
				test: /\.scss$/,
				use: ['vue-style-loader', 'css-loader', 'sass-loader'],
			},
			{
				test: /\.vue$/,
				loader: 'vue-loader',
			},
			{
				test: /\.js$/,
				loader: 'babel-loader',
				exclude: /node_modules/,
			},
			{
				test: /\.(png|jpg|gif)$/,
				loader: 'file-loader',
				options: {
					name: '[name].[ext]?[hash]',
				},
			},
			{
				test: /\.(svg)$/i,
				use: [
					{
						loader: 'url-loader',
					},
				],
			},
		],
	},
	plugins: [
		new VueLoaderPlugin(),

		// @nextcloud/moment since v1.3.0 uses `moment/min/moment-with-locales.js`
		// Which works only in Node.js and is not compatible with Webpack bundling
		// It has an unused function `localLocale` that requires locales by invalid relative path `./locale`
		// Though it is not used, Webpack tries to resolve it with `require.context` and fails
		new webpack.IgnorePlugin({
			resourceRegExp: /^\.\/locale$/,
			contextRegExp: /moment\/min$/,
		}),
	],
	resolve: {
		extensions: ['*', '.js', '.vue'],
	},
}
