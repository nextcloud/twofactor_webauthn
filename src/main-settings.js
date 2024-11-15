/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { loadState } from '@nextcloud/initial-state'
import store from './store.js'
import Vue from 'vue'

import Nextcloud from './mixins/Nextcloud.js'
import PersonalSettings from './components/PersonalSettings.vue'

import '@nextcloud/password-confirmation/dist/style.css'

Vue.mixin(Nextcloud)

const devices = loadState('twofactor_webauthn', 'devices')
devices.sort((d1, d2) => {
	if (!d1.name) {
		return 1
	} else if (!d2.name) {
		return -1
	} else {
		return d1.name.localeCompare(d2.name)
	}
})
store.replaceState({
	devices,
})

const View = Vue.extend(PersonalSettings)
new View({
	propsData: {
		httpWarning: document.location.protocol !== 'https:',
	},
	store,
}).$mount('#twofactor-webauthn-settings')
