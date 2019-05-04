<?php


namespace OCA\TwoFactorWebauthn\Db;

use mysql_xdevapi\Exception;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

class PublicKeyCredentialEntityMapper extends QBMapper
{
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'twofactor_webauthn_registrations');
    }

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
     * @var TrustPath
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
     * @param IUser $user
     * @param int $id
     */
    public function findPublicKeyCredential($publicKeyCredentialId): ?PublicKeyCredentialEntity {
        /* @var $qb IQueryBuilder */
        $qb = $this->db->getQueryBuilder();

        $qb->select('id', 'name', 'public_key_credential_id', 'type', 'transports', 'attestation_type', 'trust_path', 'aaguid', 'credential_public_key', 'user_handle', 'counter')
            ->from('twofactor_webauthn_registrations')
            ->where($qb->expr()->eq('public_key_credential_id', $qb->createNamedParameter($publicKeyCredentialId)));
        try {
            return $this->findEntity($qb);
        } catch (\Exception $exception) {
            return null;
        }
    }

    /**
     * @param IUser $user
     * @return Registration[]
     */
    public function findPublicKeyCredentials(string $userHandle): array {
        /* @var $qb IQueryBuilder */
        $qb = $this->db->getQueryBuilder();

        $qb->select('id', 'name', 'public_key_credential_id', 'type', 'transports', 'attestation_type', 'trust_path', 'aaguid', 'credential_public_key', 'user_handle', 'counter')
            ->from('twofactor_webauthn_registrations')
            ->where($qb->expr()->eq('user_handle', $qb->createNamedParameter($userHandle)));
        return $this->findEntities($qb);
    }

    public function insert(Entity $entity): Entity {
        $publicKeyCredentialEntity = $this->findPublicKeyCredential($entity->getPublicKeyCredentialId());
        if ($publicKeyCredentialEntity != null) {
            $entity->setId($publicKeyCredentialEntity->getId());
        }

        return parent::insert($entity);
    }
}