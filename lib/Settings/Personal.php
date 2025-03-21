<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TwoFactorWebauthn\Settings;

use OCP\Authentication\TwoFactorAuth\IPersonalProviderSettings;
use OCP\Template;
use OCP\Template\ITemplate;

class Personal implements IPersonalProviderSettings {
	public function getBody(): ITemplate {
		return new Template('twofactor_webauthn', 'personal');
	}
}
