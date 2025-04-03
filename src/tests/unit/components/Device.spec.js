/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { shallowMount } from '@vue/test-utils'
import Nextcloud from '../../../mixins/Nextcloud.js'
import Device from '../../../components/Device.vue'
import { createPinia, setActivePinia } from 'pinia'
import { useMainStore } from '../../../store.js'

describe('Device', () => {
	let pinia

	beforeEach(() => {
		pinia = createPinia()
		setActivePinia(pinia)
	})

	it('renders devices without a name', () => {
		const mainStore = useMainStore()
		mainStore.$patch({
			devices: [{
				id: 'k1',
				name: undefined,
			}],
		})

		const device = shallowMount(Device, {
			global: {
				plugins: [pinia],
				mixins: [Nextcloud],
			},
		})

		expect(device.text()).to.have.string('Unnamed key')
	})
})
