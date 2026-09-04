/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import Nextcloud from './mixins/Nextcloud.js'
import Challenge from './components/Challenge.vue'
import { loadState } from '@nextcloud/initial-state'

const credentialRequestOptions = loadState('twofactor_webauthn', 'credential-request-options')

const app = createApp(Challenge, { credentialRequestOptions })
app.mixin(Nextcloud)
app.mount('#twofactor-webauthn-challenge')
