/*
 * @copyright 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Michael Blumenstein <M.Flower@gmx.de>
 * @author 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

import Axios from '@nextcloud/axios';
import {generateUrl} from '@nextcloud/router';

export async function startRegistration () {
    const url = generateUrl('/apps/twofactor_webauthn/settings/startregister');

    return Axios.post(url)
        .then(resp => resp.data);
}

export async function finishRegistration (name, data) {
    const url = generateUrl('/apps/twofactor_webauthn/settings/finishregister');

    return Axios.post(url, { name, data })
        .then(resp => resp.data);
}

export async function removeRegistration (id) {
    const url = generateUrl('/apps/twofactor_webauthn/settings/remove');

    return Axios.post(url, { id })
        .then(resp => resp.data);
}

export async function changeActivationState (id, active) {
    const url = generateUrl('/apps/twofactor_webauthn/settings/active');

    return Axios.post(url, { id, active: active ? 1 : 0 })
        .then(resp => resp.data);
}
