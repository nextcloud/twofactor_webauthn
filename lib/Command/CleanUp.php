<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TwoFactorWebauthn\Command;

use OCA\TwoFactorWebauthn\Db\PublicKeyCredentialEntityMapper;
use OCP\DB\Exception;
use OCP\IDBConnection;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CleanUp extends Command {
	/** @var IDBConnection */
	private $db;

	/** @var IUserManager */
	private $userManager;

	/** @var PublicKeyCredentialEntityMapper */
	private $webauthnMapper;

	public function __construct(
		IDBConnection $db,
		IUserManager $userManager,
		PublicKeyCredentialEntityMapper $webauthnMapper,
	) {
		parent::__construct();

		$this->db = $db;
		$this->userManager = $userManager;
		$this->webauthnMapper = $webauthnMapper;
	}

	protected function configure(): void {
		$this
			->setName('twofactor_webauthn:cleanup')
			->setDescription('Remove orphaned webauthn credentials');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$io = new SymfonyStyle($input, $output);
		$io->title('Remove webauthn credentials for deleted users');

		foreach ($this->findUserIds() as $userId) {
			if ($this->userManager->userExists($userId) === false) {
				try {
					$io->text('Delete credentials for uid "' . $userId . '"');
					$this->webauthnMapper->deletePublicKeyCredentialsByUserId($userId);
				} catch (Exception $e) {
					$io->caution('Error deleting credentials: ' . $e->getMessage());
				}
			}
		}

		$io->success('Orphaned webauthn credentials removed.');

		$io->text('Thank you for using Two-Factor WebAuthn!');
		return 0;
	}

	/**
	 * @throws Exception
	 */
	private function findUserIds(): array {
		$userIds = [];

		$qb = $this->db->getQueryBuilder()
			->selectDistinct('user_handle')
			->from($this->webauthnMapper->getTableName());

		$result = $qb->executeQuery();

		while ($row = $result->fetch()) {
			$userIds[] = $row['user_handle'];
		}

		$result->closeCursor();

		return $userIds;
	}
}
