<!--
 - @author Michael Blumenstein <M.Flower@gmx.de>
 - @copyright Copyright (c) 2019 Michael Blumenstein <M.Flower@gmx.de>
 -
 - Two-factor webauthn
 -
 - This code is free software: you can redistribute it and/or modify
 - it under the terms of the GNU Affero General Public License, version 3,
 - as published by the Free Software Foundation.
 -
 - This program is distributed in the hope that it will be useful,
 - but WITHOUT ANY WARRANTY; without even the implied warranty of
 - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 - GNU Affero General Public License for more details.
 -
 - You should have received a copy of the GNU Affero General Public License, version 3,
 - along with this program.  If not, see <http://www.gnu.org/licenses/>
 -
 -
 - Software Credits
 -
 - The development of this software was made possible using the following components:
 -
 - twofactor_u2f (https://github.com/nextcloud/twofactor_u2f) by Christoph Wurst (https://github.com/ChristophWurst)
 - Licensed Under: AGPL
 - This project used the great twofactor provider u2f created by Christoph Wurst as a template.
 -
 - webauthn-framework (https://github.com/web-auth/webauthn-framework) by Florent Morselli (https://github.com/Spomky)
 - Licensed Under: MIT
 - The webauthn-framework provided most of the code and documentation for implementing the webauthn authentication.
 -->

<template>
	<div class="webauthn-device" :data-webauthn-id="id">
		<span class="icon-webauthn-device" v-bind:class="{ disabled: !active }" ></span>
		{{name || 'Unnamed device' }}
		<Actions>
			<ActionButton icon="icon-delete" @click=deleteDevice() :close-after-click="true">Delete</ActionButton>
			<ActionCheckbox :checked="active" @update:checked=changeActivation($event)>Active</ActionCheckbox>
		</Actions>
	</div>
</template>

<script>
	import { Actions } from '@nextcloud/vue/dist/Components/Actions';
	import { ActionButton } from '@nextcloud/vue/dist/Components/ActionButton';
	import { ActionCheckbox } from '@nextcloud/vue/dist/Components/ActionCheckbox';
	import confirmPassword from '@nextcloud/password-confirmation';

	export default {
		name: 'Device',
		props: {
			id: String,
			name: String,
			active: Boolean
		},
		components: {
			Actions,
			ActionButton,
			ActionCheckbox
		},
		methods: {
			deleteDevice () {
				confirmPassword()
					.then(() => this.$store.dispatch('removeDevice', this.id))
					.catch(console.error.bind(this))
			},
			changeActivation (active) {
				confirmPassword()
					.then(() => this.$store.dispatch('changeActivationState', { id: this.id, active }))
					.catch(console.error.bind(this))
			}
		}
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