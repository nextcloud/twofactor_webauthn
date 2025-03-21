/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'
import * as RegistrationService from './services/RegistrationService.js'

export const useMainStore = defineStore('main', {
	state: () => ({
		credentialRequestOptions: {},
		devices: [],
	}),
	actions: {
		addDevice(device) {
			this.devices.push(device)
			this.devices.sort((d1, d2) => d1.name.localeCompare(d2.name))
		},

		async removeDevice(entityId) {
			const device = this.devices.find(device => device.entityId === entityId)

			this.devices = this.devices.filter(device => device.entityId !== entityId)

			try {
				await RegistrationService.removeRegistration(entityId)
			} catch (err) {
				// Rollback
				this.addDevice(device)

				throw err
			}
		},

		async changeActivationState({ entityId, active }) {
			this.devices.find(device => device.entityId === entityId).active = active

			try {
				await RegistrationService.changeActivationState(entityId, active)
			} catch (err) {
				// Rollback
				this.changeActivationState({ entityId, active: !active })

				throw err
			}
		},
	},
})
