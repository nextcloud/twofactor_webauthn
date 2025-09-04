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
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUser;
use OCP\Security\ISecureRandom;
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

	private IRequest&MockObject $request;
	private ISecureRandom&MockObject $random;
	private ITimeFactory&MockObject $time;

	protected function setUp(): void {
		parent::setUp();

		$this->session = $this->createMock(ISession::class);
		$this->repository = $this->createMock(WebauthnPublicKeyCredentialSourceRepository::class);
		$this->mapper = $this->createMock(PublicKeyCredentialEntityMapper::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->request = $this->createMock(IRequest::class);
		$this->random = $this->createMock(ISecureRandom::class);
		$this->time = $this->createMock(ITimeFactory::class);

		$this->manager = new WebAuthnManager(
			$this->session,
			$this->repository,
			$this->mapper,
			$this->eventDispatcher,
			$this->logger,
			$this->request,
			$this->random,
			$this->time,
		);
	}

	private function mockRegistrations(IUser $user, int $nr): void {
		$regs = [];
		for ($i = 0; $i < $nr; $i++) {
			$reg = new PublicKeyCredentialEntity();
			$reg->setId($i);
			$reg->setPublicKeyCredentialId("credential-id-$i");
			$reg->setName("key-$i");
			$reg->setUserHandle($user->getUID());
			$reg->setActive(true);
			$reg->setCreatedAt(null);
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
			->method('dispatchTyped')
			->with(
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
