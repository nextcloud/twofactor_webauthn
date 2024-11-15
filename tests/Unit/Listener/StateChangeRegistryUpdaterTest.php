<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
