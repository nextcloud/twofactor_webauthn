<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TwoFactorWebauthn\Tests\Unit\Activity;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\TwoFactorWebauthn\Activity\Setting;
use OCP\IL10N;

class SettingTest extends TestCase {
	private $l10n;

	/** @var Setting */
	private $setting;

	protected function setUp(): void {
		parent::setUp();

		$this->l10n = $this->createMock(IL10N::class);

		$this->setting = new Setting($this->l10n);
	}

	public function testAll(): void {
		self::assertEquals(false, $this->setting->canChangeMail());
		self::assertEquals(false, $this->setting->canChangeStream());
		self::assertEquals('twofactor_webauthn', $this->setting->getIdentifier());
		$this->l10n->expects(self::once())
			->method('t')
			->with('Security key')
			->willReturn('Sicherheitsschlüssel');
		self::assertEquals('Sicherheitsschlüssel', $this->setting->getName());
		self::assertEquals(30, $this->setting->getPriority());
		self::assertEquals(true, $this->setting->isDefaultEnabledMail());
		self::assertEquals(true, $this->setting->isDefaultEnabledStream());
	}
}
