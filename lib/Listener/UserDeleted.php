<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TwoFactorWebauthn\Listener;

use OCA\TwoFactorWebauthn\Db\PublicKeyCredentialEntityMapper;
use OCP\DB\Exception;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserDeletedEvent;
use Psr\Log\LoggerInterface;

/**
 * @implements IEventListener<UserDeletedEvent>
 */
class UserDeleted implements IEventListener {

	/** @var PublicKeyCredentialEntityMapper */
	private $publicKeyCredentialEntityMapper;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(PublicKeyCredentialEntityMapper $publicKeyCredentialEntityMapper, LoggerInterface $logger) {
		$this->publicKeyCredentialEntityMapper = $publicKeyCredentialEntityMapper;
		$this->logger = $logger;
	}

	public function handle(Event $event): void {
		if ($event instanceof UserDeletedEvent) {
			try {
				$this->publicKeyCredentialEntityMapper->deletePublicKeyCredentialsByUserId($event->getUser()->getUID());
			} catch (Exception $e) {
				$this->logger->warning($e->getMessage(), ['uid' => $event->getUser()->getUID()]);
			}
		}
	}
}
