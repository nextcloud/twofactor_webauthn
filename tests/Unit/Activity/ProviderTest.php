<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
