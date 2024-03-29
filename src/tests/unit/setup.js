/*
 * @copyright 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Michael Blumenstein <M.Flower@gmx.de>
 * @author 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author 2023 Richard Steinmetz <richard@steinmetz.cloud>
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

// Emulate browser environment for tests (inside node)
// Ref (url): https://github.com/jsdom/jsdom/issues/2383#issuecomment-442199291
// Ref (SVGElement): https://github.com/vuejs/core/issues/3590
require('jsdom-global')('', {
	url: 'http://localhost',
})
global.SVGElement = window.SVGElement

const t = (app, str) => str

require('vue').mixin({
	methods: {
		t,
	},
})

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
global.t = t

// https://github.com/vuejs/vue-test-utils/issues/936
// better fix for "TypeError: Super expression must either be null or
// a function" than pinning an old version of prettier.
window.Date = Date
