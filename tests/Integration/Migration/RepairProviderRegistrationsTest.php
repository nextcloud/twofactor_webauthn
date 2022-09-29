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
		$deleteQb->execute();
	}

	protected function tearDown(): void {
		parent::tearDown();

		$deleteQb = $this->db->getQueryBuilder();
		$deleteQb->delete('twofactor_providers')
			->where($deleteQb->expr()->eq('uid', $deleteQb->createNamedParameter('test123456789')));
		$deleteQb->execute();
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
		$insertQb->execute();
		$output = $this->createMock(IOutput::class);

		$this->repairStep->run($output);

		$cntQb = $this->db->getQueryBuilder();
		$cntQb->select($cntQb->func()->count('*'))
			->from('twofactor_providers')
			->where(
				$cntQb->expr()->eq('uid', $cntQb->createNamedParameter('test123456789')),
				$cntQb->expr()->eq('provider_id', $cntQb->createNamedParameter('webauthn')),
			);
		$result = $cntQb->execute();
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
		$insertQb->execute();
		$insertQb->setParameter('provider_id', 'twofactor_webauthn');
		$insertQb->execute();
		$output = $this->createMock(IOutput::class);
		$output->expects(self::never())->method('warning');

		$this->repairStep->run($output);

		$cntQb = $this->db->getQueryBuilder();
		$cntQb->select($cntQb->func()->count('*'))
			->from('twofactor_providers')
			->where(
				$cntQb->expr()->eq('uid', $cntQb->createNamedParameter('test123456789')),
			);
		$result = $cntQb->execute();
		$cnt = $result->fetchOne();
		$result->closeCursor();
		self::assertEquals(1, $cnt);
	}
}
