<?php

/** @noinspection PhpSignatureMismatchDuringInheritanceInspection */
/** @noinspection PhpHierarchyChecksInspection */

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

namespace OCA\TwoFactorWebauthn\Repository;

use OCA\TwoFactorWebauthn\Db\PublicKeyCredentialEntity;
use OCA\TwoFactorWebauthn\Db\PublicKeyCredentialEntityMapper;
use Webauthn\AttestedCredentialData;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\PublicKeyCredentialUserEntity;

class WebauthnPublicKeyCredentialSourceRepository implements PublicKeyCredentialSourceRepository {
	/**
	 * @var PublicKeyCredentialEntityMapper
	 */
	private $publicKeyCredentialEntityMapper;


	/**
	 * WebauthnPublicKeyCredentialSourceRepository constructor.
	 * @param PublicKeyCredentialEntityMapper $publicKeyCredentialEntityMapper
	 */
	public function __construct(PublicKeyCredentialEntityMapper $publicKeyCredentialEntityMapper) {
		$this->publicKeyCredentialEntityMapper = $publicKeyCredentialEntityMapper;
	}

	public function has(string $credentialId): bool {
		return false;
	}

	public function get(string $credentialId): AttestedCredentialData {
		return null;
	}

	public function getUserHandleFor(string  $credentialId): string {
		return null;
	}

	public function getCounterFor(string  $credentialId): int {
		return null;
	}

	public function updateCounterFor(string  $credentialId, int $newCounter): void {
		return;
	}

	public function findOneByCredentialId(string $publicKeyCredentialId): ?PublicKeyCredentialSource {
		$entity = $this->publicKeyCredentialEntityMapper->findPublicKeyCredential(base64_encode($publicKeyCredentialId));
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

	public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource, string $name = null): void {
		$name = $this->getName($publicKeyCredentialSource, $name);
		$entity = PublicKeyCredentialEntity::fromPublicKeyCrendentialSource($name, $publicKeyCredentialSource);
		$this->publicKeyCredentialEntityMapper->insertOrUpdate($entity);
	}

	private function getName(PublicKeyCredentialSource $publicKeyCredentialSource, string $name = null): string {
		if ($name !== null) {
			return $name;
		}

		$entity = $this->publicKeyCredentialEntityMapper->findPublicKeyCredential(base64_encode($publicKeyCredentialSource->getPublicKeyCredentialId()));
		return $entity === null ? 'default' : $entity->getName();
	}
}
