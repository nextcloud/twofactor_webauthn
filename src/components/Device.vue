<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
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
			<ActionCheckbox data-testid="device-active"
				:model-value="active"
				@update:modelValue="changeActivation">
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
import { mapStores } from 'pinia'
import { useMainStore } from '../store.js'

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
		...mapStores(useMainStore),
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
				await this.mainStore.removeDevice(this.entityId)
			} catch (e) {
				console.error('could not delete device', e)
			}
		},
		async changeActivation(active) {
			try {
				await confirmPassword()
				await this.mainStore.changeActivationState({
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
