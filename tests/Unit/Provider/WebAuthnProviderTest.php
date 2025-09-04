<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TwoFactorWebauthn\Tests\Unit\Provider;

use OCA\TwoFactorWebauthn\Model\Device;
use OCA\TwoFactorWebauthn\Provider\WebAuthnLoginProvider;
use OCA\TwoFactorWebauthn\Provider\WebAuthnProvider;
use OCA\TwoFactorWebauthn\Service\WebAuthnManager;
use OCA\TwoFactorWebauthn\Settings\Personal;
use OCP\AppFramework\Services\IInitialState;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Template;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Webauthn\PublicKeyCredentialRequestOptions;

class WebAuthnProviderTest extends TestCase {

	/** @var IL10N|MockObject */
	private $l10n;

	/** @var WebAuthnManager|MockObject */
	private $manager;

	/** @var IURLGenerator|MockObject */
	private $urlGenerator;

	/** @var IInitialState|MockObject */
	private $initialState;

	/** @var IRequest|MockObject */
	private $request;

	/** @var WebAuthnProvider */
	private $provider;

	/** @var ContainerInterface */
	private $container;

	protected function setUp(): void {
		parent::setUp();

		$this->l10n = $this->createMock(IL10N::class);
		$this->manager = $this->createMock(WebAuthnManager::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->initialState = $this->createMock(IInitialState::class);
		$this->request = $this->createMock(IRequest::class);
		$this->container = $this->createMock(ContainerInterface::class);

		$this->provider = new WebAuthnProvider(
			$this->l10n,
			$this->manager,
			$this->initialState,
			$this->urlGenerator,
			$this->request,
			$this->container,
		);
	}

	public function testGetId(): void {
		self::assertSame('webauthn', $this->provider->getId());
	}

	public function testGetDisplayName(): void {
		$this->l10n->expects(self::once())
			->method('t')
			->with('Security key')
			->willReturn('translated');

		$displayName = $this->provider->getDisplayName();

		self::assertSame('translated', $displayName);
	}

	public function testGetDescription(): void {
		$this->l10n->expects(self::once())
			->method('t')
			->with('Use WebAuthn for second factor authentication')
			->willReturn('translated');

		self::assertSame('translated', $this->provider->getDescription());
	}

	public function testGetTemplate(): void {
		$user = $this->createMock(IUser::class);
		$key = new PublicKeyCredentialRequestOptions('challenge');
		$serverHost = 'my.next.cloud';
		$this->request->expects(self::once())
			->method('getServerHost')
			->willReturn($serverHost);
		$this->manager->expects(self::once())
			->method('startAuthenticate')
			->with($user, $serverHost)
			->willReturn($key);
		$this->initialState->expects(self::once())
			->method('provideInitialState')
			->with('credential-request-options', $key);

		$tmpl = new Template('twofactor_webauthn', 'challenge');

		$actual = $this->provider->getTemplate($user);
		self::assertEquals($tmpl, $actual);
		$actual->fetchPage();
	}

	public function testVerifyChallenge(): void {
		$user = $this->createMock(IUser::class);
		$val = '123';

		$this->manager->expects(self::once())
			->method('finishAuthenticate')
			->willReturn(false);

		self::assertFalse($this->provider->verifyChallenge($user, $val));
	}

	public function testIsTwoFactorAuthEnabledForUser(): void {
		$user = $this->createMock(IUser::class);
		$this->manager->expects(self::once())
			->method('getDevices')
			->willReturn([
				new Device(1, 'k1', 'n1', null, true),
			]);

		self::assertTrue($this->provider->isTwoFactorAuthEnabledForUser($user));
	}

	public function testIsTwoFactorAuthDisabledForUserBecauseDisabledDevice(): void {
		$user = $this->createMock(IUser::class);
		$this->manager->expects(self::once())
			->method('getDevices')
			->willReturn([
				new Device(1, 'k1', 'n1', null, false),
			]);

		self::assertFalse($this->provider->isTwoFactorAuthEnabledForUser($user));
	}

	public function testIsTwoFactorAuthDisabledForUserBecauseNoDevice(): void {
		$user = $this->createMock(IUser::class);
		$devices = [];

		$this->manager->expects(self::once())
			->method('getDevices')
			->willReturn($devices);

		self::assertFalse($this->provider->isTwoFactorAuthEnabledForUser($user));
	}

	public function testGetGetLightIcon(): void {
		$this->urlGenerator->expects(self::once())
			->method('imagePath')
			->with('twofactor_webauthn', 'app.svg')
			->willReturn('/apps/twofactor_webauthn/img/app.svg');

		$icon = $this->provider->getLightIcon();

		self::assertEquals('/apps/twofactor_webauthn/img/app.svg', $icon);
	}

	public function testGetDarkIcon(): void {
		$this->urlGenerator->expects(self::once())
			->method('imagePath')
			->with('twofactor_webauthn', 'app-dark.svg')
			->willReturn('/apps/twofactor_webauthn/img/app-dark.svg');

		$icon = $this->provider->getDarkIcon();

		self::assertEquals('/apps/twofactor_webauthn/img/app-dark.svg', $icon);
	}

	public function testGetPersonalSettings(): void {
		$expected = new Personal();
		$this->initialState->expects(self::once())
			->method('provideInitialState')
			->with(
				'devices',
				['my', 'devices']
			);
		$user = $this->createMock(IUser::class);
		$this->manager->method('getDevices')
			->willReturn(['my', 'devices']);

		$settings = $this->provider->getPersonalSettings($user);

		self::assertEquals($expected, $settings);
	}

	public function testDisable(): void {
		$user = $this->createMock(IUser::class);
		$this->manager->expects(self::once())
			->method('deactivateAllDevices')
			->with($user);

		$this->provider->disableFor($user);
	}

	public function testGetLoginSetupProvider(): void {
		$user = $this->createMock(IUser::class);
		$loginProvider = $this->createMock(WebAuthnLoginProvider::class);
		$this->container->expects($this->once())
			->method('get')
			->with(WebAuthnLoginProvider::class)
			->willReturn($loginProvider);

		$result = $this->provider->getLoginSetup($user);

		$this->assertSame($loginProvider, $result);
	}
}
