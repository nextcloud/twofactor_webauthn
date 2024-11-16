<?php

declare(strict_types = 1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TwoFactorWebauthn\Controller;

use OCA\TwoFactorWebauthn\Service\WebAuthnManager;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Authentication\TwoFactorAuth\ALoginSetupController;
use OCP\IRequest;
use OCP\IUserSession;

class SettingsController extends ALoginSetupController {

	/** @var WebAuthnManager */
	private $manager;

	/** @var IUserSession */
	private $userSession;

	public function __construct(string $appName, IRequest $request, WebAuthnManager $manager, IUserSession $userSession) {
		parent::__construct($appName, $request);
		$this->manager = $manager;
		$this->userSession = $userSession;
	}

	/**
	 * @NoAdminRequired
	 * @PasswordConfirmationRequired
	 * @UseSession
	 */
	public function startRegister(): JSONResponse {
		return new JSONResponse($this->manager->startRegistration($this->userSession->getUser(), $this->request->getServerHost()));
	}

	/**
	 * @NoAdminRequired
	 * @PasswordConfirmationRequired
	 *
	 * @param string $name
	 * @param string $data
	 */
	public function finishRegister(string $name, string $data): JSONResponse {
		return new JSONResponse(
			$this->manager->finishRegister(
				$this->userSession->getUser(),
				$name,
				$data
			)
		);
	}

	/**
	 * @NoAdminRequired
	 * @PasswordConfirmationRequired
	 */
	public function remove(int $id): JSONResponse {
		$this->manager->removeDevice($this->userSession->getUser(), $id);
		return new JSONResponse([]);
	}

	/**
	 * @NoAdminRequired
	 * @PasswordConfirmationRequired
	 */
	public function changeActivationState(int $id, bool $active): JSONResponse {
		$this->manager->changeActivationState($this->userSession->getUser(), $id, $active);
		return new JSONResponse([]);
	}
}
