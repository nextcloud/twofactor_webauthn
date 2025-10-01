<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TwoFactorWebauthn\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

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
	 * @throws Exception
	 */
	public function findById(int $id, string $userId): ?PublicKeyCredentialEntity {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq(
					'id',
					$qb->createNamedParameter($id, IQueryBuilder::PARAM_INT),
					IQueryBuilder::PARAM_INT
				),
				$qb->expr()->eq(
					'user_handle',
					$qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR),
					IQueryBuilder::PARAM_STR,
				),
			);
		try {
			return $this->findEntity($qb);
		} catch (DoesNotExistException $e) {
			return null;
		}
	}

	public function findPublicKeyCredential(string $publicKeyCredentialId, string $userId): ?PublicKeyCredentialEntity {
		$qb = $this->db->getQueryBuilder();

		$qb->select('id', 'name', 'public_key_credential_id', 'type', 'transports', 'attestation_type', 'trust_path', 'aaguid', 'credential_public_key', 'user_handle', 'counter', 'active', 'created_at')
			->from('twofactor_webauthn_regs')
			->where($qb->expr()->eq(
				'public_key_credential_id',
				$qb->createNamedParameter($publicKeyCredentialId, IQueryBuilder::PARAM_STR),
				IQueryBuilder::PARAM_STR,
			))
			->andWhere($qb->expr()->eq(
				'user_handle',
				$qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR),
				IQueryBuilder::PARAM_STR,
			));
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
		$publicKeyCredentialEntity = $this->findPublicKeyCredential($entity->getPublicKeyCredentialId(), $entity->getUserHandle());
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
