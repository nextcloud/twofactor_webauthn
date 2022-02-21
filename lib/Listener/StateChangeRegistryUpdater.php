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

namespace OCA\TwoFactorWebauthn\Listener;

use OCA\TwoFactorWebauthn\Event\StateChanged;
use OCA\TwoFactorWebauthn\Provider\WebAuthnProvider;
use OCA\TwoFactorWebauthn\Service\WebAuthnManager;
use OCP\Authentication\TwoFactorAuth\IRegistry;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * @template-implements IEventListener<StateChanged>
 */
class StateChangeRegistryUpdater implements IEventListener {

	/** @var IRegistry */
	private $providerRegistry;

	/** @var WebAuthnManager */
	private $manager;

	/** @var WebAuthnProvider */
	private $provider;

	public function __construct(IRegistry $providerRegistry, WebAuthnManager $manager, WebAuthnProvider $provider) {
		$this->providerRegistry = $providerRegistry;
		$this->provider = $provider;
		$this->manager = $manager;
	}

	public function handle(Event $event): void {
		if ($event instanceof StateChanged) {
			$devices = array_filter($this->manager->getDevices($event->getUser()), function ($device) {
				return $device['active'] === true;
			});
			if ($event->isEnabled() && count($devices) > 0) {
				// The first device was enabled -> enable provider for this user
				$this->providerRegistry->enableProviderFor($this->provider, $event->getUser());
			} elseif (!$event->isEnabled() && empty($devices)) {
				// The last device was removed -> disable provider for this user
				$this->providerRegistry->disableProviderFor($this->provider, $event->getUser());
			}
		}
	}
}
