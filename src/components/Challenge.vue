<!--
  - @copyright 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author Michael Blumenstein <M.Flower@gmx.de>
  - @author 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
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
				v-model="challenge"
				type="hidden"
				name="challenge">
		</form>

		<p v-if="error"
			id="webauthn-info">
			<strong>
				{{ t('twofactor_webauthn', 'An error occurred: {msg}', {msg: error}) }}
			</strong>
			<button class="btn sign"
				@click="sign">
				{{ t('twofactor_webauthn', 'Retry') }}
			</button>
		</p>
		<p v-else
			id="webauthn-info">
			{{ t('mail', 'Plug in your Webauthn device and press the button below to begin authorization.') }}
			<button class="btn sign"
				@click="sign">
				{{ t('twofactor_webauthn', 'Use webauthn device') }}
			</button>
		</p>
		<p id="webauthn-error"
			style="display: none">
			<strong>{{ t('mail', 'An error occurred. Please try again.') }}</strong>
		</p>

		<p v-if="notSupported">
			<em>
				{{ t('twofactor_webauthn', 'Your browser does not support Webauthn.') }}
			</em>
		</p>
		<p v-else-if="httpWarning"
			id="webauthn-http-warning">
			<em>
				{{ t('twofactor_webauthn', 'You are accessing this site via an insecure connection. Browsers might therefore refuse the Webauthn authentication.') }}
			</em>
		</p>
	</div>
</template>

<script>
import { TWOFACTOR_WEBAUTHN } from '../constants'

const debug = (text) => (data) => {
	console.debug(TWOFACTOR_WEBAUTHN, text, data)
	return data
}

export default {
	name: 'Challenge',
	props: {
		publicKey: {
			type: Object,
			required: true,
		},
		httpWarning: {
			type: Boolean,
			required: true,
		},
	},
	data() {
		return {
			notSupported: typeof (PublicKeyCredential) === 'undefined',
			challenge: '',
			error: undefined,
		}
	},
	mounted() {
		// TODO: wait for the user to click the button or run on load?
		// this.sign().catch(console.error.bind(this))
	},
	methods: {
		arrayToBase64String(a) {
			return btoa(String.fromCharCode(...a))
		},

		base64url2base64(input) {
			input = input
				.replace(/=/g, '')
				.replace(/-/g, '+')
				.replace(/_/g, '/')

			const pad = input.length % 4
			if (pad) {
				if (pad === 1) {
					throw new Error('InvalidLengthError: Input base64url string is the wrong length to determine padding')
				}
				input += new Array(5 - pad).join('=')
			}

			return input
		},

		sign() {
			console.trace('sign')
			this.error = undefined

			const publicKey = this.publicKey

			publicKey.challenge = Uint8Array.from(window.atob(this.base64url2base64(publicKey.challenge)), c => c.charCodeAt(0))
			if (publicKey.allowCredentials) {
				publicKey.allowCredentials = publicKey.allowCredentials.map((data) => ({
					...data,
					id: Uint8Array.from(window.atob(this.base64url2base64(data.id)), c => c.charCodeAt(0)),
				}))
			}

			console.debug(TWOFACTOR_WEBAUTHN, 'Starting webauthn authentication', this.publicKey)

			return navigator.credentials.get({ publicKey })
				.then(debug('got credentials'))
				.then(data => {
					return {
						id: data.id,
						type: data.type,
						rawId: this.arrayToBase64String(new Uint8Array(data.rawId)),
						response: {
							authenticatorData: this.arrayToBase64String(new Uint8Array(data.response.authenticatorData)),
							clientDataJSON: this.arrayToBase64String(new Uint8Array(data.response.clientDataJSON)),
							signature: this.arrayToBase64String(new Uint8Array(data.response.signature)),
							userHandle: data.response.userHandle ? this.arrayToBase64String(new Uint8Array(data.response.userHandle)) : null,
						},
					}
				})
				.then(debug('mapped credentials'))
				.then(challenge => {
					this.challenge = JSON.stringify(challenge)

					// eslint-disable-next-line vue/valid-next-tick
					return this.$nextTick(() => {
						this.$refs.challengeForm.submit()
					})
				})
				.then(debug('submitted challengeForm'))
				.catch(error => {
					this.error = error
					console.log(error) // Example: timeout, interaction refused...
					window.location = window.location.href.replace('challenge/twofactor_webauthn', 'selectchallenge')
				})
		},
	},
}
</script>

<style scoped>
    .sign {
        margin-top: 1em;
    }
</style>
