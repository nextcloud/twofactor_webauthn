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

namespace OCA\TwoFactorWebauthn\Listener;

use OCA\TwoFactorWebauthn\Event\DisabledByAdmin;
use OCA\TwoFactorWebauthn\Event\StateChanged;
use OCP\Activity\IManager;
use Symfony\Component\EventDispatcher\Event;

class StateChangeActivity implements IListener {

	/** @var IManager */
	private $activityManager;

	public function __construct(IManager $activityManager) {
		$this->activityManager = $activityManager;
	}

	public function handle(Event $event) {
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
