/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { shallowMount, createLocalVue } from '@vue/test-utils'
import Nextcloud from '../../../mixins/Nextcloud.js'
import Device from '../../../components/Device.vue'
import { createPinia, PiniaVuePlugin, setActivePinia } from 'pinia'
import { useMainStore } from '../../../store.js'

const localVue = createLocalVue()

localVue.mixin(Nextcloud)
localVue.use(PiniaVuePlugin)

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
			pinia,
			localVue,
		})

		expect(device.text()).to.have.string('Unnamed key')
	})
})
