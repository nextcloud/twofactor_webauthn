<?php

declare(strict_types=1);

/*
 * @copyright 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Michael Blumenstein <M.Flower@gmx.de>
 * @author Richard Steinmetz <richard@steinmetz.cloud>
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

namespace OCA\TwoFactorWebauthn\Provider;

use OCA\TwoFactorWebauthn\AppInfo\Application;
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

	/** @var ContainerInterface  */
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
		$devices = array_filter($this->manager->getDevices($user), function ($device) {
			return $device['active'] === true;
		});
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
