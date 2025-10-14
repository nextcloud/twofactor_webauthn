<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TwoFactorWebauthn\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version000400Date20220524123120 extends SimpleMigrationStep {

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
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('twofactor_webauthn_regs')) {
			$table = $schema->createTable('twofactor_webauthn_regs');
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('name', 'string', [
				'notnull' => true,
				'length' => 64,
				'default' => 'default'
			]);
			$table->addColumn('public_key_credential_id', 'string', [
				'notnull' => true,
				'length' => 255
			]);
			$table->addColumn('type', 'string', [
				'notnull' => true,
				'length' => 30,
			]);
			$table->addColumn('transports', 'string', [
				'notnull' => true,
				'length' => 30,
			]);
			$table->addColumn('attestation_type', 'string', [
				'notnull' => true,
				'length' => 6,
			]);
			$table->addColumn('trust_path', 'string', [
				'notnull' => true,
				'length' => 2500,
			]);
			$table->addColumn('aaguid', 'guid', [
				'notnull' => false
			]);
			$table->addColumn('credential_public_key', 'string', [
				'notnull' => true,
				'length' => 2000,
			]);
			$table->addColumn('user_handle', 'string', [
				'notnull' => true,
				'length' => 64
			]);
			$table->addColumn('counter', 'integer', [
				'notnull' => true,
				'length' => 255
			]);
			$table->addColumn('active', 'boolean', [
				'notnull' => false,
				'default' => true,
			]);
			$table->setPrimaryKey(['id'], 'webauthn_regs_id');
			$table->addIndex(['user_handle'], 'webauthn_regs_userHandle');
			$table->addIndex(['public_key_credential_id'], 'webauthn_regs_pubKeyCredId');
		}

		return $schema;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return void
	 */
	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
		if (!$this->connection->tableExists('twofactor_webauthn_registrations')) {
			return;
		}

		$insertQb = $this->connection->getQueryBuilder();
		$insertQb->insert('twofactor_webauthn_regs')
			->values([
				//'id' => $insertQb->createParameter('id'),
				'name' => $insertQb->createParameter('name'),
				'public_key_credential_id' => $insertQb->createParameter('public_key_credential_id'),
				'type' => $insertQb->createParameter('type'),
				'transports' => $insertQb->createParameter('transports'),
				'attestation_type' => $insertQb->createParameter('attestation_type'),
				'trust_path' => $insertQb->createParameter('trust_path'),
				'aaguid' => $insertQb->createParameter('aaguid'),
				'credential_public_key' => $insertQb->createParameter('credential_public_key'),
				'user_handle' => $insertQb->createParameter('user_handle'),
				'counter' => $insertQb->createParameter('counter'),
				'active' => $insertQb->createParameter('active')
			]);

		$selectQb = $this->connection->getQueryBuilder();
		$selectQb->select('*')
			->from('twofactor_webauthn_registrations');

		$result = $selectQb->executeQuery();
		while ($row = $result->fetch()) {
			$insertQb
				->setParameter('name', $row['name'])
				->setParameter('public_key_credential_id', $row['public_key_credential_id'])
				->setParameter('type', $row['type'])
				->setParameter('transports', $row['transports'])
				->setParameter('attestation_type', $row['attestation_type'])
				->setParameter('trust_path', $row['trust_path'])
				->setParameter('aaguid', $row['aaguid'])
				->setParameter('credential_public_key', $row['credential_public_key'])
				->setParameter('user_handle', $row['user_handle'])
				->setParameter('counter', (int)$row['counter'], IQueryBuilder::PARAM_INT)
				->setParameter('active', (bool)$row['active'], IQueryBuilder::PARAM_BOOL);
			$insertQb->executeStatement();
		}
		$result->closeCursor();
	}
}
