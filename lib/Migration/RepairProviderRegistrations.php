<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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

		$result = $selectQb->executeQuery();
		while ($row = $result->fetch()) {
			try {
				$updateQb->setParameter('uid', $row['uid']);
				$updateQb->setParameter('enabled', $row['enabled'], IQueryBuilder::PARAM_INT);
				$updateQb->executeStatement();
			} catch (Exception $e) {
				if ($e->getReason() === Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
					// The provider was registered twice for the user. We can safely drop the old one.
					$deleteQb->setParameter('uid', $row['uid']);
					$deleteQb->executeStatement();
				} else {
					$output->warning(sprintf(
						'%s Could not migrate %s:%s',
						(string)$e->getCode(),
						$row['provider_id'],
						$row['uid'],
					));
				}
			}
		}
		$result->closeCursor();
	}
}
