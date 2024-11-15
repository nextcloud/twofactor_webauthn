/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
