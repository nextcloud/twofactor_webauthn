<?php
declare(strict_types=1);

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

namespace OCA\TwoFactorWebauthn\Migration;

require dirname(__FILE__).'/../../vendor/autoload.php';

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;
use Ramsey\Uuid;

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
	 * @param \Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @since 13.0.0
	 */
	public function preSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
		$qb = $this->connection->getQueryBuilder();

        $cursor = $qb->select(array('id', 'name', 'aaguid', 'user_handle'))
           ->from('twofactor_webauthn_registrations')
		   ->where('LENGTH(aaguid) <> 16 OR aaguid IS NULL')
		   ->execute();

        while($row = $cursor->fetch()){
            $qb->update('twofactor_webauthn_registrations')
                ->set('aaguid', $qb->createNamedParameter($this->getBytes($output, $row)))
                ->where('id = :id')
				->setParameter('id', $row['id'])
				->execute();
        }
	}

	private function getBytes(IOutput $output, array $row) {
		try {
			return Uuid\Uuid::fromString($row['aaguid'])->getBytes();
		} catch(\Exception $e) {
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

		$table = $schema->getTable('twofactor_webauthn_registrations');

		$table->getColumn('aaguid')->setOptions(['length' => 16]);

		return $schema;
	}
}
