<!--
  - @copyright 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author Michael Blumenstein <M.Flower@gmx.de>
  - @author 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
  - @author Richard Steinmetz <richard@steinmetz.cloud>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
import { mapGetters } from 'vuex'
import { NcButton } from '@nextcloud/vue'
import { browserSupportsWebAuthn, startAuthentication } from '@simplewebauthn/browser'
import logger from '../logger.js'

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
		...mapGetters({
			credentialRequestOptions: 'getCredentialRequestOptions',
		}),
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
				authResponse = await startAuthentication(this.credentialRequestOptions)
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
