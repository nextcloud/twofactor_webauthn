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

class Version000203Date20200322200200 extends SimpleMigrationStep {

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
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('twofactor_webauthn_registrations')) {
			$table = $schema->getTable('twofactor_webauthn_registrations');
			if (!$table->hasColumn('aaguid_transform')) {
				$table->addColumn('aaguid_transform', 'string', [
					'notnull' => false
				]);
			}
		}

		return $schema;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 *
	 * @since 13.0.0
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
		if (!$this->connection->tableExists('twofactor_webauthn_registrations')) {
			return;
		}

		$selectQb = $this->connection->getQueryBuilder();
		$select = $selectQb->select('id', 'aaguid')
			->from('twofactor_webauthn_registrations');
		$updateQb = $this->connection->getQueryBuilder();
		$update = $updateQb->update('twofactor_webauthn_registrations')
			->set('aaguid_transform', $updateQb->createParameter('aaguid_transform'))
			->where($updateQb->expr()->eq('id', $updateQb->createParameter('id')));

		$this->connection->beginTransaction();
		try {
			$result = $select->executeQuery();
			while ($row = $result->fetch()) {
				$update->setParameter('aaguid_transform', $this->getUuidString($output, $row));
				$update->setParameter('id', $row['id']);
				$update->executeStatement();
			}
			$result->closeCursor();
			$this->connection->commit();
		} catch (Throwable $e) {
			$this->connection->rollBack();
			throw $e;
		}
	}

	private function getUuidString(IOutput $output, array $row) {
		try {
			return Uuid::fromBinary($row['aaguid'])->toRfc4122();
		} catch (Exception $e) {
			return Uuid::fromBinary("\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0")->toRfc4122();
		}
	}
}
