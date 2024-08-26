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
use InvalidArgumentException;
use OCA\TwoFactorWebauthn\Activity\Provider;
use OCP\Activity\IEvent;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use PHPUnit\Framework\MockObject\MockObject;

class ProviderTest extends TestCase {
	private IFactory&MockObject $l10n;
	private IURLGenerator&MockObject $urlGenerator;

	/** @var Provider */
	private $provider;

	protected function setUp(): void {
		parent::setUp();

		$this->l10n = $this->createMock(IFactory::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);

		$this->provider = new Provider($this->l10n, $this->urlGenerator);
	}

	public function testParseUnrelated(): void {
		$lang = 'ru';
		$event = $this->createMock(IEvent::class);
		$event->expects(self::once())
			->method('getApp')
			->willReturn('comments');
		$this->expectException(InvalidArgumentException::class);

		$this->provider->parse($lang, $event);
	}

	public function subjectData(): array {
		return [
			['webauthn_device_added'],
			['webauthn_device_removed'],
			['webauthn_disabled_by_admin'],
		];
	}

	/**
	 * @dataProvider subjectData
	 */
	public function testParse($subject): void {
		$lang = 'ru';
		$event = $this->createMock(IEvent::class);
		$l = $this->createMock(IL10N::class);

		$event->expects(self::once())
			->method('getApp')
			->willReturn('twofactor_webauthn');
		$this->l10n->expects(self::once())
			->method('get')
			->with('twofactor_webauthn', $lang)
			->willReturn($l);
		$this->urlGenerator->expects(self::once())
			->method('imagePath')
			->with('core', 'actions/password.svg')
			->willReturn('path/to/image');
		$this->urlGenerator->expects(self::once())
			->method('getAbsoluteURL')
			->with('path/to/image')
			->willReturn('absolute/path/to/image');
		$event->expects(self::once())
			->method('setIcon')
			->with('absolute/path/to/image');
		$event->expects(self::once())
			->method('getSubject')
			->willReturn($subject);
		$event->expects(self::once())
			->method('setSubject');

		$this->provider->parse($lang, $event);
	}
}
