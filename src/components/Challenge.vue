<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<form ref="challengeForm"
			method="POST">
			<input id="challenge"
				:value="challenge"
				type="hidden"
				name="challenge">
		</form>

		<p v-if="error"
			id="webauthn-info">
			<strong>
				{{ t('twofactor_webauthn', 'An error occurred: {msg}', {msg: error}) }}
			</strong>
			<NcButton class="btn sign"
				@click="sign">
				{{ t('twofactor_webauthn', 'Retry') }}
			</NcButton>
		</p>
		<p v-else id="webauthn-info">
			{{ t('twofactor_webauthn', 'Use security key') }}
		</p>
		<p id="webauthn-error"
			style="display: none">
			<strong>{{ t('mail', 'An error occurred. Please try again.') }}</strong>
		</p>

		<p v-if="notSupported">
			<em>
				{{ t('twofactor_webauthn', 'Your browser does not support WebAuthn.') }}
			</em>
		</p>
		<p v-else-if="httpWarning"
			id="webauthn-http-warning">
			<em>
				{{ t('twofactor_webauthn', 'You are accessing this site via an insecure connection. Browsers might therefore refuse the WebAuthn authentication.') }}
			</em>
		</p>
	</div>
</template>

<script>
import { NcButton } from '@nextcloud/vue'
import { browserSupportsWebAuthn, startAuthentication } from '@simplewebauthn/browser'
import logger from '../logger.js'
import { mapState } from 'pinia'
import { useMainStore } from '../store.js'

export default {
	name: 'Challenge',

	components: {
		NcButton,
	},

	data() {
		return {
			challenge: '',
			error: undefined,
		}
	},

	computed: {
		...mapState(useMainStore, ['credentialRequestOptions']),
		httpWarning() {
			return document.location.protocol !== 'https:'
		},
		/**
		 * @return {boolean} True, if WebAuthn is not supported by the browser
		 */
		notSupported() {
			return !browserSupportsWebAuthn()
		},
	},

	mounted() {
		this.sign()
	},

	methods: {
		async sign() {
			this.error = undefined

			logger.debug('Starting webauthn authentication', {
				credentialOptions: this.credentialRequestOptions,
			})

			let authResponse
			try {
				authResponse = await startAuthentication({
					optionsJSON: this.credentialRequestOptions,
				})
			} catch (error) {
				switch (error.name) {
				case 'AbortError':
					this.error = t('twofactor_webauthn', 'Authentication cancelled')
					break
				case 'NotAllowedError':
					this.error = t('twofactor_webauthn', 'Authentication cancelled')
					break
				default:
					this.error = error.toString()
				}
				logger.error('challenge failed', { error })
				return
			}
			logger.debug('got credentials', { authResponse })

			// Wait for challenge to propagate to the template
			this.challenge = JSON.stringify(authResponse)
			await this.$nextTick()

			this.$refs.challengeForm.submit()
			logger.debug('submitted challengeForm')
		},
	},
}
</script>

<style scoped>
    .sign {
        margin-top: 1em;
    }
    .btn {
        margin: 0 auto;
        margin-top: 12px;
    }
</style>
