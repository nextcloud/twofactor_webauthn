<?php

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
        return new JSONResponse($this->manager->finishRegister($name, $data));
    }

    /**
     * @NoAdminRequired
     * @PasswordConfirmationRequired
     */
    public function remove(string $id): JSONResponse {
        return new JSONResponse($this->manager->removeDevice($this->userSession->getUser(), $id));
    }

}
