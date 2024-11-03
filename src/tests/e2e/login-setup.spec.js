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

import { test } from '@playwright/test'
import { createVirtualAuthenticator } from './virtualAuthenticator.js'
import { disableTwofactorAuth, enforceTwofactorAuth } from './occ.js'

test.beforeAll(async () => {
	await enforceTwofactorAuth(true)
})

test.beforeEach(async ({ page }) => {
	await disableTwofactorAuth(['admin'])
	await createVirtualAuthenticator(page)
})

test('setup and login', async ({ page }) => {
	await page.goto('./index.php/login')
	await page.locator('#user').fill('admin')
	await page.locator('#password').fill('admin')
	await page.locator('#password').press('Enter')
	await page.getByRole('link', { name: 'Security key Use WebAuthn for' }).click()
	await page.getByRole('button', { name: 'Add security key' }).click()
	await page.getByPlaceholder('Name your security key').fill('key')
	await page.getByRole('button', { name: 'Add' }).click()
	await page.getByRole('link', { name: 'Security key Use WebAuthn for' }).click()

	// Wait for log in to finish
	await page.waitForURL('**/apps/**')
})
