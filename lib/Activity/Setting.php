<?php

declare(strict_types = 1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
		return $this->l10n->t('Security key');
	}

	/**
	 * @return int
	 */
	public function getPriority() {
		return 30;
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
