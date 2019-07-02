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
		<span class="icon-webauthn-device"></span>
		{{name || 'Unnamed device' }}
		<span class="more">
		    <a class="icon icon-more"
			   v-on:click.stop="togglePopover"></a>
		    <div class="popovermenu"
				 :class="{open: showPopover}"
				 v-click-outside="hidePopover">
				<PopoverMenu :menu="menu"/>
		    </div>
		</span>
	</div>
</template>

<script>
	import ClickOutside from 'vue-click-outside'
	import confirmPassword from 'nextcloud-password-confirmation'
	import {PopoverMenu} from 'nextcloud-vue'

	export default {
		name: 'Device',
		props: {
			id: String,
			name: String,
		},
		components: {
			PopoverMenu
		},
		directives: {
			ClickOutside
		},
		data () {
			return {
				showPopover: false,
				menu: [
					{
						text: 'Remove',
						icon: 'icon-delete',
						action: () => {
							confirmPassword()
								.then(() => this.$store.dispatch('removeDevice', this.id))
								.catch(console.error.bind(this))
						}
					}
				]
			}
		},
		methods: {
			togglePopover () {
				this.showPopover = !this.showPopover
			},

			hidePopover () {
				this.showPopover = false
			}
		}
	}
</script>

<style scoped>
	.webauthn-device {
		line-height: 300%;
		display: flex;
	}

	.webauthn-device .more {
		position: relative;
	}

	.webauthn-device .more .icon-more {
		display: inline-block;
		width: 16px;
		height: 16px;
		padding-left: 20px;
		vertical-align: middle;
		opacity: .7;
	}

	.webauthn-device .popovermenu {
		right: -12px;
		top: 42px;
	}

	.icon-webauthn-device {
		display: inline-block;
		background-size: 100%;
		padding: 3px;
		margin: 3px;
	}
</style>