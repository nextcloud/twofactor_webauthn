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

import Vue from 'vue'
import Vuex from 'vuex'

import { removeRegistration, changeActivationState } from './services/RegistrationService.js'

Vue.use(Vuex)

export const mutations = {
	setCredentialRequestOptions(state, credentialRequestOptions) {
		state.credentialRequestOptions = credentialRequestOptions
	},

	addDevice(state, device) {
		state.devices.push(device)
		state.devices.sort((d1, d2) => d1.name.localeCompare(d2.name))
	},

	removeDevice(state, entityId) {
		state.devices = state.devices.filter(device => device.entityId !== entityId)
	},

	changeActivationState(state, { entityId, active }) {
		state.devices.find(device => device.entityId === entityId).active = active
	},
}

export const actions = {
	removeDevice({ state, commit }, entityId) {
		const device = state.devices.find(device => device.entityId === entityId)

		commit('removeDevice', entityId)

		removeRegistration(entityId)
			.catch(err => {
				// Rollback
				commit('addDevice', device)

				throw err
			})
	},

	changeActivationState({ state, commit }, { entityId, active }) {
		commit('changeActivationState', { entityId, active })

		changeActivationState(entityId, active).catch(err => {
			commit('changeActivationState', { entityId, active: !active })
			throw err
		})
	},
}

export const getters = {
	getCredentialRequestOptions: state => state.credentialRequestOptions,
}

export default new Vuex.Store({
	strict: process.env.NODE_ENV !== 'production',
	state: {
		credentialRequestOptions: {},
		devices: [],
	},
	getters,
	mutations,
	actions,
})
