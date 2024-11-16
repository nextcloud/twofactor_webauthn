<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
