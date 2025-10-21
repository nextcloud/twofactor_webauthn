<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TwoFactorWebauthn\Tests\Integration\Migration;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OC;
use OCA\TwoFactorWebauthn\Migration\RepairProviderRegistrations;
use OCP\IDBConnection;
use OCP\Migration\IOutput;

class RepairProviderRegistrationsTest extends TestCase {

	/** @var IDBConnection */
	private $db;

	/** @var RepairProviderRegistrations */
	private $repairStep;

	protected function setUp(): void {
		parent::setUp();

		$this->db = OC::$server->get(IDBConnection::class);
		$this->repairStep = OC::$server->get(RepairProviderRegistrations::class);

		$deleteQb = $this->db->getQueryBuilder();
		$deleteQb->delete('twofactor_providers')
			->where($deleteQb->expr()->eq('uid', $deleteQb->createNamedParameter('test123456789')));
		$deleteQb->executeStatement();
	}

	protected function tearDown(): void {
		parent::tearDown();

		$deleteQb = $this->db->getQueryBuilder();
		$deleteQb->delete('twofactor_providers')
			->where($deleteQb->expr()->eq('uid', $deleteQb->createNamedParameter('test123456789')));
		$deleteQb->executeStatement();
	}

	public function testFixesOldRegistration(): void {
		$insertQb = $this->db->getQueryBuilder();
		$insertQb->insert('twofactor_providers')
			->values([
				'uid' => $insertQb->createNamedParameter('test123456789'),
				'provider_id' => $insertQb->createParameter('provider_id'),
				'enabled' => $insertQb->createNamedParameter(1),
			]);
		$insertQb->setParameter('provider_id', 'twofactor_webauthn');
		$insertQb->executeStatement();
		$output = $this->createMock(IOutput::class);

		$this->repairStep->run($output);

		$cntQb = $this->db->getQueryBuilder();
		$cntQb->select($cntQb->func()->count('*'))
			->from('twofactor_providers')
			->where(
				$cntQb->expr()->eq('uid', $cntQb->createNamedParameter('test123456789')),
				$cntQb->expr()->eq('provider_id', $cntQb->createNamedParameter('webauthn')),
			);
		$result = $cntQb->executeQuery();
		$cnt = $result->fetchOne();
		$result->closeCursor();
		self::assertEquals(1, $cnt);
	}

	public function testDropsDuplicateRegistration(): void {
		$insertQb = $this->db->getQueryBuilder();
		$insertQb->insert('twofactor_providers')
			->values([
				'uid' => $insertQb->createNamedParameter('test123456789'),
				'provider_id' => $insertQb->createParameter('provider_id'),
				'enabled' => $insertQb->createNamedParameter(1),
			]);
		$insertQb->setParameter('provider_id', 'webauthn');
		$insertQb->executeStatement();
		$insertQb->setParameter('provider_id', 'twofactor_webauthn');
		$insertQb->executeStatement();
		$output = $this->createMock(IOutput::class);
		$output->expects(self::never())->method('warning');

		$this->repairStep->run($output);

		$cntQb = $this->db->getQueryBuilder();
		$cntQb->select($cntQb->func()->count('*'))
			->from('twofactor_providers')
			->where(
				$cntQb->expr()->eq('uid', $cntQb->createNamedParameter('test123456789')),
			);
		$result = $cntQb->executeQuery();
		$cnt = $result->fetchOne();
		$result->closeCursor();
		self::assertEquals(1, $cnt);
	}
}
