<?php

declare(strict_types=1);

/*
 * @copyright 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Michael Blumenstein <M.Flower@gmx.de>
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

namespace OCA\TwoFactorWebauthn\AppInfo;

use OCA\TwoFactorWebauthn\Event\DisabledByAdmin;
use OCA\TwoFactorWebauthn\Event\StateChanged;
use OCA\TwoFactorWebauthn\Listener\StateChangeActivity;
use OCA\TwoFactorWebauthn\Listener\StateChangeRegistryUpdater;
use OCP\AppFramework\App;
use OCP\EventDispatcher\IEventDispatcher;

require_once __DIR__ . '/../../vendor/autoload.php';

class Application extends App {
	public const APP_ID = 'twofactor_webauthn';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);

		$container = $this->getContainer();

		/** @var IEventDispatcher $eventDispatcher */
		$eventDispatcher = $container->query(IEventDispatcher::class);
		$eventDispatcher->addServiceListener(StateChanged::class, StateChangeActivity::class);
		$eventDispatcher->addServiceListener(StateChanged::class, StateChangeRegistryUpdater::class);
		$eventDispatcher->addServiceListener(DisabledByAdmin::class, StateChangeActivity::class);
	}
}
