<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TwoFactorWebauthn\AppInfo;

use OCA\TwoFactorWebauthn\Event\StateChanged;
use OCA\TwoFactorWebauthn\Listener\StateChangeActivity;
use OCA\TwoFactorWebauthn\Listener\StateChangeRegistryUpdater;
use OCA\TwoFactorWebauthn\Listener\UserDeleted;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\User\Events\UserDeletedEvent;

require_once __DIR__ . '/../../vendor/autoload.php';

class Application extends App implements IBootstrap {
	public const APP_ID = 'twofactor_webauthn';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerEventListener(StateChanged::class, StateChangeActivity::class);
		$context->registerEventListener(StateChanged::class, StateChangeRegistryUpdater::class);
		$context->registerEventListener(UserDeletedEvent::class, UserDeleted::class);
	}

	public function boot(IBootContext $context): void {
	}
}
