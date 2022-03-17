<?php

declare(strict_types=1);

/*
 * @copyright 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
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

use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use function sprintf;

/**
 * Migrate all provider registrations of 'twofactor_webauthn' to just 'webauthn'
 */
class RepairProviderRegistrations implements IRepairStep {

	/** @var IDBConnection */
	private $db;

	public function __construct(IDBConnection $db) {
		$this->db = $db;
	}

	public function getName(): string {
		return 'Repair provider registrations';
	}

	public function run(IOutput $output): void {
		$selectQb = $this->db->getQueryBuilder();
		$selectQb->select('uid', 'provider_id', 'enabled')
			->from('twofactor_providers')
			->where($selectQb->expr()->eq('provider_id', $selectQb->createNamedParameter('twofactor_webauthn')));
		$updateQb = $this->db->getQueryBuilder();
		$updateQb->update('twofactor_providers')
			->set('provider_id', $updateQb->createNamedParameter('webauthn'))
			->set('enabled', $updateQb->createParameter('enabled'))
			->where(
				$updateQb->expr()->eq('provider_id', $updateQb->createNamedParameter('twofactor_webauthn')),
				$updateQb->expr()->eq('uid', $updateQb->createParameter('uid')),
			);
		$deleteQb = $this->db->getQueryBuilder();
		$deleteQb->delete('twofactor_providers')
			->where(
				$deleteQb->expr()->eq('provider_id', $deleteQb->createNamedParameter('twofactor_webauthn')),
				$deleteQb->expr()->eq('uid', $deleteQb->createParameter('uid')),
			);

		$result = $selectQb->execute();
		while ($row = $result->fetch()) {
			try {
				$updateQb->setParameter('uid', $row['uid']);
				$updateQb->setParameter('enabled', $row['enabled'], IQueryBuilder::PARAM_INT);
				$updateQb->executeStatement();
			} catch (Exception $e) {
				if ($e->getReason() === Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
					// The provider was registered twice for the user. We can safely drop the old one.
					$deleteQb->setParameter('uid', $row['uid']);
					$deleteQb->execute();
				} else {
					$output->warning(sprintf(
						$e->getCode() .
						'Could not migrate %s:%s',
						$row['provider_id'],
						$row['uid'],
					));
				}
			}
		}
		$result->closeCursor();
	}
}
