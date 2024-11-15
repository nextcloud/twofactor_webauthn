/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import store from './store.js'
import Vue from 'vue'
import Nextcloud from './mixins/Nextcloud.js'
import Challenge from './components/Challenge.vue'
import { loadState } from '@nextcloud/initial-state'

Vue.mixin(Nextcloud)

const credentialRequestOptions = loadState('twofactor_webauthn', 'credential-request-options')
store.commit('setCredentialRequestOptions', credentialRequestOptions)

export default new Vue({
	el: '#twofactor-webauthn-challenge',
	store,
	render: h => h(Challenge),
})
