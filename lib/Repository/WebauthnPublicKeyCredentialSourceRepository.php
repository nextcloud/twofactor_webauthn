<?php
/** @noinspection PhpSignatureMismatchDuringInheritanceInspection */
/** @noinspection PhpHierarchyChecksInspection */

namespace OCA\TwoFactorWebauthn\Repository;

use OCA\TwoFactorWebauthn\Db\PublicKeyCredentialEntity;
use OCA\TwoFactorWebauthn\Db\PublicKeyCredentialEntityMapper;
use Webauthn\AttestedCredentialData;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\PublicKeyCredentialUserEntity;

class WebauthnPublicKeyCredentialSourceRepository implements PublicKeyCredentialSourceRepository
{
    /**
     * @var PublicKeyCredentialEntityMapper
     */
    private $publicKeyCredentialEntityMapper;


    /**
     * WebauthnPublicKeyCredentialSourceRepository constructor.
     * @param PublicKeyCredentialEntityMapper $publicKeyCredentialEntityMapper
     */
    public function __construct(PublicKeyCredentialEntityMapper $publicKeyCredentialEntityMapper)
    {
        $this->publicKeyCredentialEntityMapper = $publicKeyCredentialEntityMapper;
    }

    public function has(string $credentialId): bool
    {
        return false;
    }

    public function get(string $credentialId): AttestedCredentialData
    {
        return null;
    }

    public function getUserHandleFor(string  $credentialId): string
    {
        return null;
    }

    public function getCounterFor(string  $credentialId): int
    {
        return null;
    }

    public function updateCounterFor(string  $credentialId, int $newCounter): void
    {
        return;
    }

    public function findOneByCredentialId(string $publicKeyCredentialId): ?PublicKeyCredentialSource
    {
        $entity = $this->publicKeyCredentialEntityMapper->findPublicKeyCredential(base64_encode($publicKeyCredentialId));
        return $entity == null ? null : $entity->toPublicKeyCredentialSource();
    }

    /**
     * @param PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity
     * @return PublicKeyCredentialSource[]
     */
    public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
    {
        $credentials = $this->publicKeyCredentialEntityMapper->findPublicKeyCredentials($publicKeyCredentialUserEntity->getId());
        return array_map(function (PublicKeyCredentialEntity $credential) {
            return $credential->toPublicKeyCredentialSource();
        }, $credentials);
    }

    public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource, string $name = null): void
    {
        $name = $this->getName($publicKeyCredentialSource, $name);
        $entity = PublicKeyCredentialEntity::fromPublicKeyCrendentialSource($name, $publicKeyCredentialSource);
        $this->publicKeyCredentialEntityMapper->insertOrUpdate($entity);
    }

    private function getName(PublicKeyCredentialSource $publicKeyCredentialSource, string $name = null): string {
        if ($name != null) {
            return $name;
        }
        
        $entity = $this->publicKeyCredentialEntityMapper->findPublicKeyCredential(base64_encode($publicKeyCredentialSource->getPublicKeyCredentialId()));
        return $entity == null ? 'default' : $entity->getName();
    }
}