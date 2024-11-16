/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

/**
 *
 */
export async function startRegistration() {
	const url = generateUrl('/apps/twofactor_webauthn/settings/startregister')

	return Axios.post(url)
		.then(resp => resp.data)
}

/**
 * @param name
 * @param data
 */
export async function finishRegistration(name, data) {
	const url = generateUrl('/apps/twofactor_webauthn/settings/finishregister')

	return Axios.post(url, { name, data })
		.then(resp => resp.data)
}

/**
 * @param id
 */
export async function removeRegistration(id) {
	const url = generateUrl('/apps/twofactor_webauthn/settings/remove')

	return Axios.post(url, { id })
		.then(resp => resp.data)
}

/**
 * @param id
 * @param active
 */
export async function changeActivationState(id, active) {
	const url = generateUrl('/apps/twofactor_webauthn/settings/active')

	return Axios.post(url, { id, active: active ? 1 : 0 })
		.then(resp => resp.data)
}
