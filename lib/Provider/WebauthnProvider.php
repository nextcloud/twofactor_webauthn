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

namespace OCA\TwoFactorWebauthn\Provider;

require_once(__DIR__ . '/../../appinfo/autoload.php');

use OCA\TwoFactorWebauthn\Service\WebauthnManager;
use OCA\TwoFactorWebauthn\Settings\Personal;
use OCP\Authentication\TwoFactorAuth\IDeactivatableByAdmin;
use OCP\Authentication\TwoFactorAuth\IPersonalProviderSettings;
use OCP\Authentication\TwoFactorAuth\IProvider;
use OCP\Authentication\TwoFactorAuth\IProvidesIcons;
use OCP\Authentication\TwoFactorAuth\IProvidesPersonalSettings;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\Template;

class WebauthnProvider implements IProvider, IProvidesPersonalSettings, IProvidesIcons, IDeactivatableByAdmin
{
    /**
     * @var WebauthnManager
     */
    private $manager;
    /**
     * @var IL10N
     */
    private $l10n;
    /**
     * @var IRequest
     */
    private $request;


    /**
     * WebauthnProvider constructor.
     */
    public function __construct(IL10N $l10n, IRequest $request, WebauthnManager $manager)
    {
        $this->manager = $manager;
        $this->l10n = $l10n;
        $this->request = $request;
    }


    /**
     * Decides whether 2FA is enabled for the given user
     *
     * @param IUser $user
     * @return bool
     * @since 9.1.0
     *
     */
    public function isTwoFactorAuthEnabledForUser(IUser $user): bool
    {
        $devices = $this->manager->getDevices($user);
        return count($devices) > 0;
    }

    /**
     * Get the template for rending the 2FA provider view
     *
     * @param IUser $user
     * @return Template
     * @since 9.1.0
     *
     */
    public function getTemplate(IUser $user): Template
    {
        $publicKey = $this->manager->startAuthenticate($user, $this->request->getServerHost());

        $tmpl = new Template('twofactor_webauthn', 'challenge');
        $tmpl->assign('publicKey', $publicKey);
        return $tmpl;
    }

    /**
     * Verify the given challenge
     *
     * @param IUser $user
     * @param string $challenge
     * @return bool
     * @since 9.1.0
     *
     */
    public function verifyChallenge(IUser $user, string $challenge): bool
    {
        return $this->manager->finishAuthenticate($user, $challenge);
    }

    /**
     * Get unique identifier of this 2FA provider
     *
     * @return string
     * @since 9.1.0
     *
     */
    public function getId(): string
    {
        return 'twofactor_webauthn';
    }

    /**
     * Get the display name for selecting the 2FA provider
     *
     * Example: "Email"
     *
     * @return string
     * @since 9.1.0
     *
     */
    public function getDisplayName(): string
    {
        return $this->l10n->t('Webauthn Devices');
    }

    /**
     * Get the description for selecting the 2FA provider
     *
     * Example: "Get a token via e-mail"
     *
     * @return string
     * @since 9.1.0
     *
     */
    public function getDescription(): string
    {
        return $this->l10n->t('Use Webauthn for second factor authentication');
    }

    /**
     * @param IUser $user
     *
     * @return IPersonalProviderSettings
     *
     * @since 15.0.0
     */
    public function getPersonalSettings(IUser $user): IPersonalProviderSettings
    {
        return new Personal($this->manager->getDevices($user));
    }

    /**
     * Get the path to the light (white) icon of this provider
     *
     * @return String
     *
     * @since 15.0.0
     */
    public function getLightIcon(): String
    {
        return image_path('twofactor_webauthn', 'app.svg');
    }

    /**
     * Get the path to the dark (black) icon of this provider
     *
     * @return String
     *
     * @since 15.0.0
     */
    public function getDarkIcon(): String
    {
        return image_path('twofactor_webauthn', 'app-dark.svg');
    }

    /**
     * Disable this provider for the given user.
     *
     * @param IUser $user the user to deactivate this provider for
     *
     * @return void
     *
     * @since 15.0.0
     */
    public function disableFor(IUser $user)
    {
        return $this->manager->removeAllDevices($user);
    }
    
}