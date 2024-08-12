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
	<div class="webauthn-device" :data-webauthn-id="id">
		<span class="icon-webauthn-device" :class="{ disabled: !active }" />
		{{ name || t('twofactor_webauthn', 'Unnamed key') }}
		<Actions class="webauthn-device__actions">
			<ActionText v-if="createdAt" :title="t('twofactor_webauthn', 'Registered')">
				<template #icon>
					<InformationOutline :size="20" />
				</template>
				{{ createdAtFormatted }}
			</ActionText>
			<ActionCheckbox :checked="active" @update:checked="changeActivation">
				{{ t('twofactor_webauthn', 'Active') }}
			</ActionCheckbox>
			<ActionButton icon="icon-delete" :close-after-click="true" @click="onDelete">
				{{ t('twofactor_webauthn', 'Remove') }}
			</ActionButton>
		</Actions>
	</div>
</template>

<script>
import {
	NcActions as Actions,
	NcActionButton as ActionButton,
	NcActionCheckbox as ActionCheckbox,
	NcActionText as ActionText,
} from '@nextcloud/vue'
import InformationOutline from 'vue-material-design-icons/InformationOutline.vue'
import { confirmPassword } from '@nextcloud/password-confirmation'
import moment from '@nextcloud/moment'

export default {
	name: 'Device',
	components: {
		Actions,
		ActionButton,
		ActionCheckbox,
		ActionText,
		InformationOutline,
	},
	props: {
		entityId: Number,
		id: String,
		name: String,
		active: Boolean,
		createdAt: {
			type: Number,
			default: undefined,
		},
	},
	computed: {
		createdAtFormatted() {
			if (!this.createdAt) {
				return
			}

			return moment(this.createdAt * 1000).format('L')
		},
	},
	methods: {
		async onDelete() {
			try {
				await confirmPassword()
				await this.$store.dispatch('removeDevice', this.entityId)
			} catch (e) {
				console.error('could not delete device', e)
			}
		},
		async changeActivation(active) {
			try {
				await confirmPassword()
				await this.$store.dispatch('changeActivationState', {
					entityId: this.entityId,
					active,
				})
			} catch (e) {
				console.error('could not change device state', e)
			}
		},
	},
}
</script>

<style lang="scss" scoped>
	.webauthn-device {
		line-height: var(--default-clickable-area);
		display: flex;

		&__actions {
			margin-left: var(--default-grid-baseline);
		}
	}

	.device__created_at {
		color: var(--color-text-maxcontrast);
	}

	.icon-webauthn-device {
		display: inline-block;
		background-size: 100%;
		padding: 3px;
		margin: 3px;
		filter: var(--background-invert-if-dark);
	}
</style>
