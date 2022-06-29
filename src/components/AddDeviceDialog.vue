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
	<div v-if="step === RegistrationSteps.READY">
		<button @click="start">
			{{ t('twofactor_webauthn', 'Add security key') }}
		</button>
		<p v-if="errorMessage" class="error-message">
			<span class="icon icon-error" />
			{{ errorMessage }}
		</p>
	</div>

	<div v-else-if="step === RegistrationSteps.REGISTRATION"
		class="new-webauthn-device">
		<span class="icon-loading-small webauthn-loading" />
		{{ t('twofactor_webauthn', 'Please use your security key authorize.') }}
	</div>

	<div v-else-if="step === RegistrationSteps.NAMING"
		class="new-webauthn-device">
		<span class="icon-loading-small webauthn-loading" />
		<input v-model="name"
			type="text"
			:placeholder="t('twofactor_webauthn', 'Name your security key')"
			@keyup.enter="submit">
		<button @click="submit">
			{{ t('twofactor_webauthn', 'Add') }}
		</button>
	</div>

	<div v-else-if="step === RegistrationSteps.PERSIST"
		class="new-webauthn-device">
		<span class="icon-loading-small webauthn-loading" />
		{{ t('twofactor_webauthn', 'Adding your security key …') }}
	</div>

	<div v-else>
		Invalid registration step. This should not have happened.
	</div>
</template>

<script>
import confirmPassword from '@nextcloud/password-confirmation'

import {
	startRegistration,
	finishRegistration,
} from '../services/RegistrationService'

import logger from '../logger'

const debug = (text) => (data) => {
	logger.debug(text, { data })
	return data
}

const RegistrationSteps = Object.freeze({
	READY: 1,
	REGISTRATION: 2,
	NAMING: 3,
	PERSIST: 4,
})

export default {
	name: 'AddDeviceDialog',
	props: {
		httpWarning: Boolean,
	},
	data() {
		return {
			name: '',
			credential: {},
			RegistrationSteps,
			step: RegistrationSteps.READY,
			errorMessage: null,
		}
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

		start() {
			this.errorMessage = null
			logger.info('Starting to add a new twofactor webauthn device')
			this.step = RegistrationSteps.REGISTRATION

			return confirmPassword()
				.then(this.getRegistrationData)
				.then(this.register.bind(this))
				.then(() => (this.step = RegistrationSteps.NAMING))
				.catch(error => {
					logger.error(error.name + ': ' + error.message, {
						error,
					})
					// Do not show an error when the user aborts registration
					if (error.name !== 'AbortError') {
						this.errorMessage = error.message
					}
					this.step = RegistrationSteps.READY
				})
		},

		getRegistrationData() {
			return startRegistration()
				.then(publicKey => {
					publicKey.challenge = Uint8Array.from(window.atob(this.base64url2base64(publicKey.challenge)), c => c.charCodeAt(0))
					publicKey.user.id = Uint8Array.from(window.atob(publicKey.user.id), c => c.charCodeAt(0))
					if (publicKey.excludeCredentials) {
						publicKey.excludeCredentials = publicKey.excludeCredentials.map(data => {
							data.id = Uint8Array.from(window.atob(this.base64url2base64(data.id)), c => c.charCodeAt(0))
							return data
						})
					}
					return publicKey
				})
				.catch(error => {
					logger.error('getRegistrationData: Error getting webauthn registration data from server', { error })
					throw new Error(t('twofactor_webauthn', 'Server error while trying to add WebAuthn device'))
				})
		},

		register(publicKey) {
			logger.debug('starting webauthn registration')

			return navigator.credentials.create({ publicKey })
				.then(debug('navigator.credentials.create called'))
				.then(data => {
					this.credential = {
						id: data.id,
						type: data.type,
						rawId: this.arrayToBase64String(new Uint8Array(data.rawId)),
						response: {
							clientDataJSON: this.arrayToBase64String(new Uint8Array(data.response.clientDataJSON)),
							attestationObject: this.arrayToBase64String(new Uint8Array(data.response.attestationObject)),
						},
					}
				})
				.then(debug('mapped credentials data'))
				.catch(error => {
					logger.error('register: Error creating credentials', { error })
					throw error
				})
		},

		submit() {
			this.step = RegistrationSteps.PERSIST

			return confirmPassword()
				.then(this.saveRegistrationData)
				.then(debug('registration data saved'))
				.then(() => this.reset())
				.then(debug('app reset'))
				.then(() => this.$emit('add'))
				.catch(error => {
					logger.error(error, error.name)
					this.errorMessage = error.message
					this.step = RegistrationSteps.READY
				})
		},

		saveRegistrationData() {
			return finishRegistration(this.name, JSON.stringify(this.credential))
				.then(device => this.$store.commit('addDevice', device))
				.then(debug('new device added to store'))
				.catch(error => {
					logger.error('Error persisting webauthn registration', { error })
					throw new Error(t('twofactor_webauthn', 'Server error while trying to complete security key registration'))
				})
		},

		reset() {
			this.name = ''
			this.registrationData = {}
			this.step = RegistrationSteps.READY
		},
	},
}
</script>

<style scoped>
    .webauthn-loading {
        display: inline-block;
        vertical-align: sub;
        margin-left: 2px;
        margin-right: 5px;
    }

    .new-webauthn-device {
		display: flex;
        line-height: 300%;
    }

    .error-message {
        color: var(--color-error);
    }

	input {
		/* Fix appearance on login setup page */
		padding: 0;
		font-size: 15px;
	}
</style>
