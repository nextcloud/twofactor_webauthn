<?php

namespace OCA\TwoFactorWebauthn\Db;

use OCP\AppFramework\Db\Entity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\TrustPath\AbstractTrustPath;
use Webauthn\TrustPath\TrustPath;

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


    static function fromPublicKeyCrendentialSource(string $name, PublicKeyCredentialSource $publicKeyCredentialSource): PublicKeyCredentialEntity
    {
        $publicKeyCredentialEntity = new self();
        
        $publicKeyCredentialEntity->setName($name);
        $publicKeyCredentialEntity->setPublicKeyCredentialId(base64_encode($publicKeyCredentialSource->getPublicKeyCredentialId()));
        $publicKeyCredentialEntity->setType($publicKeyCredentialSource->getType());
        $publicKeyCredentialEntity->setTransports(json_encode($publicKeyCredentialSource->getTransports()));
        $publicKeyCredentialEntity->setAttestationType($publicKeyCredentialSource->getAttestationType());
        $publicKeyCredentialEntity->setTrustPath(json_encode($publicKeyCredentialSource->getTrustPath()->jsonSerialize()));
        $publicKeyCredentialEntity->setAaguid($publicKeyCredentialSource->getAaguid());
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
            AbstractTrustPath::createFromArray((array)json_decode($this->trustPath)),
            $this->aaguid,
            base64_decode($this->credentialPublicKey),
            $this->userHandle,
            $this->counter
        );
    }
}