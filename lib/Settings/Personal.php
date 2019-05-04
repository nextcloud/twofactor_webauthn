<?php

namespace OCA\TwoFactorWebauthn\Settings;

use OCP\Authentication\TwoFactorAuth\IPersonalProviderSettings;
use OCP\Template;

class Personal implements IPersonalProviderSettings
{

    /** @var array */
    private $devices;

    public function __construct(array $devices) {
        $this->devices = $devices;
    }

    /**
     * @return Template
     *
     * @since 15.0.0
     */
    public function getBody(): Template {
        $template = new Template('twofactor_webauthn', 'personal');
        $template->assign('state', json_encode($this->devices));
        return $template;
    }
}