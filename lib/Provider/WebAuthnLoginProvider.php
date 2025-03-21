<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TwoFactorWebauthn\Provider;

use OCA\TwoFactorWebauthn\AppInfo\Application;
use OCP\Authentication\TwoFactorAuth\ILoginSetupProvider;
use OCP\Template;
use OCP\Template\ITemplate;

class WebAuthnLoginProvider implements ILoginSetupProvider {
	public function getBody(): ITemplate {
		return new Template(Application::APP_ID, 'login-setup');
	}
}
