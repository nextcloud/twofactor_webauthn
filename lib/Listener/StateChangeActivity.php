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

use OCA\TwoFactorWebauthn\Event\DisabledByAdmin;
use OCA\TwoFactorWebauthn\Event\StateChanged;
use OCP\Activity\IManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * @template-implements IEventListener<StateChanged>
 */
class StateChangeActivity implements IEventListener {

	/** @var IManager */
	private $activityManager;

	public function __construct(IManager $activityManager) {
		$this->activityManager = $activityManager;
	}

	public function handle(Event $event): void {
		if ($event instanceof StateChanged) {
			if ($event instanceof DisabledByAdmin) {
				$subject = 'webauthn_disabled_by_admin';
			} else {
				$subject = $event->isEnabled() ? 'webauthn_device_added' : 'webauthn_device_removed';
			}

			$activity = $this->activityManager->generateEvent();
			$activity->setApp('twofactor_webauthn')
				->setType('security')
				->setAuthor($event->getUser()->getUID())
				->setAffectedUser($event->getUser()->getUID())
				->setSubject($subject);
			$this->activityManager->publish($activity);
		}
	}
}
