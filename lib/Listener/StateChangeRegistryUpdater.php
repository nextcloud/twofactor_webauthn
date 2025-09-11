<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TwoFactorWebauthn\Listener;

use OCA\TwoFactorWebauthn\Event\StateChanged;
use OCA\TwoFactorWebauthn\Model\Device;
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
			$devices = array_filter(
				$this->manager->getDevices($event->getUser()),
				static fn (Device $device) => $device->isActive(),
			);
			$hasDevices = !empty($devices);
			if ($hasDevices && $event->isEnabled()) {
				// The first device was enabled -> enable provider for this user
				$this->providerRegistry->enableProviderFor($this->provider, $event->getUser());
			} elseif (!$hasDevices && !$event->isEnabled()) {
				// The last device was removed -> disable provider for this user
				$this->providerRegistry->disableProviderFor($this->provider, $event->getUser());
			}
		}
	}
}
