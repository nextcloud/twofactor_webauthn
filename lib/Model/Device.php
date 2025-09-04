<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TwoFactorWebauthn\Model;

use OCA\TwoFactorWebauthn\Db\PublicKeyCredentialEntity;

final class Device implements \JsonSerializable {
	public function __construct(
		private readonly int $entityId,
		private readonly string $id,
		private readonly string $name,
		private readonly ?int $createdAt,
		private readonly bool $active,
	) {
	}

	public static function fromPublicKeyCredentialEntity(PublicKeyCredentialEntity $entity): self {
		return new self(
			$entity->getId(),
			$entity->getPublicKeyCredentialId(),
			$entity->getName(),
			$entity->getCreatedAt(),
			$entity->isActive() === true,
		);
	}

	public function getEntityId(): int {
		return $this->entityId;
	}

	public function getId(): string {
		return $this->id;
	}

	public function getName(): string {
		return $this->name;
	}

	public function getCreatedAt(): ?int {
		return $this->createdAt;
	}

	public function isActive(): bool {
		return $this->active;
	}

	#[\ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'entityId' => $this->getEntityId(),
			'id' => $this->getId(),
			'name' => $this->getName(),
			'createdAt' => $this->getCreatedAt(),
			'active' => $this->isActive(),
		];
	}
}
