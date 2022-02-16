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
		<p v-if="devices.length === 0">
			{{ t('twofactor_webauthn', 'No WebAuthn devices configured. You are not using WebAuthn as second factor at the moment.') }}
		</p>
		<p v-else>
			{{ t('twofactor_webauthn', 'The following devices are configured for WebAuthn two-factor authentication:') }}
		</p>
		<Device v-for="device in devices"
			:id="device.id"
			:key="device.id"
			:name="device.name"
			:active="device.active" />

		<AddDeviceDialog :http-warning="httpWarning" />
		<p v-if="allDeactivated" class="webauthn-warning">
			<span class="icon icon-info" />
			{{ t('twofactor_webauthn', 'All devices are deactivated.') }}
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

import AddDeviceDialog from './AddDeviceDialog'
import Device from './Device'

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
