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
		$reg = $this->createMock(PublicKeyCredentialEntity::class);
		$this->mapper->expects(self::once())
			->method('findPublicKeyCredential')
			->with('k1')
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

		$this->manager->removeDevice($user, 'k1');
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
