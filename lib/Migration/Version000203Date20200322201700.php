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

namespace OCA\TwoFactorWebauthn\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;
use Ramsey\Uuid\Uuid;
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

		$table = $schema->getTable('twofactor_webauthn_registrations');
		if (!$table->hasColumn('aaguid')) {
			$table->addColumn('aaguid', 'guid', [
				'notnull' => false
			]);
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
		$this->connection->beginTransaction();
		try {
			$selectQb = $this->connection->getQueryBuilder();
			$result = $selectQb->select('id', 'aaguid', 'aaguid_transform')
				->from('twofactor_webauthn_registrations')
				->execute();
			$updateQb = $this->connection->getQueryBuilder();
			$updateQb->update('twofactor_webauthn_registrations')
				->set('aaguid', $updateQb->createParameter('aaguid'))
				->where($updateQb->expr()->eq('id', $updateQb->createParameter('id')));
			while ($row = $result->fetch()) {
				$updateQb->setParameter('aaguid', Uuid::fromString($row['aaguid_transform'])->toString());
				$updateQb->setParameter('id', $row['id']);
				$updateQb->execute();
			}
			$result->closeCursor();
			$this->connection->commit();
		} catch (Throwable $e) {
			$this->connection->rollBack();
			throw $e;
		}
	}
}
