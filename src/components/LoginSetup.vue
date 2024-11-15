<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<p>{{ t('twofactor_webauthn', 'Set up a security key as a second factor.') }}</p>
		<div v-if="!added" class="add-device">
			<AddDeviceDialog :http-warning="httpWarning"
				@add="onAdded" />
		</div>
		<p v-else>
			{{ t('twofactor_webauthn', 'Your security key was added successfully. You are now being redirected to the login page.') }}
		</p>
		<p v-if="notSupported">
			{{ t('twofactor_webauthn', 'Your browser does not support WebAuthn.') }}
		</p>
		<p v-if="httpWarning"
			id="u2f-http-warning">
			{{ t('twofactor_webauthn', 'You are accessing this site via an insecure connection. Browsers might therefore refuse the WebAuthn authentication.') }}
		</p>
		<form ref="confirmForm" method="POST" />
	</div>
</template>

<script>
import AddDeviceDialog from './AddDeviceDialog.vue'

export default {
	name: 'LoginSetup',
	components: {
		AddDeviceDialog,
	},
	data() {
		return {
			added: false,
			notSupported: !window.PublicKeyCredential,
		}
	},
	computed: {
		httpWarning() {
			return document.location.protocol !== 'https:'
		},
	},
	methods: {
		onAdded() {
			this.added = true
			this.$refs.confirmForm.submit()
		},
	},
}
</script>

<style scoped>
.add-device {
	display: flex;
	justify-content: space-around;
}
</style>
