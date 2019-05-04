<?php


namespace OCA\TwoFactorWebauthn\Provider;

require_once(__DIR__ . '/../../appinfo/autoload.php');

use OCA\TwoFactorWebauthn\Service\WebauthnManager;
use OCA\TwoFactorWebauthn\Settings\Personal;
use OCP\Authentication\TwoFactorAuth\IActivatableByAdmin;
use OCP\Authentication\TwoFactorAuth\IDeactivatableByAdmin;
use OCP\Authentication\TwoFactorAuth\IPersonalProviderSettings;
use OCP\Authentication\TwoFactorAuth\IProvider;
use OCP\Authentication\TwoFactorAuth\IProvidesIcons;
use OCP\Authentication\TwoFactorAuth\IProvidesPersonalSettings;
use OCP\IUser;
use OCP\Template;

class WebauthnProvider implements IProvider, IProvidesPersonalSettings, IProvidesIcons, IDeactivatableByAdmin, IActivatableByAdmin
{
    /**
     * @var WebauthnManager
     */
    private $manager;


    /**
     * WebauthnProvider constructor.
     */
    public function __construct(WebauthnManager $manager)
    {
        $this->manager = $manager;
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
        $publicKey = $this->manager->startAuthenticate($user);

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
        return 'Webauthn Devices';
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
        return 'Use Webauthn for second factor authentication';
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
        return null;
    }

    /**
     * Enable this provider for the given user.
     *
     * @param IUser $user the user to activate this provider for
     *
     * @return void
     *
     * @since 15.0.0
     */
    public function enableFor(IUser $user)
    {
        return null;
    }
}