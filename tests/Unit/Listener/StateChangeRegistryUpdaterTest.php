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

namespace OCA\TwoFactorWebauthn\Tests\Unit\Listener;

use OCA\TwoFactorWebauthn\Event\StateChanged;
use OCA\TwoFactorWebauthn\Listener\StateChangeRegistryUpdater;
use OCA\TwoFactorWebauthn\Provider\WebAuthnProvider;
use OCA\TwoFactorWebauthn\Service\WebAuthnManager;
use OCP\Authentication\TwoFactorAuth\IRegistry;
use OCP\EventDispatcher\Event;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StateChangeRegistryUpdaterTest extends TestCase {

	/** @var IRegistry|MockObject */
	private $providerRegistry;

	/** @var WebAuthnManager|MockObject */
	private $manager;

	/** @var WebAuthnProvider|MockObject */
	private $provider;

	/** @var StateChangeRegistryUpdater */
	private $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->providerRegistry = $this->createMock(IRegistry::class);
		$this->manager = $this->createMock(WebAuthnManager::class);
		$this->provider = $this->createMock(WebAuthnProvider::class);

		$this->listener = new StateChangeRegistryUpdater($this->providerRegistry, $this->manager, $this->provider);
	}

	public function testHandleGenericEvent(): void {
		$event = new Event();
		$this->providerRegistry->expects(self::never())
			->method('enableProviderFor');
		$this->providerRegistry->expects(self::never())
			->method('disableProviderFor');

		$this->listener->handle($event);
	}

	public function testHandleEnableFirstDevice(): void {
		$user = $this->createMock(IUser::class);
		$event = new StateChanged($user, true);
		$this->manager->expects(self::once())
			->method('getDevices')
			->willReturn([
				[
					'id' => 1,
					'name' => 'utf1',
					'active' => true,
				],
			]);
		$this->providerRegistry->expects(self::once())
			->method('enableProviderFor')
			->with(
				$this->provider,
				$user
			);

		$this->listener->handle($event);
	}

	public function testHandleDisableLastDevice(): void {
		$user = $this->createMock(IUser::class);
		$event = new StateChanged($user, false);
		$this->manager->expects(self::once())
			->method('getDevices')
			->willReturn([]);
		$this->providerRegistry->expects(self::once())
			->method('disableProviderFor')
			->with(
				$this->provider,
				$user
			);

		$this->listener->handle($event);
	}

	public function testHandleDisableWithRemainingDevices(): void {
		$user = $this->createMock(IUser::class);
		$event = new StateChanged($user, false);
		$this->manager->expects(self::once())
			->method('getDevices')
			->willReturn([
				[
					'id' => 2,
					'name' => 'utf2',
					'active' => true,
				],
			]);
		$this->providerRegistry->expects(self::never())
			->method('disableProviderFor')
			->with(
				$this->provider,
				$user
			);

		$this->listener->handle($event);
	}
}
