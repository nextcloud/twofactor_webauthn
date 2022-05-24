<?php

declare(strict_types=1);

/*
 * @copyright 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Michael Blumenstein <M.Flower@gmx.de>
 * @author Richard Steinmetz <richard@steinmetz.cloud>
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
use Exception;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;
use Ramsey\Uuid;
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
			$result = $select->execute();
			while ($row = $result->fetch()) {
				$update->setParameter('aaguid', $this->getBytes($output, $row));
				$update->setParameter('id', $row['id']);
				$update->execute();
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
			return Uuid\Uuid::fromString($row['aaguid'])->getBytes();
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
