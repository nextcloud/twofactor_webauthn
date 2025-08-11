<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TwoFactorWebauthn\Tests\Unit\Service;

use OCA\TwoFactorWebauthn\Db\PublicKeyCredentialEntity;
use OCA\TwoFactorWebauthn\Db\PublicKeyCredentialEntityMapper;
use OCA\TwoFactorWebauthn\Event\StateChanged;
use OCA\TwoFactorWebauthn\Repository\WebauthnPublicKeyCredentialSourceRepository;
use OCA\TwoFactorWebauthn\Service\WebAuthnManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\ISession;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class WebAuthnManagerTest extends TestCase {

	/** @var ISession|MockObject */
	private $session;

	/** @var WebauthnPublicKeyCredentialSourceRepository|MockObject */
	private $repository;

	/** @var PublicKeyCredentialEntityMapper|MockObject */
	private $mapper;

	/** @var IEventDispatcher|MockObject */
	private $eventDispatcher;

	/** @var WebAuthnManager */
	private $manager;

	/** @var MockObject|LoggerInterface */
	private $logger;

	protected function setUp(): void {
		parent::setUp();

		$this->session = $this->createMock(ISession::class);
		$this->repository = $this->createMock(WebauthnPublicKeyCredentialSourceRepository::class);
		$this->mapper = $this->createMock(PublicKeyCredentialEntityMapper::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->manager = new WebAuthnManager(
			$this->session,
			$this->repository,
			$this->mapper,
			$this->eventDispatcher,
			$this->logger,
		);
	}

	/**
	 * @param IUser $user
	 * @param int $nr
	 */
	private function mockRegistrations(IUser $user, $nr): void {
		$regs = [];
		for ($i = 0; $i < $nr; $i++) {
			$reg = new PublicKeyCredentialEntity();
			$regs[] = $reg;
		}
		$this->mapper->expects(self::once())
			->method('findPublicKeyCredentials')
			->with($user->getUID())
			->willReturn($regs);
	}

	public function testGetDevices(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user123');
		$this->mockRegistrations($user, 2);

		self::assertCount(2, $this->manager->getDevices($user));
	}

	public function testGetNoDevices(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user123');
		$this->mockRegistrations($user, 0);

		self::assertEmpty($this->manager->getDevices($user));
	}

	public function testDisableWebAuthn(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user123');
		$reg = new PublicKeyCredentialEntity();
		$reg->setId(420);
		$this->mapper->expects(self::once())
			->method('findById')
			->with(420, 'user123')
			->willReturn($reg);
		$this->mapper->expects(self::once())
			->method('delete')
			->with($reg);
		$this->eventDispatcher->expects(self::once())
			->method('dispatch')
			->with(
				self::equalTo(StateChanged::class),
				self::equalTo(new StateChanged($user, false))
			);

		$this->manager->removeDevice($user, 420);
	}

	public function testStartRegistrationFirstDevice(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user123');
		$user->method('getDisplayName')->willReturn('User 123');
		$this->session->expects(self::once())
			->method('set');

		$this->manager->startRegistration($user, 'my.next.cloud');
	}
}
