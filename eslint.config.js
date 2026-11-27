/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { recommended } from '@nextcloud/eslint-config'

export default [
	...recommended,
	{
		files: ['src/tests/**'],
		languageOptions: {
			globals: {
				describe: 'readonly',
				it: 'readonly',
				before: 'readonly',
				after: 'readonly',
				beforeEach: 'readonly',
				afterEach: 'readonly',
				expect: 'readonly',
			},
		},
	},
	{
		rules: {
			'no-console': 'off',
			'vue/multi-word-component-names': 'off',
		},
	},
]
