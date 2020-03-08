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
     * @var bool
     */
    protected $active;

    /**
     * @param IUser $user
     * @param int $id
     */
    public function findPublicKeyCredential($publicKeyCredentialId): ?PublicKeyCredentialEntity {
        /* @var $qb IQueryBuilder */
        $qb = $this->db->getQueryBuilder();

        $qb->select('id', 'name', 'public_key_credential_id', 'type', 'transports', 'attestation_type', 'trust_path', 'aaguid', 'credential_public_key', 'user_handle', 'counter', 'active')
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

        $qb->select('id', 'name', 'public_key_credential_id', 'type', 'transports', 'attestation_type', 'trust_path', 'aaguid', 'credential_public_key', 'user_handle', 'counter', 'active')
            ->from('twofactor_webauthn_registrations')
            ->where($qb->expr()->eq('user_handle', $qb->createNamedParameter($userHandle)));
        return $this->findEntities($qb);
    }

    public function insert(Entity $entity): Entity {
        $publicKeyCredentialEntity = $this->findPublicKeyCredential($entity->getPublicKeyCredentialId());
        if ($publicKeyCredentialEntity !== null) {
            $entity->setId($publicKeyCredentialEntity->getId());
        }

        return parent::insert($entity);
    }
}