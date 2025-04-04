/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import Nextcloud from './mixins/Nextcloud.js'
import Challenge from './components/Challenge.vue'
import { loadState } from '@nextcloud/initial-state'
import { useMainStore } from './store.js'

const pinia = createPinia()

const credentialRequestOptions = loadState('twofactor_webauthn', 'credential-request-options')
const mainStore = useMainStore(pinia)
mainStore.$patch({
	credentialRequestOptions,
})

const app = createApp(Challenge)
app.mixin(Nextcloud)
app.use(pinia)
app.mount('#twofactor-webauthn-challenge')
