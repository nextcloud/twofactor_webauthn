/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { exec as execCallback } from 'child_process'
import util from 'util'

const exec = util.promisify(execCallback)

/**
 * Helper to execute the occ command.
 *
 * @param {string} args Arguments ot pass to occ
 * @return {Promise<{stdout: string, stderr: string}>} The result of the command
 */
export async function occ(args) {
	return exec(`php ../../occ ${args}`)
}

/**
 * Enforce twofactor auth for all users.
 *
 * @param {boolean} enforce True to enforce twofactor auth, false to disable it
 * @return {Promise<void>}
 */
export async function enforceTwofactorAuth(enforce) {
	const flag = enforce ? '--on' : '--off'
	try {
		await occ(`twofactorauth:enforce ${flag}`)
	} catch (error) {
		console.error(`Failed to enfore twofactor auth: ${error}`)
		throw error
	}
}

/**
 * Disable webauthn twofactor auth for all given users.
 *
 * @param {string[]} users List of uids to disable twofactor auth for
 * @return {Promise<void>}
 */
export async function disableTwofactorAuth(users) {
	for (const user of users) {
		try {
			await occ(`twofactorauth:disable ${user} webauthn`)
		} catch (error) {
			console.error(`Failed to disable twofactor webauthn for ${user}: ${error}`)
			throw error
		}
	}
}
