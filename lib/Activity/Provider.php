<?php

declare(strict_types = 1);

/**
 * @author Michael Blumenstein <M.Flower@gmx.de>
 * @copyright Copyright (c) 2019 Michael Blumenstein <M.Flower@gmx.de>
 *
 * Two-factor webauthn
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
 *
 * Software Credits
 *
 * The development of this software was made possible using the following components:
 * 
 * twofactor_u2f (https://github.com/nextcloud/twofactor_u2f) by Christoph Wurst (https://github.com/ChristophWurst)
 * Licensed Under: AGPL
 * This project used the great twofactor provider u2f created by Christoph Wurst as a template.
 *
 * webauthn-framework (https://github.com/web-auth/webauthn-framework) by Florent Morselli (https://github.com/Spomky)
 * Licensed Under: MIT
 * The webauthn-framework provided most of the code and documentation for implementing the webauthn authentication.
 */

namespace OCA\TwoFactorWebauthn\Activity;

use InvalidArgumentException;
use OCP\Activity\IEvent;
use OCP\Activity\IProvider;
use OCP\ILogger;
use OCP\IURLGenerator;
use OCP\L10N\IFactory as L10nFactory;

class Provider implements IProvider {

	/** @var L10nFactory */
	private $l10n;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var ILogger */
	private $logger;

	/**
	 * @param L10nFactory $l10n
	 * @param IURLGenerator $urlGenerator
	 * @param ILogger $logger
	 */
	public function __construct(L10nFactory $l10n, IURLGenerator $urlGenerator, ILogger $logger) {
		$this->logger = $logger;
		$this->urlGenerator = $urlGenerator;
		$this->l10n = $l10n;
	}

	/**
	 * @param string $language
	 * @param IEvent $event
	 * @param IEvent $previousEvent
	 * @return IEvent
	 * @throws InvalidArgumentException
	 */
	public function parse($language, IEvent $event, IEvent $previousEvent = null) {
		if ($event->getApp() !== 'twofactor_webauthn') {
			throw new InvalidArgumentException();
		}

		$l = $this->l10n->get('twofactor_webauthn', $language);

		$event->setIcon($this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('core', 'actions/password.svg')));
		switch ($event->getSubject()) {
			case 'webauthn_device_added':
				$event->setSubject($l->t('You added an webauthn hardware token'));
				break;
			case 'webauthn_device_removed':
				$event->setSubject($l->t('You removed an webauthn hardware token'));
				break;
			case 'webauthn_disabled_by_admin':
				$event->setSubject($l->t('webauthn disabled by an admin'));
				break;
		}
		return $event;
	}

}
