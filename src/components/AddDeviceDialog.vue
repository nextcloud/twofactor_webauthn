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
    <div v-if="step === RegistrationSteps.READY">
        <button
                v-on:click="start">{{ t('twofactor_webauthn', 'Add Webauthn device') }}
        </button>
    </div>

    <div v-else-if="step === RegistrationSteps.REGISTRATION"
         class="new-webauthn-device">
        <span class="icon-loading-small webauthn-loading"></span>
        {{ t('twofactor_webauthn', 'Please plug in your Webauthn device and press the device button to authorize.') }}
    </div>

    <div v-else-if="step === RegistrationSteps.NAMING"
         class="new-webauthn-device">
        <span class="icon-loading-small webauthn-loading"></span>
        <input type="text"
               :placeholder="t('twofactor_webauthn', 'Name your device')"
               v-model="name">
        <button v-on:click="submit">{{ t('twofactor_webauthn', 'Add') }}</button>
    </div>

    <div v-else-if="step === RegistrationSteps.PERSIST"
         class="new-webauthn-device">
        <span class="icon-loading-small webauthn-loading"></span>
        {{ t('twofactor_webauthn', 'Adding your device …') }}
    </div>

    <div v-else>
        Invalid registration step. This should not have happened.
    </div>
</template>

<script>
    import confirmPassword from 'nextcloud-password-confirmation'

    import {
        startRegistration,
        finishRegistration
    } from '../services/RegistrationService'

    const logAndPass = (text) => (data) => {
        console.debug(text)
        return data
    }

    const RegistrationSteps = Object.freeze({
        READY: 1,
        REGISTRATION: 2,
        NAMING: 3,
        PERSIST: 4,
    })

    export default {
        name: 'AddDeviceDialog',
        props: {
            httpWarning: Boolean
        },
        data() {
            return {
                name: '',
                credential: {},
                RegistrationSteps,
                step: RegistrationSteps.READY,
            }
        },
        methods: {
            arrayToBase64String(a) {
                return btoa(String.fromCharCode(...a));
            },
            start() {
                this.step = RegistrationSteps.REGISTRATION

                return confirmPassword()
                    .then(this.getRegistrationData)
                    .then(this.register.bind(this))
                    .then(() => this.step = RegistrationSteps.NAMING)
                    .catch(err => {
                        console.error(err.name, err.message);
                        this.step = RegistrationSteps.READY;
                    })
            },

            getRegistrationData() {
                return startRegistration()
                    .then(publicKey => {
                        publicKey.challenge = Uint8Array.from(window.atob(publicKey.challenge), c => c.charCodeAt(0));
                        publicKey.user.id = Uint8Array.from(publicKey.user.id, c => c.charCodeAt(0));
                        return publicKey;
                    })
                    .catch(err => {
                        console.error('Error getting webauthn registration data from server', err)
                        throw new Error(t('twofactor_webauthn', 'Server error while trying to add webauthn device'))
                    })
            },

            register(publicKey) {
                console.debug('starting webauthn registration');

                return navigator.credentials.create({publicKey})
                    .then(data => {
                        this.credential = {
                            id: data.id,
                            type: data.type,
                            rawId: this.arrayToBase64String(new Uint8Array(data.rawId)),
                            response: {
                                clientDataJSON: this.arrayToBase64String(new Uint8Array(data.response.clientDataJSON)),
                                attestationObject: this.arrayToBase64String(new Uint8Array(data.response.attestationObject))
                            }
                        }
                    });
            },

            submit() {
                this.step = RegistrationSteps.PERSIST

                return confirmPassword()
                    .then(logAndPass('confirmed password'))
                    .then(this.saveRegistrationData)
                    .then(logAndPass('registration data saved'))
                    .then(() => this.reset())
                    .then(logAndPass('app reset'))
                    .catch(console.error.bind(this))
            },

            saveRegistrationData() {
                return finishRegistration(this.name, JSON.stringify(this.credential))
                    .then(device => this.$store.commit('addDevice', device))
                    .then(logAndPass('new device added to store'))
                    .catch(err => {
                        console.error('Error persisting webauthn registration', err);
                        throw new Error(t('twofactor_webauthn', 'Server error while trying to complete webauthn device registration'))
                    })
            },

            reset() {
                this.name = ''
                this.registrationData = {}
                this.step = RegistrationSteps.READY
            }
        }
    }
</script>

<style scoped>
    .webauthn-loading {
        display: inline-block;
        vertical-align: sub;
        margin-left: 2px;
        margin-right: 2px;
    }

    .new-webauthn-device {
        line-height: 300%;
    }
</style>