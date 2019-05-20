/**
 * @author Michael Blumenstein <M.Flower@gmx.de>
 * @copyright Copyright (c) 2019 Michael Blumenstein <M.Flower@gmx.de>
 *
 * Two-factor webauthn
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 *
 * Software Credits
 *
 * The development of this software was made possible using the following components:
 *
 * twofactor_u2f (https://github.com/nextcloud/twofactor_u2f) by Christoph Wurst (https://github.com/ChristophWurst)
 * Licensed Under: AGPL
 * This project used the great twofactor provider u2f created by Christoph Wurst as a template.
 *
 * webauthn-framework (https://github.com/web-auth/webauthn-framework) by Florent Morselli (https://github.com/Spomky)
 * Licensed Under: MIT
 * The webauthn-framework provided most of the code and documentation for implementing the webauthn authentication.
 */

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
