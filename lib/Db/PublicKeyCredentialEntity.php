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

namespace OCA\TwoFactorWebauthn\Db;

use OCP\AppFramework\Db\Entity;
use Ramsey\Uuid\Uuid;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\TrustPath\TrustPathLoader;

class PublicKeyCredentialEntity extends Entity
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $publicKeyCredentialId;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string[]
     */
    protected $transports;

    /**
     * @var string
     */
    protected $attestationType;

    /**
     * @var string
     */
    protected $trustPath;

    /**
     * @var string
     */
    protected $aaguid;

    /**
     * @var string
     */
    protected $credentialPublicKey;

    /**
     * @var string
     */
    protected $userHandle;

    /**
     * @var int
     */
    protected $counter;

    /**
     * @var bool
     */
    protected $active;


    static function fromPublicKeyCrendentialSource(string $name, PublicKeyCredentialSource $publicKeyCredentialSource): PublicKeyCredentialEntity
    {
        $publicKeyCredentialEntity = new self();
        
        $publicKeyCredentialEntity->setName($name);
        $publicKeyCredentialEntity->setPublicKeyCredentialId(base64_encode($publicKeyCredentialSource->getPublicKeyCredentialId()));
        $publicKeyCredentialEntity->setType($publicKeyCredentialSource->getType());
        $publicKeyCredentialEntity->setTransports(json_encode($publicKeyCredentialSource->getTransports()));
        $publicKeyCredentialEntity->setAttestationType($publicKeyCredentialSource->getAttestationType());
        $publicKeyCredentialEntity->setTrustPath(json_encode($publicKeyCredentialSource->getTrustPath()->jsonSerialize()));
        $publicKeyCredentialEntity->setAaguid($publicKeyCredentialSource->getAaguid()->getBytes());
        $publicKeyCredentialEntity->setCredentialPublicKey(base64_encode($publicKeyCredentialSource->getCredentialPublicKey()));
        $publicKeyCredentialEntity->setUserHandle($publicKeyCredentialSource->getUserHandle());
        $publicKeyCredentialEntity->setCounter($publicKeyCredentialSource->getCounter());
        
        return $publicKeyCredentialEntity;
    }

    function toPublicKeyCredentialSource(): PublicKeyCredentialSource
    {
        return new PublicKeyCredentialSource(
            base64_decode($this->publicKeyCredentialId),
            $this->type,
            json_decode($this->transports),
            $this->attestationType,
            TrustPathLoader::loadTrustPath((array)json_decode($this->trustPath)),
            Uuid::fromBytes($this->aaguid),
            base64_decode($this->credentialPublicKey),
            $this->userHandle,
            $this->counter
        );
    }
}