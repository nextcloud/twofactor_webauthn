/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
