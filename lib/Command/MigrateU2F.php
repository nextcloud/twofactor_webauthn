<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
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
 */

namespace OCA\TwoFactorWebauthn\Command;

use OC;
use OCA\TwoFactorU2F\Db\RegistrationMapper;
use OCA\TwoFactorWebauthn\Repository\WebauthnPublicKeyCredentialSourceRepository;
use OCA\TwoFactorWebauthn\Service\U2FMigrator;
use OCP\IUser;
use OCP\IUserManager;
use Psr\Container\ContainerExceptionInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateU2F extends Command {

	public const OPTION_ALL = 'all';
	public const ARGUMENT_USER_ID = 'userId';

	/** @var U2FMigrator */
	protected $migrator;

	/** @var IUserManager */
	private $userManager;

	/** @var WebauthnPublicKeyCredentialSourceRepository */
	private $repository;

	/** @var ?RegistrationMapper */
	private $mapper;

	public function __construct(U2FMigrator                                 $migrator,
								IUserManager                                $userManager,
								WebauthnPublicKeyCredentialSourceRepository $repository) {
		parent::__construct();

		$this->migrator = $migrator;
		$this->userManager = $userManager;
		$this->repository = $repository ;

		try {
			$this->mapper = OC::$server->get(RegistrationMapper::class);
		} catch (ContainerExceptionInterface $e) {
			$this->mapper = null;
		}

	}

	protected function configure(): void {
		$this->setName("twofactor_webauthn:migrate-u2f");
		$this->addOption(self::OPTION_ALL);
		$this->addArgument(self::ARGUMENT_USER_ID, InputArgument::OPTIONAL | InputArgument::IS_ARRAY);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		if ($this->mapper === null) {
			$output->writeln('<error>Please enable the twofactor_u2f app first</error>');
			return 1;
		}

		/** @var bool $all */
		$all = $input->getOption(self::OPTION_ALL);
		/** @var string[] $userIds */
		$userIds = $input->getArgument(self::ARGUMENT_USER_ID);

		if (count($userIds) > 0) {
			foreach ($userIds as $userId) {
				$user = $this->userManager->get($userId);
				if ($user === null) {
					$output->writeln("<error>User $userId does not exist</error>");
					continue;
				}
				$this->migrateUser($user, $output);
			}
		} else if ($all) {
			$output->writeln('Migrating all devices of all users ...');
			$this->userManager->callForAllUsers(function (IUser $user) use ($output) {
				$this->migrateUser($user, $output);
			});
		} else {
			$output->writeln('<error>Specify userId(s) or use --all flag</error>');
			return 1;
		}

		return 0;
	}

	private function migrateUser(IUser $user, OutputInterface $output): void {
		$output->writeln('Migrating devices of user ' . $user->getUID());
		$registrations = $this->mapper->findRegistrations($user);
		foreach ($registrations as $registration) {
			$source = $this->migrator->migrateU2FRegistration($registration);
			$this->repository->saveCredentialSource($source);
		}
	}
}
