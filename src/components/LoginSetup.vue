<!--
  - @copyright Copyright (c) 2022 Richard Steinmetz <richard@steinmetz.cloud>
  -
  - @author Richard Steinmetz <richard@steinmetz.cloud>
  -
  - @license AGPL-3.0-or-later
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<div>
		<!-- TODO: at some explanatory text about what this page is about -->
		<div v-if="!added" class="add-device">
			<AddDeviceDialog :http-warning="httpWarning"
				@add="onAdded" />
		</div>
		<p v-else>
			{{ t('twofactor_webauthn', 'Your device was added successfully. You are now being redirected to the login page.') }}
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
import AddDeviceDialog from './AddDeviceDialog'

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
