<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TwoFactorWebauthn\Tests\Unit\Event;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\TwoFactorWebauthn\Event\StateChanged;
use OCP\IUser;

class StateChangedTest extends TestCase {
	public function testEnabledState(): void {
		$user = $this->createMock(IUser::class);

		$event = new StateChanged($user, true);

		self::assertTrue($event->isEnabled());
		self::assertSame($user, $event->getUser());
	}

	public function testDisabledState(): void {
		$user = $this->createMock(IUser::class);

		$event = new StateChanged($user, false);

		self::assertFalse($event->isEnabled());
		self::assertSame($user, $event->getUser());
	}
}
