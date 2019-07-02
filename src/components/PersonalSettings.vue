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
        <p v-if="devices.length === 0">{{ t('twofactor_webauthn', 'No Webauthn devices configured. You are not using Webauthn as second factor at the moment.') }}</p>
        <p v-else>{{ t('twofactor_webauthn', 'The following devices are configured for Webauthn second-factor authentication:') }}</p>
        <Device v-for="device in devices"
                :key="device.id"
                :id="device.id"
                :name="device.name"/>

        <AddDeviceDialog :httpWarning="httpWarning"/>
        <p v-if="notSupported">
            {{ t('twofactor_webauthn', 'Your browser does not support Webauthn.') }}
        </p>
        <p v-if="httpWarning"
           id="u2f-http-warning">
            {{ t('twofactor_webauthn', 'You are accessing this site via an insecure connection. Browsers might therefore refuse the Webauthn authentication.') }}
        </p>
<!--        <button id="register-webauthn-device"-->
<!--                v-on:click="registerWebauthnDevice">Webauthn Device register-->
<!--        </button>-->
    </div>
</template>

<script>

    import AddDeviceDialog from './AddDeviceDialog'
    import Device from './Device'

    export default {
        name: "PersonalSettings",
        data() {
            return {
                notSupported: typeof(PublicKeyCredential) === 'undefined'
            }
        },
        props: {
            httpWarning: Boolean
        },
        components: {
            AddDeviceDialog,
            Device
        },
        computed: {
            devices() {
                return this.$store.state.devices
            }
        }
    }
</script>

<style scoped>
</style>
