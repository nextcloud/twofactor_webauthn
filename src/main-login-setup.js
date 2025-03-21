/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import { createPinia, PiniaVuePlugin } from 'pinia'

import Nextcloud from './mixins/Nextcloud.js'

import LoginSetup from './components/LoginSetup.vue'

Vue.mixin(Nextcloud)

Vue.use(PiniaVuePlugin)
const pinia = createPinia()

const View = Vue.extend(LoginSetup)
new View({ pinia }).$mount('#twofactor-webauthn-login-setup')
