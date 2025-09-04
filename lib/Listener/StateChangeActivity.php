<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TwoFactorWebauthn\Listener;

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
			if ($event->isByAdmin()) {
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
