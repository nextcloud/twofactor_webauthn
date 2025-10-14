<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TwoFactorWebauthn\Migration;

use Closure;
use Exception;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Symfony\Component\Uid\Uuid;
use Throwable;

class Version000202Date20200320192700 extends SimpleMigrationStep {

	/** @var IDBConnection */
	protected $connection;

	/**
	 * @param IDBConnection $connection
	 */
	public function __construct(IDBConnection $connection) {
		$this->connection = $connection;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
		if (!$this->connection->tableExists('twofactor_webauthn_registrations')) {
			return;
		}

		$updateQb = $this->connection->getQueryBuilder();
		$update = $updateQb->update('twofactor_webauthn_registrations')
			->set('aaguid', $updateQb->createParameter('aaguid'))
			->where($updateQb->expr()->eq('id', $updateQb->createParameter('id')));
		$selectQb = $this->connection->getQueryBuilder();
		$select = $selectQb->select('id', 'name', 'aaguid', 'user_handle')
			->from('twofactor_webauthn_registrations')
			->where($selectQb->expr()->orX(
				$selectQb->createFunction('LENGTH(aaguid) <> 16'),
				$selectQb->expr()->isNull('aaguid')
			));

		$this->connection->beginTransaction();
		try {
			$result = $select->executeQuery();
			while ($row = $result->fetch()) {
				$update->setParameter('aaguid', $this->getBytes($output, $row));
				$update->setParameter('id', $row['id']);
				$update->executeStatement();
			}
			$result->closeCursor();
			$this->connection->commit();
		} catch (Throwable $e) {
			$this->connection->rollback();
			throw $e;
		}
	}

	private function getBytes(IOutput $output, array $row) {
		try {
			return Uuid::fromString($row['aaguid'])->toBinary();
		} catch (Exception $e) {
			$name = $row['name'];
			$user_handle = $row['user_handle'];
			$output->warning("replacing faulty aaguid for device $name from user $user_handle");
			return "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";
		}
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('twofactor_webauthn_registrations')) {
			$table = $schema->getTable('twofactor_webauthn_registrations');
			$table->getColumn('aaguid')->setOptions(['length' => 16]);
		}

		return $schema;
	}
}
