/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCurrentUser } from '@nextcloud/auth'
import { getLoggerBuilder } from '@nextcloud/logger'

const builder = getLoggerBuilder().setApp('twofactor_webauthn')

const user = getCurrentUser()
if (user !== null) {
	builder.setUid(user.uid)
}

export default builder.build()
