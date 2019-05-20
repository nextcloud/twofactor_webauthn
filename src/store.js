/**
 * @author Michael Blumenstein <M.Flower@gmx.de>
 * @copyright Copyright (c) 2019 Michael Blumenstein <M.Flower@gmx.de>
 *
 * Two-factor webauthn
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 *
 * Software Credits
 *
 * The development of this software was made possible using the following components:
 *
 * twofactor_u2f (https://github.com/nextcloud/twofactor_u2f) by Christoph Wurst (https://github.com/ChristophWurst)
 * Licensed Under: AGPL
 * This project used the great twofactor provider u2f created by Christoph Wurst as a template.
 *
 * webauthn-framework (https://github.com/web-auth/webauthn-framework) by Florent Morselli (https://github.com/Spomky)
 * Licensed Under: MIT
 * The webauthn-framework provided most of the code and documentation for implementing the webauthn authentication.
 */

import Vue from 'vue'
import Vuex from 'vuex'

import {removeRegistration} from './services/RegistrationService'

Vue.use(Vuex)

export const mutations = {
	addDevice (state, device) {
		state.devices.push(device)
		state.devices.sort((d1, d2) => d1.name.localeCompare(d2.name))
	},

	removeDevice (state, id) {
		state.devices = state.devices.filter(device => device.id !== id)
	}
}

export const actions = {
	removeDevice ({state, commit}, id) {
		const device = state.devices[id]

		commit('removeDevice', id)

		removeRegistration(id)
			.catch(err => {
				// Rollback
				commit('addDevice', device)

				throw err
			})
	}
}

export const getters = {}

export default new Vuex.Store({
	strict: process.env.NODE_ENV !== 'production',
	state: {
		devices: []
	},
	getters,
	mutations,
	actions
})
