/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import Nextcloud from './mixins/Nextcloud.js'

import LoginSetup from './components/LoginSetup.vue'

const pinia = createPinia()

const app = createApp(LoginSetup)
app.mixin(Nextcloud)
app.use(pinia)
app.mount('#twofactor-webauthn-login-setup')
