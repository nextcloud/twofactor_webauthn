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
