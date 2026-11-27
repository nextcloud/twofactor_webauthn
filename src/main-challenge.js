/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { loadState } from '@nextcloud/initial-state'
import { createApp } from 'vue'
import Challenge from './components/Challenge.vue'
import Nextcloud from './mixins/Nextcloud.js'

const credentialRequestOptions = loadState('twofactor_webauthn', 'credential-request-options')

const app = createApp(Challenge, { credentialRequestOptions })
app.mixin(Nextcloud)
app.mount('#twofactor-webauthn-challenge')
