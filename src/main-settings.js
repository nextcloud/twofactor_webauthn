/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { loadState } from '@nextcloud/initial-state'
import { createApp } from 'vue'
import { createPinia } from 'pinia'

import Nextcloud from './mixins/Nextcloud.js'
import PersonalSettings from './components/PersonalSettings.vue'
import { useMainStore } from './store.js'

import '@nextcloud/password-confirmation/style.css'

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

const app = createApp(PersonalSettings, {
	httpWarning: document.location.protocol !== 'https:',
})
app.mixin(Nextcloud)
app.use(pinia)
app.mount('#twofactor-webauthn-settings')
