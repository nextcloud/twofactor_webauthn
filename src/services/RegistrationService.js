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

import Axios from 'nextcloud-axios'
import {generateUrl} from 'nextcloud-server/dist/router'

export function startRegistration () {
    const url = generateUrl('/apps/twofactor_webauthn/settings/startregister');

    return Axios.post(url)
        .then(resp => resp.data);
}

export function finishRegistration (name, data) {
    const url = generateUrl('/apps/twofactor_webauthn/settings/finishregister');

    return Axios.post(url, { name, data })
        .then(resp => resp.data);
}

export function removeRegistration (id) {
    const url = generateUrl('/apps/twofactor_webauthn/settings/remove')
    const data = {
        id
    }

    return Axios.post(url, data)
        .then(resp => resp.data)
}