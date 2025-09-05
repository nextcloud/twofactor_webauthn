<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TwoFactorWebauthn\Tests\Unit\Listener;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\TwoFactorWebauthn\Event\StateChanged;
use OCA\TwoFactorWebauthn\Listener\StateChangeActivity;
use OCP\Activity\IEvent;
use OCP\Activity\IManager;
use OCP\EventDispatcher\Event;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;

class StateChangeActivityTest extends TestCase {

	/** @var IManager|MockObject */
	private $activityManager;

	/** @var StateChangeActivity */
	private $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->activityManager = $this->createMock(IManager::class);

		$this->listener = new StateChangeActivity($this->activityManager);
	}

	public function testHandleGenericEvent(): void {
		$event = new Event();
		$this->activityManager->expects(self::never())
			->method('publish');

		$this->listener->handle($event);
	}

	public function testHandleEnableEvent(): void {
		$user = $this->createMock(IUser::class);
		$event = new StateChanged($user, true);
		$activityEvent = $this->createMock(IEvent::class);
		$this->activityManager->expects(self::once())
			->method('generateEvent')
			->willReturn($activityEvent);
		$activityEvent->expects(self::once())
			->method('setApp')
			->with(self::equalTo('twofactor_webauthn'))
			->willReturnSelf();
		$activityEvent->expects(self::once())
			->method('setType')
			->with(self::equalTo('security'))
			->willReturnSelf();
		$user->expects($this->any())
			->method('getUID')
			->willReturn('ursula');
		$activityEvent->expects(self::once())
			->method('setAuthor')
			->with(self::equalTo('ursula'))
			->willReturnSelf();
		$activityEvent->expects(self::once())
			->method('setAffectedUser')
			->with(self::equalTo('ursula'))
			->willReturnSelf();
		$activityEvent->expects(self::once())
			->method('setSubject')
			->with(self::equalTo('webauthn_device_added'))
			->willReturnSelf();
		$this->activityManager->expects(self::once())
			->method('publish')
			->with(self::equalTo($activityEvent));

		$this->listener->handle($event);
	}

	public function testHandleDisableEvent(): void {
		$user = $this->createMock(IUser::class);
		$event = new StateChanged($user, false);
		$activityEvent = $this->createMock(IEvent::class);
		$this->activityManager->expects(self::once())
			->method('generateEvent')
			->willReturn($activityEvent);
		$activityEvent->expects(self::once())
			->method('setApp')
			->with(self::equalTo('twofactor_webauthn'))
			->willReturnSelf();
		$activityEvent->expects(self::once())
			->method('setType')
			->with(self::equalTo('security'))
			->willReturnSelf();
		$user->expects($this->any())
			->method('getUID')
			->willReturn('ursula');
		$activityEvent->expects(self::once())
			->method('setAuthor')
			->with(self::equalTo('ursula'))
			->willReturnSelf();
		$activityEvent->expects(self::once())
			->method('setAffectedUser')
			->with(self::equalTo('ursula'))
			->willReturnSelf();
		$activityEvent->expects(self::once())
			->method('setSubject')
			->with(self::equalTo('webauthn_device_removed'))
			->willReturnSelf();
		$this->activityManager->expects(self::once())
			->method('publish')
			->with(self::equalTo($activityEvent));

		$this->listener->handle($event);
	}

	public function testHandleDisabledByAdminEvent(): void {
		$uid = 'user234';
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn($uid);
		$event = new StateChanged($user, false, true);
		$activityEvent = $this->createMock(IEvent::class);
		$this->activityManager->expects(self::once())
			->method('generateEvent')
			->willReturn($activityEvent);
		$activityEvent->expects(self::once())
			->method('setApp')
			->with('twofactor_webauthn')
			->willReturnSelf();
		$activityEvent->expects(self::once())
			->method('setType')
			->with('security')
			->willReturnSelf();
		$activityEvent->expects(self::once())
			->method('setAuthor')
			->with($uid)
			->willReturnSelf();
		$activityEvent->expects(self::once())
			->method('setAffectedUser')
			->with($uid)
			->willReturnSelf();
		$activityEvent->expects(self::once())
			->method('setSubject')
			->with(self::equalTo('webauthn_disabled_by_admin'))
			->willReturnSelf();
		$this->activityManager->expects(self::once())
			->method('publish')
			->with($activityEvent);

		$this->listener->handle($event);
	}
}
