/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { loadState } from '@nextcloud/initial-state'
import Vue from 'vue'
import { createPinia, PiniaVuePlugin } from 'pinia'

import Nextcloud from './mixins/Nextcloud.js'
import PersonalSettings from './components/PersonalSettings.vue'
import { useMainStore } from './store.js'

import '@nextcloud/password-confirmation/dist/style.css'

Vue.mixin(Nextcloud)

Vue.use(PiniaVuePlugin)
const pinia = createPinia()

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
const mainStore = useMainStore(pinia)
mainStore.$patch({
	devices,
})

const View = Vue.extend(PersonalSettings)
new View({
	propsData: {
		httpWarning: document.location.protocol !== 'https:',
	},
	pinia,
}).$mount('#twofactor-webauthn-settings')
