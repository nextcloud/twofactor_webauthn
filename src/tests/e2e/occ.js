/**
 * @copyright Copyright (c) 2024 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
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
