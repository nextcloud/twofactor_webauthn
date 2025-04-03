<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
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
				type="submit"
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
import { startRegistration } from '@simplewebauthn/browser'
import * as RegistrationService from '../services/RegistrationService.js'
import logger from '../logger.js'
import { mapStores } from 'pinia'
import { useMainStore } from '../store.js'

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
			registrationResponse: null,
			RegistrationSteps,
			step: RegistrationSteps.READY,
			errorMessage: null,
		}
	},

	computed: {
		...mapStores(useMainStore),
	},

	methods: {
		async start() {
			this.errorMessage = null
			logger.info('Starting to add a new twofactor webauthn device')
			this.step = RegistrationSteps.REGISTRATION

			try {
				await confirmPassword()
				const registrationData = await RegistrationService.startRegistration()
				this.registrationResponse = await startRegistration({
					optionsJSON: registrationData,
				})
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
				const device = await RegistrationService.finishRegistration(
					this.name,
					JSON.stringify(this.registrationResponse),
				)
				this.mainStore.addDevice(device)
				logger.debug('new device added to store', { device })
			} catch (error) {
				logger.error('Error persisting webauthn registration', { error })
				throw new Error(t('twofactor_webauthn', 'Server error while trying to complete security key registration'))
			}
		},

		reset() {
			this.name = ''
			this.registrationResponse = null
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
