<?php

/** @noinspection PhpSignatureMismatchDuringInheritanceInspection */
/** @noinspection PhpHierarchyChecksInspection */

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TwoFactorWebauthn\Repository;

use BadMethodCallException;
use OCA\TwoFactorWebauthn\Db\PublicKeyCredentialEntity;
use OCA\TwoFactorWebauthn\Db\PublicKeyCredentialEntityMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use Webauthn\AttestedCredentialData;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\PublicKeyCredentialUserEntity;

class WebauthnPublicKeyCredentialSourceRepository implements PublicKeyCredentialSourceRepository {
	/**
	 * @var PublicKeyCredentialEntityMapper
	 */
	private $publicKeyCredentialEntityMapper;

	/** @var ITimeFactory */
	private $time;

	private ?string $userId;

	/**
	 * @param PublicKeyCredentialEntityMapper $publicKeyCredentialEntityMapper
	 */
	public function __construct(PublicKeyCredentialEntityMapper $publicKeyCredentialEntityMapper,
		ITimeFactory $time,
		?string $userId) {
		$this->publicKeyCredentialEntityMapper = $publicKeyCredentialEntityMapper;
		$this->time = $time;
		$this->userId = $userId;
	}

	public function has(string $credentialId): bool {
		throw new BadMethodCallException('Not implemented');
	}

	public function get(string $credentialId): AttestedCredentialData {
		throw new BadMethodCallException('Not implemented');
	}

	public function getUserHandleFor(string $credentialId): string {
		throw new BadMethodCallException('Not implemented');
	}

	public function getCounterFor(string $credentialId): int {
		throw new BadMethodCallException('Not implemented');
	}

	public function updateCounterFor(string $credentialId, int $newCounter): void {
		throw new BadMethodCallException('Not implemented');
	}

	public function findOneByCredentialId(string $publicKeyCredentialId): ?PublicKeyCredentialSource {
		if ($this->userId === null) {
			return null;
		}

		$entity = $this->publicKeyCredentialEntityMapper->findPublicKeyCredential(base64_encode($publicKeyCredentialId), $this->userId);
		return $entity === null ? null : $entity->toPublicKeyCredentialSource();
	}

	/**
	 * @param PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity
	 * @return PublicKeyCredentialSource[]
	 */
	public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array {
		$credentials = $this->publicKeyCredentialEntityMapper->findPublicKeyCredentials($publicKeyCredentialUserEntity->getId());
		return array_map(function (PublicKeyCredentialEntity $credential) {
			return $credential->toPublicKeyCredentialSource();
		}, $credentials);
	}

	public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource, ?string $name = null): void {
		$name = $this->getName($publicKeyCredentialSource, $name);
		$entity = PublicKeyCredentialEntity::fromPublicKeyCrendentialSource(
			$name,
			$publicKeyCredentialSource,
			$this->time->getTime(),
		);
		$this->publicKeyCredentialEntityMapper->insertOrUpdate($entity);
	}

	private function getName(PublicKeyCredentialSource $publicKeyCredentialSource, ?string $name = null): string {
		if ($name !== null) {
			return $name;
		}

		$entity = $this->publicKeyCredentialEntityMapper->findPublicKeyCredential(base64_encode($publicKeyCredentialSource->publicKeyCredentialId), $publicKeyCredentialSource->userHandle);
		return $entity === null ? 'default' : $entity->getName();
	}
}
