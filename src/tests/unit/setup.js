/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

// Emulate browser environment for tests (inside node)
// Ref (url): https://github.com/jsdom/jsdom/issues/2383#issuecomment-442199291
// Ref (SVGElement): https://github.com/vuejs/core/issues/3590
require('jsdom-global')('', {
	url: 'http://localhost',
})
global.SVGElement = window.SVGElement

global.expect = require('chai').expect
global.OC = {
	getCurrentUser: () => {
		return { uid: false }
	},
	isUserAdmin() {
		return false
	},
	getLanguage() {
		return 'en'
	},
	getLocale() {
		return 'en'
	},
}
global.t = (app, str) => str

// https://github.com/vuejs/vue-test-utils/issues/936
// better fix for "TypeError: Super expression must either be null or
// a function" than pinning an old version of prettier.
window.Date = Date
