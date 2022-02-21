<?php

declare(strict_types = 1);

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
	 *
	 * @param string $id
	 */
	public function remove(string $id): JSONResponse {
		return new JSONResponse($this->manager->removeDevice($this->userSession->getUser(), $id));
	}

	public function changeActivationState(string $id, bool $active): JSONResponse {
		return new JSONResponse($this->manager->changeActivationState($this->userSession->getUser(), $id, $active));
	}
}
