<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TwoFactorWebauthn\Command;

use OCA\TwoFactorWebauthn\Db\PublicKeyCredentialEntity;
use OCA\TwoFactorWebauthn\Db\PublicKeyCredentialEntityMapper;
use OCA\TwoFactorWebauthn\Db\RegistrationMapper;
use OCA\TwoFactorWebauthn\Event\StateChanged;
use OCA\TwoFactorWebauthn\Service\U2FMigrator;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use function strlen;
use function substr;

class MigrateU2F extends Command {
	public const OPTION_ALL = 'all';
	public const OPTION_DELETE_U2F_REGISTRATIONS = 'delete-u2f-registrations';
	public const ARGUMENT_USER_ID = 'userId';

	/** @var U2FMigrator */
	protected $migrator;

	/** @var IDBConnection */
	private $db;

	/** @var IUserManager */
	private $userManager;

	/** @var IEventDispatcher */
	private $eventDispatcher;

	/** @var PublicKeyCredentialEntityMapper */
	private $webauthnMapper;

	/** @var RegistrationMapper */
	private $u2fMapper;

	public function __construct(U2FMigrator $migrator,
		IDBConnection $db,
		IUserManager $userManager,
		IEventDispatcher $eventDispatcher,
		PublicKeyCredentialEntityMapper $webauthnMapper,
		RegistrationMapper $u2fMapper) {
		parent::__construct();

		$this->migrator = $migrator;
		$this->db = $db;
		$this->userManager = $userManager;
		$this->eventDispatcher = $eventDispatcher;
		$this->webauthnMapper = $webauthnMapper;
		$this->u2fMapper = $u2fMapper;
	}

	protected function configure(): void {
		$this->setName('twofactor_webauthn:migrate-u2f');
		$this->addOption(self::OPTION_ALL);
		$this->addOption(self::OPTION_DELETE_U2F_REGISTRATIONS);
		$this->addArgument(self::ARGUMENT_USER_ID, InputArgument::OPTIONAL | InputArgument::IS_ARRAY);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		/** @var bool $all */
		$all = $input->getOption(self::OPTION_ALL);
		/** @var bool $deleteU2fRegistrations */
		$deleteU2fRegistrations = $input->getOption(self::OPTION_DELETE_U2F_REGISTRATIONS);

		/** @var string[] $userIds */
		$userIds = $input->getArgument(self::ARGUMENT_USER_ID);

		if (count($userIds) > 0) {
			foreach ($userIds as $userId) {
				$user = $this->userManager->get($userId);
				if ($user === null) {
					$output->writeln("<error>User $userId does not exist</error>");
					continue;
				}
				$this->migrateUser($user, $deleteU2fRegistrations, $output);
			}
		} elseif ($all) {
			$output->writeln('Migrating all devices of all users ...');
			$this->userManager->callForAllUsers(function (IUser $user) use ($deleteU2fRegistrations, $output) {
				$this->migrateUser($user, $deleteU2fRegistrations, $output);
			});
		} else {
			$output->writeln('<error>Specify userId(s) or use --all flag</error>');
			return 1;
		}

		return 0;
	}

	private function migrateUser(IUser $user, bool $deleteU2fRegistrations, OutputInterface $output): void {
		$output->writeln('Migrating devices of user ' . $user->getUID());
		$registrations = $this->u2fMapper->findRegistrations($user);

		$this->db->beginTransaction();
		try {
			foreach ($registrations as $registration) {
				$name = $registration->getName() . ' (U2F)';
				if (strlen($name) > 64) {
					$dots = '...';
					$name = substr($name, 0, 64 - strlen($dots)) . $dots;
				}

				$source = $this->migrator->migrateU2FRegistration($registration);
				$entity = PublicKeyCredentialEntity::fromPublicKeyCrendentialSource($name, $source, null);
				$this->webauthnMapper->insert($entity);

				if ($deleteU2fRegistrations) {
					$this->u2fMapper->delete($registration);
				}
			}
			$this->db->commit();
		} catch (Throwable $e) {
			$this->db->rollBack();

			$uid = $user->getUID();
			$errorMessage = $e->getMessage();
			$output->writeln("<error>Could not migrate user $uid ($errorMessage)</error>");

			throw $e;
		}

		// Enable provider if at least one device was migrated
		if (count($registrations) > 0) {
			$this->eventDispatcher->dispatchTyped(new StateChanged($user, true));
		}
	}
}
