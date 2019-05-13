<?php

declare(strict_types=1);

/**
 * Nextcloud - Webauthn 2FA
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2018
 */

namespace OCA\TwoFactorWebauthn\AppInfo;

use OCA\TwoFactorWebauthn\Event\DisabledByAdmin;
use OCA\TwoFactorWebauthn\Event\StateChanged;
use OCA\TwoFactorWebauthn\Listener\IListener;
use OCA\TwoFactorWebauthn\Listener\StateChangeActivity;
use OCA\TwoFactorWebauthn\Listener\StateChangeRegistryUpdater;
use OCP\AppFramework\App;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Application extends App {

	public function __construct(array $urlParams = []) {
		parent::__construct('twofactor_webauthn', $urlParams);

		$container = $this->getContainer();
		/** @var EventDispatcherInterface $eventDispatcher */
		$eventDispatcher = $container->getServer()->getEventDispatcher();
		$eventDispatcher->addListener(StateChanged::class, function (StateChanged $event) use ($container) {
			/** @var IListener[] $listeners */
			$listeners = [
				$container->query(StateChangeActivity::class),
				$container->query(StateChangeRegistryUpdater::class),
			];

			foreach ($listeners as $listener) {
				$listener->handle($event);
			}
		});
		$eventDispatcher->addListener(DisabledByAdmin::class, function (DisabledByAdmin $event) use ($container) {
			/** @var IListener[] $listeners */
			$listeners = [
				$container->query(StateChangeActivity::class),
			];

			foreach ($listeners as $listener) {
				$listener->handle($event);
			}
		});
	}

}
