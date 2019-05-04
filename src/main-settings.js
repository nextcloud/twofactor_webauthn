import store from './store'
import Vue from 'vue'

import Nextcloud from './mixins/Nextcloud'

Vue.mixin(Nextcloud);

const initialStateElement = document.getElementById('twofactor-webauthn-initial-state');
if (initialStateElement) {
    const devices = JSON.parse(initialStateElement.value)
    devices.sort()
    devices.sort((d1, d2) => {
        if (!d1.name) {
            return 1
        } else if (!d2.name) {
            return -1
        } else {
            return d1.name.localeCompare(d2.name)
        }
    })
    store.replaceState({
        devices
    })
}

import PersonalSettings from './components/PersonalSettings';

const View = Vue.extend(PersonalSettings);
new View({
    propsData: {
        httpWarning: document.location.protocol !== 'https:',
    },
    store,
}).$mount('#twofactor-webauthn-settings');
