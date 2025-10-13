<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TwoFactorWebauthn\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Symfony\Component\Uid\Uuid;
use Throwable;

class Version000203Date20200322201700 extends SimpleMigrationStep {

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
		$schema = $schemaClosure();

		if ($schema->hasTable('twofactor_webauthn_registrations')) {
			$table = $schema->getTable('twofactor_webauthn_registrations');
			if (!$table->hasColumn('aaguid')) {
				$table->addColumn('aaguid', 'guid', [
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
		$select = $selectQb->select('id', 'aaguid', 'aaguid_transform')
			->from('twofactor_webauthn_registrations');
		$updateQb = $this->connection->getQueryBuilder();
		$update = $updateQb->update('twofactor_webauthn_registrations')
			->set('aaguid', $updateQb->createParameter('aaguid'))
			->where($updateQb->expr()->eq('id', $updateQb->createParameter('id')));

		$this->connection->beginTransaction();
		try {
			$result = $select->executeQuery();
			while ($row = $result->fetch()) {
				$update->setParameter('aaguid', Uuid::fromString($row['aaguid_transform'])->toRfc4122());
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
}
