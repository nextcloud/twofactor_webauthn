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

use OCP\Activity\ISetting;
use OCP\IL10N;

class Setting implements ISetting {

	/** @var IL10N */
	private $l10n;

	/**
	 * @param IL10N $l10n
	 */
	public function __construct(IL10N $l10n) {
		$this->l10n = $l10n;
	}

	/**
	 * @return boolean
	 */
	public function canChangeMail() {
		return false;
	}

	/**
	 * @return boolean
	 */
	public function canChangeStream() {
		return false;
	}

	/**
	 * @return string
	 */
	public function getIdentifier() {
		return 'twofactor_webauthn';
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->l10n->t('webauthn device');
	}

	/**
	 * @return int
	 */
	public function getPriority() {
		return 20;
	}

	/**
	 * @return boolean
	 */
	public function isDefaultEnabledMail() {
		return true;
	}

	/**
	 * @return boolean
	 */
	public function isDefaultEnabledStream() {
		return true;
	}

}
