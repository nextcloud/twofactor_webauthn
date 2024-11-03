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

import { test, expect } from '@playwright/test'
import { randomBytes } from 'crypto'
import { createVirtualAuthenticator } from './virtualAuthenticator.js'
import { login } from './login.js'
import { disableTwofactorAuth, enforceTwofactorAuth } from './occ.js'

const randomHex = () => randomBytes(16).toString('hex')

test.beforeAll(async () => {
	await enforceTwofactorAuth(false)
})

test.beforeEach(async ({ page }) => {
	await createVirtualAuthenticator(page)
	await disableTwofactorAuth(['admin'])
	await login(page)
})

test('add device and login', async ({ page }) => {
	const keyName = randomHex()

	await page.goto('./index.php/settings/user/security')
	await page.getByRole('button', { name: 'Add security key' }).click()
	await page.getByPlaceholder('Name your security key').fill(keyName)
	await page.getByRole('button', { name: 'Add', exact: true }).click()

	// Device should be added to the list
	await expect(page.locator('#two-factor-auth')).toContainText(keyName)

	// Nextcloud 28 and above: await page.getByLabel('Settings menu').click()
	await page.locator('[aria-controls="header-menu-user-menu"]').click()
	await page.getByRole('link', { name: 'Log out' }).click()
	await page.locator('#user').fill('admin')
	await page.locator('#password').fill('admin')
	await page.locator('#password').press('Enter')

	// Wait for login to finish
	await page.waitForURL('**/apps/**')
})

test('add device and remove it again', async ({ page }) => {
	const keyName = randomHex()

	await page.goto('./index.php/settings/user/security')
	await page.getByLabel('Settings menu').click()
	await page.getByRole('link', { name: 'Personal settings' }).click()
	await page.getByLabel('Personal').getByRole('link', { name: 'Security' }).click()

	// Add a new device
	await page.getByRole('button', { name: 'Add security key' }).click()
	await page.getByPlaceholder('Name your security key').fill(keyName)
	await page.getByRole('button', { name: 'Add', exact: true }).click()

	// Device should be added to the list
	await expect(page.locator('#two-factor-auth')).toContainText(keyName)

	// Remove the device
	await page.locator(`:text-matches("${keyName}") button[aria-label="Actions"]`).click()
	await page.getByRole('menuitem', { name: 'Remove' }).click()

	// Device should be removed from the list
	await expect(page.locator('#two-factor-auth')).not.toContainText(keyName)
})
