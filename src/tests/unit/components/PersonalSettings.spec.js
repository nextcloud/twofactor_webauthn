/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { shallowMount, createLocalVue } from '@vue/test-utils'
import Vuex from 'vuex'

import Nextcloud from '../../../mixins/Nextcloud.js'

import PersonalSettings from '../../../components/PersonalSettings.vue'

const localVue = createLocalVue()

localVue.use(Vuex)
localVue.mixin(Nextcloud)

describe('PersonalSettings', () => {
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

	it('shows text if no devices are configured', () => {
		const settings = shallowMount(PersonalSettings, {
			store,
			localVue,
		})

		expect(settings.text()).to.contain('No security keys configured. You are not using WebAuthn as second factor at the moment.')
	})

	it('shows no info text if devices are configured', () => {
		store.state.devices.push({
			id: 'k1',
			name: 'a',
		})
		const settings = shallowMount(PersonalSettings, {
			store,
			localVue,
		})

		expect(settings.text()).to.not.contain('No security keys configured. You are not using WebAuthn as second factor at the moment.')
	})

	it('shows a HTTP warning', () => {
		const settings = shallowMount(PersonalSettings, {
			store,
			localVue,
			propsData: {
				httpWarning: true,
			},
		})

		expect(settings.text()).to.contain('You are accessing this site via an')
	})
})
