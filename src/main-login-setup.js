/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'

import Nextcloud from './mixins/Nextcloud.js'
import store from './store.js'

import LoginSetup from './components/LoginSetup.vue'

Vue.mixin(Nextcloud)

const View = Vue.extend(LoginSetup)
new View({ store }).$mount('#twofactor-webauthn-login-setup')
