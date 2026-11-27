/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { shallowMount } from '@vue/test-utils'
import Challenge from '../../../components/Challenge.vue'
import Nextcloud from '../../../mixins/Nextcloud.js'

describe('Challenge', () => {
	it('receives credentialRequestOptions as a prop', () => {
		const credentialRequestOptions = {
			allowCredentials: [{ id: 'abc', transports: ['usb'] }],
		}

		const challenge = shallowMount(Challenge, {
			global: {
				mixins: [Nextcloud],
			},
			props: {
				credentialRequestOptions,
			},
		})

		expect(challenge.vm.credentialRequestOptions).to.deep.equal(credentialRequestOptions)
	})
})
