<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TwoFactorWebauthn\Provider;

use OCA\TwoFactorWebauthn\AppInfo\Application;
use OCA\TwoFactorWebauthn\Model\Device;
use OCA\TwoFactorWebauthn\Service\WebAuthnManager;
use OCA\TwoFactorWebauthn\Settings\Personal;
use OCP\AppFramework\Services\IInitialState;
use OCP\Authentication\TwoFactorAuth\IActivatableAtLogin;
use OCP\Authentication\TwoFactorAuth\IDeactivatableByAdmin;
use OCP\Authentication\TwoFactorAuth\ILoginSetupProvider;
use OCP\Authentication\TwoFactorAuth\IPersonalProviderSettings;
use OCP\Authentication\TwoFactorAuth\IProvider;
use OCP\Authentication\TwoFactorAuth\IProvidesIcons;
use OCP\Authentication\TwoFactorAuth\IProvidesPersonalSettings;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Template;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class WebAuthnProvider implements IProvider, IProvidesIcons, IProvidesPersonalSettings, IDeactivatableByAdmin, IActivatableAtLogin {

	/** @var IL10N */
	private $l10n;

	/** @var WebAuthnManager */
	private $manager;

	/** @var IInitialState */
	private $initialState;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var IRequest */
	private $request;

	/** @var ContainerInterface */
	private $container;

	public function __construct(IL10N $l10n,
		WebAuthnManager $manager,
		IInitialState $initialState,
		IURLGenerator $urlGenerator,
		IRequest $request,
		ContainerInterface $container) {
		$this->l10n = $l10n;
		$this->manager = $manager;
		$this->initialState = $initialState;
		$this->urlGenerator = $urlGenerator;
		$this->request = $request;
		$this->container = $container;
	}

	/**
	 * Get unique identifier of this 2FA provider
	 */
	public function getId(): string {
		return 'webauthn';
	}

	/**
	 * Get the display name for selecting the 2FA provider
	 */
	public function getDisplayName(): string {
		return $this->l10n->t('Security key');
	}

	/**
	 * Get the description for selecting the 2FA provider
	 */
	public function getDescription(): string {
		return $this->l10n->t('Use WebAuthn for second factor authentication');
	}

	/**
	 * Get the template for rending the 2FA provider view
	 */
	public function getTemplate(IUser $user): Template {
		$publicKey = $this->manager->startAuthenticate($user, $this->request->getServerHost());
		$this->initialState->provideInitialState('credential-request-options', $publicKey);
		return new Template('twofactor_webauthn', 'challenge');
	}

	/**
	 * Verify the given challenge
	 */
	public function verifyChallenge(IUser $user, string $challenge): bool {
		return $this->manager->finishAuthenticate($user, $challenge);
	}

	/**
	 * Decides whether 2FA is enabled for the given user
	 */
	public function isTwoFactorAuthEnabledForUser(IUser $user): bool {
		$devices = array_filter(
			$this->manager->getDevices($user),
			static fn (Device $device) => $device->isActive(),
		);
		return count($devices) > 0;
	}

	public function getPersonalSettings(IUser $user): IPersonalProviderSettings {
		$this->initialState->provideInitialState('devices', $this->manager->getDevices($user));

		return new Personal();
	}

	public function getLightIcon(): String {
		return $this->urlGenerator->imagePath(Application::APP_ID, 'app.svg');
	}

	public function getDarkIcon(): String {
		return $this->urlGenerator->imagePath(Application::APP_ID, 'app-dark.svg');
	}

	/**
	 * Disable this provider for the given user.
	 *
	 * @param IUser $user the user to deactivate this provider for
	 */
	public function disableFor(IUser $user) {
		$this->manager->deactivateAllDevices($user);
	}

	/**
	 * Enable setup during login.
	 *
	 * @param IUser $user
	 * @return ILoginSetupProvider
	 *
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function getLoginSetup(IUser $user): ILoginSetupProvider {
		return $this->container->get(WebAuthnLoginProvider::class);
	}
}
