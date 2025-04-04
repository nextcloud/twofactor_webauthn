/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

const path = require('path')
const webpackConfig = require('@nextcloud/webpack-vue-config')

delete webpackConfig.entry['main']
webpackConfig.entry['challenge'] = path.join(__dirname, 'src', 'main-challenge.js')
webpackConfig.entry['settings'] = path.join(__dirname, 'src', 'main-settings.js')
webpackConfig.entry['login-setup'] = path.join(__dirname, 'src', 'main-login-setup.js')

module.exports = webpackConfig
