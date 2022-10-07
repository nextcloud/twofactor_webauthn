<?php

declare(strict_types=1);

/*
 * @copyright 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Michael Blumenstein <M.Flower@gmx.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\TwoFactorWebauthn\Db;

use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUser;

/**
 * @template-extends QBMapper<PublicKeyCredentialEntity>
 */
class PublicKeyCredentialEntityMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'twofactor_webauthn_regs');
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

	/**
	 * @param IUser $user
	 * @param int $id
	 */
	public function findPublicKeyCredential($publicKeyCredentialId): ?PublicKeyCredentialEntity {
		/* @var $qb IQueryBuilder */
		$qb = $this->db->getQueryBuilder();

		$qb->select('id', 'name', 'public_key_credential_id', 'type', 'transports', 'attestation_type', 'trust_path', 'aaguid', 'credential_public_key', 'user_handle', 'counter', 'active', 'created_at')
			->from('twofactor_webauthn_regs')
			->where($qb->expr()->eq('public_key_credential_id', $qb->createNamedParameter($publicKeyCredentialId)));
		try {
			return $this->findEntity($qb);
		} catch (\Exception $exception) {
			return null;
		}
	}

	/**
	 * @param string $uid
	 * @return PublicKeyCredentialEntity[]
	 */
	public function findPublicKeyCredentials(string $uid): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('id', 'name', 'public_key_credential_id', 'type', 'transports', 'attestation_type', 'trust_path', 'aaguid', 'credential_public_key', 'user_handle', 'counter', 'active', 'created_at')
			->from('twofactor_webauthn_regs')
			->where($qb->expr()->eq('user_handle', $qb->createNamedParameter($uid)));
		return $this->findEntities($qb);
	}

	public function insertOrUpdate(Entity $entity): Entity {
		$publicKeyCredentialEntity = $this->findPublicKeyCredential($entity->getPublicKeyCredentialId());
		if ($publicKeyCredentialEntity !== null) {
			$entity->setId($publicKeyCredentialEntity->getId());
			return parent::update($entity);
		} else {
			return parent::insert($entity);
		}
	}

	/**
	 * @param string $uid
	 * @throws Exception
	 */
	public function deletePublicKeyCredentialsByUserId(string $uid): void {
		$qb = $this->db->getQueryBuilder();

		$qb->delete($this->getTableName())
			->where($qb->expr()->eq('user_handle', $qb->createNamedParameter($uid)));
		$qb->executeStatement();
	}
}
