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

import store from './store.js'
import Vue from 'vue'
import Nextcloud from './mixins/Nextcloud.js'
import Challenge from './components/Challenge.vue'
import { loadState } from '@nextcloud/initial-state'

Vue.mixin(Nextcloud)

const credentialRequestOptions = loadState('twofactor_webauthn', 'credential-request-options')
store.commit('setCredentialRequestOptions', credentialRequestOptions)

export default new Vue({
	el: '#twofactor-webauthn-challenge',
	store,
	render: h => h(Challenge),
})
