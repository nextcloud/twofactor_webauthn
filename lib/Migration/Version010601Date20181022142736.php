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

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version010601Date20181022142736 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

//		$schema->dropTable('twofactor_webauthn_registrations');

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
                'length' => 255
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
		return $schema;
	}
}
