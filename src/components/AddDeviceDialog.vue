<!--
  - @copyright 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author Michael Blumenstein <M.Flower@gmx.de>
  - @author 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
  - @author 2023 Richard Steinmetz <richard@steinmetz.cloud>
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
		<NcButton class="new-webauthn-device__button"
			@click="start">
			{{ t('twofactor_webauthn', 'Add security key') }}
		</NcButton>
		<p v-if="errorMessage" class="error-message">
			<span class="icon icon-error" />
			{{ errorMessage }}
		</p>
	</div>

	<div v-else-if="step === RegistrationSteps.REGISTRATION"
		class="new-webauthn-device">
		<span class="icon-loading-small webauthn-loading" />
		{{ t('twofactor_webauthn', 'Please use your security key to authorize.') }}
	</div>

	<div v-else-if="step === RegistrationSteps.NAMING"
		class="new-webauthn-device">
		<span class="icon-loading-small webauthn-loading" />
		<form @submit.prevent="submit" class="new-webauthn-device__form">
			<input v-model="name"
				required
				type="text"
				:placeholder="t('twofactor_webauthn', 'Name your security key')">
			<NcButton class="new-webauthn-device__button"
				native-type="submit"
				:disabled="!name.length">
				{{ t('twofactor_webauthn', 'Add') }}
			</NcButton>
		</form>
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
import { confirmPassword } from '@nextcloud/password-confirmation'
import { NcButton } from '@nextcloud/vue'

import {
	startRegistration,
	finishRegistration,
} from '../services/RegistrationService.js'

import logger from '../logger.js'

const RegistrationSteps = Object.freeze({
	READY: 1,
	REGISTRATION: 2,
	NAMING: 3,
	PERSIST: 4,
})

export default {
	name: 'AddDeviceDialog',

	components: {
		NcButton,
	},

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

		async start() {
			this.errorMessage = null
			logger.info('Starting to add a new twofactor webauthn device')
			this.step = RegistrationSteps.REGISTRATION

			try {
				await confirmPassword()
				const registrationData = await this.getRegistrationData()
				await this.register(registrationData)
				this.step = RegistrationSteps.NAMING
			} catch (error) {
				if (error?.name && error?.message) {
					logger.error(error.name + ': ' + error.message, {
						error,
					})

					// Do not show an error when the user aborts registration
					if (error.name !== 'AbortError') {
						this.errorMessage = error.message
					}
				}

				this.step = RegistrationSteps.READY
			}
		},

		async getRegistrationData() {
			try {
				const publicKey = await startRegistration()
				publicKey.challenge = Uint8Array.from(window.atob(this.base64url2base64(publicKey.challenge)), c => c.charCodeAt(0))
				publicKey.user.id = Uint8Array.from(window.atob(publicKey.user.id), c => c.charCodeAt(0))
				if (publicKey.excludeCredentials) {
					publicKey.excludeCredentials = publicKey.excludeCredentials.map(data => {
						data.id = Uint8Array.from(window.atob(this.base64url2base64(data.id)), c => c.charCodeAt(0))
						return data
					})
				}
				return publicKey
			} catch (error) {
				logger.error('getRegistrationData: Error getting webauthn registration data from server', { error })
				throw new Error(t('twofactor_webauthn', 'Server error while trying to add WebAuthn device'))
			}
		},

		async register(publicKey) {
			logger.debug('starting webauthn registration')

			try {
				const data = await navigator.credentials.create({ publicKey })
				logger.debug('navigator.credentials.create called', { data })
				this.credential = {
					id: data.id,
					type: data.type,
					rawId: this.arrayToBase64String(new Uint8Array(data.rawId)),
					response: {
						clientDataJSON: this.arrayToBase64String(new Uint8Array(data.response.clientDataJSON)),
						attestationObject: this.arrayToBase64String(new Uint8Array(data.response.attestationObject)),
					},
				}
				logger.debug('mapped credentials data')
			} catch (error) {
				logger.error('register: Error creating credentials', { error })
				throw error
			}
		},

		async submit() {
			this.step = RegistrationSteps.PERSIST

			try {
				await confirmPassword()
				await this.saveRegistrationData()
				logger.debug('registration data saved')
				this.reset()
				this.$emit('add')
			} catch (error) {
				logger.error(error, { error })
				this.errorMessage = error.message
				this.step = RegistrationSteps.READY
			}
		},

		async saveRegistrationData() {
			try {
				const device = await finishRegistration(this.name, JSON.stringify(this.credential))
				this.$store.commit('addDevice', device)
				logger.debug('new device added to store', { device })
			} catch (error) {
				logger.error('Error persisting webauthn registration', { error })
				throw new Error(t('twofactor_webauthn', 'Server error while trying to complete security key registration'))
			}
		},

		reset() {
			this.name = ''
			this.registrationData = {}
			this.step = RegistrationSteps.READY
		},
	},
}
</script>

<style scoped lang="scss">
    .webauthn-loading {
        display: inline-block;
        vertical-align: sub;
        margin-left: 2px;
        margin-right: 5px;
    }

    .new-webauthn-device {
		display: flex;
        line-height: 300%;
		align-items: center;

		&__form {
			display: flex;
			align-items: center;
		}
    }

	.new-webauthn-device__button {
		margin: 10px 6px;
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
