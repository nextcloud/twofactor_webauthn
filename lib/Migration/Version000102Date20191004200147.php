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
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version000102Date20191004200147 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		/* Replaced by Version000400Date20220524123120
		if (!$schema->hasTable('twofactor_webauthn_registrations')) {
			$table = $schema->createTable('twofactor_webauthn_registrations');
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
			$table->addColumn('aaguid', 'string', [
				'notnull' => false,
				'length' => 36,
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
			$table->setPrimaryKey(['id']);
			$table->addIndex(['user_handle'], 'webauthn_registrations_userHandle');
			$table->addIndex(['public_key_credential_id'], 'webauthn_registrations_publicKeyCredentialId');
		}
		*/

		return $schema;
	}
}
