/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { shallowMount, createLocalVue } from '@vue/test-utils'
import Vuex from 'vuex'

import Nextcloud from '../../../mixins/Nextcloud.js'

import Device from '../../../components/Device.vue'

const localVue = createLocalVue()

localVue.use(Vuex)
localVue.mixin(Nextcloud)

describe('Device', () => {
	let actions
	let store

	beforeEach(() => {
		actions = {}
		store = new Vuex.Store({
			state: {
				devices: [],
			},
			actions,
		})
	})

	it('renders devices without a name', () => {
		store.state.devices.push({
			id: 'k1',
			name: undefined,
		})
		const device = shallowMount(Device, {
			store,
			localVue,
		})

		expect(device.text()).to.have.string('Unnamed key')
	})
})
