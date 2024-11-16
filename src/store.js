/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
