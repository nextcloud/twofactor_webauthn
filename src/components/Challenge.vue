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
	<div>
		<form method="POST"
			  ref="challengeForm">
			<input id="challenge"
				   type="hidden"
				   name="challenge"
				   v-model="challenge">
		</form>

		<p id="webauthn-info"
		   v-if="error">
			<strong>
				{{ t('twofactor_webauthn', 'An error occurred: {msg}', {msg: this.error}) }}
			</strong>
			<button class="btn sign"
					@click="sign">
				{{ t('twofactor_webauthn', 'Retry') }}
			</button>
		</p>
		<p id="webauthn-info"
		   v-else>
			{{ t('mail', 'Plug in your Webauthn device and press the button below to begin authorization.') }}
			<button class="btn sign"
					@click="sign">
				{{ t('twofactor_webauthn', 'Use webauthn device') }}
			</button>
		</p>
		<p id="webauthn-error"
		   style="display: none">
			<strong>{{ t('mail', 'An error occurred. Please try again.')}}</strong>
		</p>

		<p v-if="notSupported">
			<em>
			{{ t('twofactor_webauthn', 'Your browser does not support Webauthn.') }}
			</em>
		</p>
		<p v-else-if="httpWarning"
		   id="webauthn-http-warning">
			<em>
			{{ t('twofactor_webauthn', 'You are accessing this site via an insecure connection. Browsers might therefore refuse the Webauthn authentication.') }}
			</em>
		</p>
	</div>
</template>

<script>
    import { TWOFACTOR_WEBAUTHN } from '../constants';

	const debug = (text) => (data) => {
        console.debug(TWOFACTOR_WEBAUTHN, text, data)
        return data
    }

	export default {
		name: 'Challenge',
		props: {
			publicKey: {
				type: Object,
				required: true,
			},
			httpWarning: {
				type: Boolean,
				required: true,
			}
		},
		data () {
			return {
				notSupported: typeof(PublicKeyCredential) === "undefined",
				challenge: '',
				error: undefined,
			}
		},
		mounted () {
			// this.sign()
			// 	.catch(console.error.bind(this))
		},
		methods: {
			arrayToBase64String(a) {
				return btoa(String.fromCharCode(...a));
			},

			base64url2base64(input) {
				input = input
					.replace(/=/g, "")
					.replace(/-/g, '+')
					.replace(/_/g, '/');

				const pad = input.length % 4;
				if(pad) {
					if(pad === 1) {
						throw new Error('InvalidLengthError: Input base64url string is the wrong length to determine padding');
					}
					input += new Array(5-pad).join('=');
				}

				return input;
			},

			sign () {
				console.trace('sign');
				this.error = undefined;

				const publicKey = this.publicKey;

				publicKey.challenge = Uint8Array.from(window.atob(this.base64url2base64(publicKey.challenge)), c=>c.charCodeAt(0));
				if (publicKey.allowCredentials) {
					publicKey.allowCredentials = publicKey.allowCredentials.map((data) => ({
							...data,
							'id': Uint8Array.from(window.atob(this.base64url2base64(data.id)), c=>c.charCodeAt(0))
					}));
				}


				console.debug(TWOFACTOR_WEBAUTHN, 'Starting webauthn authentication', this.publicKey)

				return navigator.credentials.get({publicKey})
						.then(debug('got credentials'))
						.then(data => {
							return {
								id: data.id,
								type: data.type,
								rawId: this.arrayToBase64String(new Uint8Array(data.rawId)),
								response: {
									authenticatorData: this.arrayToBase64String(new Uint8Array(data.response.authenticatorData)),
									clientDataJSON: this.arrayToBase64String(new Uint8Array(data.response.clientDataJSON)),
									signature: this.arrayToBase64String(new Uint8Array(data.response.signature)),
									userHandle: data.response.userHandle ? this.arrayToBase64String(new Uint8Array(data.response.userHandle)) : null
								}
							};
						})
						.then(debug('mapped credentials'))
						.then(challenge => {
							this.challenge = JSON.stringify(challenge)

							return this.$nextTick(() => {
								this.$refs.challengeForm.submit()
							})
						})
						.then(debug('submitted challengeForm'))
						.catch(error => {
							this.error = error;
							console.log(error); // Example: timeout, interaction refused...
							window.location = window.location.href.replace('challenge/twofactor_webauthn', 'selectchallenge');
						});
			}
		}
	}
</script>

<style scoped>
    .sign {
        margin-top: 1em;
    }
</style>