<?php

declare(strict_types = 1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TwoFactorWebauthn\Activity;

use InvalidArgumentException;
use OCP\Activity\IEvent;
use OCP\Activity\IProvider;
use OCP\IURLGenerator;
use OCP\L10N\IFactory as L10nFactory;

class Provider implements IProvider {

	/**
	 * @param L10nFactory $l10n
	 * @param IURLGenerator $urlGenerator
	 */
	public function __construct(
		private L10nFactory $l10n,
		private IURLGenerator $urlGenerator,
	) {
	}

	/**
	 * @param string $language
	 * @param IEvent $event
	 * @param IEvent $previousEvent
	 * @return IEvent
	 * @throws InvalidArgumentException
	 */
	public function parse($language, IEvent $event, ?IEvent $previousEvent = null) {
		if ($event->getApp() !== 'twofactor_webauthn') {
			throw new InvalidArgumentException();
		}

		$l = $this->l10n->get('twofactor_webauthn', $language);

		$event->setIcon($this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('core', 'actions/password.svg')));
		switch ($event->getSubject()) {
			case 'webauthn_device_added':
				$event->setSubject($l->t('You added an WebAuthn hardware token'));
				break;
			case 'webauthn_device_removed':
				$event->setSubject($l->t('You removed an WebAuthn hardware token'));
				break;
			case 'webauthn_disabled_by_admin':
				$event->setSubject($l->t('WebAuthn disabled by the administration'));
				break;
		}
		return $event;
	}
}
