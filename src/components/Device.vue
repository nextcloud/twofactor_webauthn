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
	<div class="webauthn-device" :data-webauthn-id="id">
		<span class="icon-webauthn-device" :class="{ disabled: !active }" />
		{{ name || t('twofactor_webauthn', 'Unnamed key') }}
		<Actions>
			<ActionButton icon="icon-delete" :close-after-click="true" @click="onDelete">
				{{ t('twofactor_webauthn', 'Remove') }}
			</ActionButton>
			<ActionCheckbox :checked="active" @update:checked="changeActivation">
				{{ t('twofactor_webauthn', 'Active') }}
			</ActionCheckbox>
		</Actions>
	</div>
</template>

<script>
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import ActionCheckbox from '@nextcloud/vue/dist/Components/ActionCheckbox'
import confirmPassword from '@nextcloud/password-confirmation'

export default {
	name: 'Device',
	components: {
		Actions,
		ActionButton,
		ActionCheckbox,
	},
	props: {
		id: String,
		name: String,
		active: Boolean,
	},
	methods: {
		async onDelete() {
			await confirmPassword()
			try {
				await this.$store.dispatch('removeDevice', this.id)
			} catch (e) {
				console.error('could not delete device', e)
			}
		},
		async changeActivation(active) {
			await confirmPassword()
			try {
				this.$store.dispatch('changeActivationState', { id: this.id, active })
			} catch (e) {
				console.error('could not change device state', e)
			}
		},
	},
}
</script>

<style scoped>
	.webauthn-device {
		line-height: 300%;
		display: flex;
	}

	.icon-webauthn-device {
		display: inline-block;
		background-size: 100%;
		padding: 3px;
		margin: 3px;
	}
</style>
