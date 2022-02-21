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
			->with('WebAuthn device')
			->willReturn('WebAuthn Gerät');
		self::assertEquals('WebAuthn Gerät', $this->setting->getName());
		self::assertEquals(30, $this->setting->getPriority());
		self::assertEquals(true, $this->setting->isDefaultEnabledMail());
		self::assertEquals(true, $this->setting->isDefaultEnabledStream());
	}
}
