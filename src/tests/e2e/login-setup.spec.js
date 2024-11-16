/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
