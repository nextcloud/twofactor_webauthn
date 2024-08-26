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
namespace OCA\TwoFactorWebauthn\Tests\Unit\Controller;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\TwoFactorWebauthn\Controller\SettingsController;
use OCA\TwoFactorWebauthn\Service\WebAuthnManager;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

class SettingsControllerTest extends TestCase {

	/** @var IRequest|MockObject */
	private $request;

	/** @var WebAuthnManager|MockObject */
	private $webauthnManager;

	/** @var IUserSession|MockObject */
	private $userSession;

	/** @var SettingsController */
	private $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->webauthnManager = $this->createMock(WebAuthnManager::class);
		$this->userSession = $this->createMock(IUserSession::class);

		$this->controller = new SettingsController('twofactor_webauthn', $this->request, $this->webauthnManager, $this->userSession);
	}

	public function testStartRegister(): void {
		$user = $this->createMock(IUser::class);
		$this->userSession->expects(self::once())
			->method('getUser')
			->willReturn($user);
		$publicKeyCredentialCreationOptions = new PublicKeyCredentialCreationOptions(
			new PublicKeyCredentialRpEntity('relying_party'),
			new PublicKeyCredentialUserEntity('user', 'user_id', 'User'),
			'challenge',
		);
		$this->webauthnManager->expects(self::once())
			->method('startRegistration')
			->with(self::equalTo($user))
			->willReturn($publicKeyCredentialCreationOptions);

		$response = $this->controller->startRegister();

		self::assertEquals(new JSONResponse($publicKeyCredentialCreationOptions), $response);
	}

	public function testFinishRegister(): void {
		$user = $this->createMock(IUser::class);
		$this->userSession->expects(self::once())
			->method('getUser')
			->willReturn($user);
		$data = 'some data';

		$this->webauthnManager->expects(self::once())
			->method('finishRegister')
			->with(
				self::equalTo($user),
				self::equalTo('my key'),
				self::equalTo($data))
			->willReturn([]);

		$resp = $this->controller->finishRegister('my key', $data);

		self::assertEquals(new JSONResponse([]), $resp);
	}
}
