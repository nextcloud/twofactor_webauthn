/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

const webpackConfig = require('./webpack.config.js')

// For some reason mocha(pack) fails to import webpack chunks because they have a ?v=[contenthash]
// suffix that is not actually present in names of written entrypoints.
webpackConfig.output.filename = webpackConfig.output.filename.replace('?v=[contenthash]', '')

module.exports = webpackConfig
