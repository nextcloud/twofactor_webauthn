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
use Ramsey\Uuid;

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
		$schema = $schemaClosure();

		$table = $schema->getTable('twofactor_webauthn_registrations');

		if (!$table->hasColumn('aaguid_transform')) {
			$table->addColumn('aaguid_transform', 'string', [
				'notnull' => false
			]);
		}

		return $schema;
	}

	/**
	 * @param IOutput $output
	 * @param \Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @since 13.0.0
	 */
	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
		$qb = $this->connection->getQueryBuilder();

		$cursor = $qb->select(array('id', 'aaguid'))
		   ->from('twofactor_webauthn_registrations')
		   ->execute();

		while ($row = $cursor->fetch()) {
			$updater = $this->connection->getQueryBuilder();
			$updater->update('twofactor_webauthn_registrations')
				->set('aaguid_transform', $updater->createNamedParameter($this->getUuidString($output, $row)))
				->where('id = :id')
				->setParameter('id', $row['id'])
				->execute();
		}
	}

	private function getUuidString(IOutput $output, array $row) {
		try {
			return Uuid\Uuid::fromBytes($row['aaguid'])->toString();
		} catch (\Exception $e) {
			return Uuid\Uuid::fromBytes("\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0")->toString();
		}
	}
}
