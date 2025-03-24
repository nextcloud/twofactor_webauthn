/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import { createPinia, PiniaVuePlugin } from 'pinia'
import Nextcloud from './mixins/Nextcloud.js'
import Challenge from './components/Challenge.vue'
import { loadState } from '@nextcloud/initial-state'
import { useMainStore } from './store.js'

Vue.mixin(Nextcloud)

Vue.use(PiniaVuePlugin)
const pinia = createPinia()

const credentialRequestOptions = loadState('twofactor_webauthn', 'credential-request-options')
const mainStore = useMainStore(pinia)
mainStore.$patch({
	credentialRequestOptions,
})

export default new Vue({
	el: '#twofactor-webauthn-challenge',
	pinia,
	render: h => h(Challenge),
})
