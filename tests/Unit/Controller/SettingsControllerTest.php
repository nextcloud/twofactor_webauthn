<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\TwoFactorWebauthn\Tests\Unit\Controller;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\TwoFactorWebauthn\Controller\SettingsController;
use OCA\TwoFactorWebauthn\Model\Device;
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

		$device = new Device(1, 'key-1', 'my key', null, true);
		$this->webauthnManager->expects(self::once())
			->method('finishRegister')
			->with(
				self::equalTo($user),
				self::equalTo('my key'),
				self::equalTo($data))
			->willReturn($device);

		$resp = $this->controller->finishRegister('my key', $data);

		self::assertEquals(new JSONResponse($device), $resp);
	}
}
