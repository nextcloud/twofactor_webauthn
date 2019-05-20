<?php

/**
 * @author Michael Blumenstein <M.Flower@gmx.de>
 * @copyright Copyright (c) 2019 Michael Blumenstein <M.Flower@gmx.de>
 *
 * Two-factor webauthn
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 *
 * Software Credits
 *
 * The development of this software was made possible using the following components:
 *
 * twofactor_u2f (https://github.com/nextcloud/twofactor_u2f) by Christoph Wurst (https://github.com/ChristophWurst)
 * Licensed Under: AGPL
 * This project used the great twofactor provider u2f created by Christoph Wurst as a template.
 *
 * webauthn-framework (https://github.com/web-auth/webauthn-framework) by Florent Morselli (https://github.com/Spomky)
 * Licensed Under: MIT
 * The webauthn-framework provided most of the code and documentation for implementing the webauthn authentication.
 */

namespace OCA\TwoFactorWebauthn\Controller;


require_once(__DIR__ . '/../../appinfo/autoload.php');

use OCA\TwoFactorWebauthn\Service\WebauthnManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;

class SettingsController extends Controller
{
    /**
     * @var WebauthnManager
     */
    private $manager;
    /**
     * @var IUserSession
     */
    private $userSession;

    public function __construct($AppName, IRequest $request, WebauthnManager $manager, IUserSession $userSession)
    {
        parent::__construct($AppName, $request);
        $this->manager = $manager;
        $this->userSession = $userSession;
    }

    /**
     * @NoAdminRequired
     * @PasswordConfirmationRequired
     * @UseSession
     */
    public function startRegister(): JSONResponse
    {
        return new JSONResponse($this->manager->startRegistration($this->userSession->getUser()));
    }

    /**
     * @PasswordConfirmationRequired
     * @NoAdminRequired
     */
    public function finishRegister(string $name, string $data): JSONResponse
    {
        return new JSONResponse($this->manager->finishRegister($this->userSession->getUser(), $name, $data));
    }

    /**
     * @NoAdminRequired
     * @PasswordConfirmationRequired
     */
    public function remove(string $id): JSONResponse {
        return new JSONResponse($this->manager->removeDevice($this->userSession->getUser(), $id));
    }

}
