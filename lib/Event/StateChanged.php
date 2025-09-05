<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TwoFactorWebauthn\Event;

use OCP\EventDispatcher\Event;
use OCP\IUser;

final class StateChanged extends Event {

	public function __construct(
		private readonly IUser $user,
		private readonly bool $enabled,
		private readonly bool $byAdmin = false,
	) {
		parent::__construct();
	}

	public function getUser(): IUser {
		return $this->user;
	}

	public function isEnabled(): bool {
		return $this->enabled;
	}

	public function isByAdmin(): bool {
		return $this->byAdmin;
	}
}
