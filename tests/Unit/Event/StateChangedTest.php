<?php

declare(strict_types=1);

/*
 * @copyright 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Michael Blumenstein <M.Flower@gmx.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
