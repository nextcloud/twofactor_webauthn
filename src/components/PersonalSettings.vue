<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<p v-if="devices.length === 0">
			{{ t('twofactor_webauthn', 'No security keys configured. You are not using WebAuthn as second factor at the moment.') }}
		</p>
		<p v-else>
			{{ t('twofactor_webauthn', 'The following security keys are configured for WebAuthn two-factor authentication:') }}
		</p>
		<Device v-for="device in devices"
			:id="device.id"
			:key="device.entityId"
			:entity-id="device.entityId"
			:name="device.name"
			:active="device.active"
			:created-at="device.createdAt" />

		<AddDeviceDialog :http-warning="httpWarning" />
		<p v-if="allDeactivated" class="webauthn-warning">
			<span class="icon icon-info" />
			{{ t('twofactor_webauthn', 'All security keys are deactivated.') }}
		</p>
		<p v-if="notSupported" class="webauthn-warning">
			<span class="icon icon-info" />
			{{ t('twofactor_webauthn', 'Your browser does not support WebAuthn.') }}
		</p>
		<p v-if="httpWarning" class="webauthn-warning">
			<span class="icon icon-info" />
			{{ t('twofactor_webauthn', 'You are accessing this site via an insecure connection. Browsers might therefore refuse the WebAuthn authentication.') }}
		</p>
	</div>
</template>

<script>

import AddDeviceDialog from './AddDeviceDialog.vue'
import Device from './Device.vue'

export default {
	name: 'PersonalSettings',
	components: {
		AddDeviceDialog,
		Device,
	},
	props: {
		httpWarning: Boolean,
	},
	data() {
		return {
			notSupported: typeof (PublicKeyCredential) === 'undefined',
		}
	},
	computed: {
		devices() {
			return this.$store.state.devices
		},
		allDeactivated() {
			return this.$store.state.devices.length > 0 && this.$store.state.devices.every(device => !device.active)
		},
	},
}
</script>

<style scoped>
</style>
