<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TwoFactorWebauthn\Event;

use OCP\IUser;

class DisabledByAdmin extends StateChanged {
	public function __construct(IUser $user) {
		parent::__construct($user, false);
	}
}
