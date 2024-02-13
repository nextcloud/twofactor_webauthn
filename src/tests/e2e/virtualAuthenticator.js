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

/**
 * Create a new virtual authenticator for a single test.
 * This method is best used in a beforeEach hook.
 *
 * @param {import("@playwright/test").Page} page The page object to create the authenticator for
 * @return {Promise<void>}
 */
export async function createVirtualAuthenticator(page) {
	const cdpSession = await page.context().newCDPSession(page)
	await cdpSession.send('WebAuthn.enable')
	await cdpSession.send('WebAuthn.addVirtualAuthenticator', {
		options: {
			protocol: 'ctap2',
			ctap2Version: 'ctap2_1',
			hasUserVerification: true,
			transport: 'usb',
			automaticPresenceSimulation: true,
			isUserVerified: true,
		},
	})
}
