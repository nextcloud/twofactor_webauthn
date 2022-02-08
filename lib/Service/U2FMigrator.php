<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\TwoFactorWebauthn\Service;

use OCA\TwoFactorWebauthn\Db\Registration;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Webauthn\AttestationStatement\AttestationStatement;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\TrustPath\EmptyTrustPath;

class U2FMigrator {

	/**
	 * @param string $data
	 * @return false|string
	 */
	private function base64_urlsafe_decode(string $data) {
		return base64_decode(str_replace(array('-', '_'), array('+', '/'), $data));
	}

	private function zeroUuid(): UuidInterface {
		return Uuid::fromString('00000000-0000-0000-0000-000000000000');
	}

	public function migrateU2FRegistration(Registration $registration): PublicKeyCredentialSource {
		// It is not recommended enabling attestation if not strictly
		// required. It is only required in super high security contexts
		// as it complicates the whole second factor UX.
		// Ref https://developers.yubico.com/WebAuthn/WebAuthn_Developer_Guide/Attestation.html
		// TL;DR: Attestation ensures that only specific/approved devices with
		// signed certificates can be used. Like TLS but for Webauthn devices.
		$attestationType = AttestationStatement::TYPE_NONE;
		$trustPath = new EmptyTrustPath();

		// Credential ID is the same as the legacy key handle
		$credentialId = $this->base64_urlsafe_decode($registration->getKeyHandle());

		// AAGUID is not required for legacy U2F sources and should be all zeros
		$aaguid = $this->zeroUuid();

		// Decode U2F key and reuse it
		// Raw format of u2f key: 0x4 . [x: 32 bytes] . [y: 32 bytes]
		$decodedPublicKey = base64_decode($registration->getPublicKey(), true);

		return new PublicKeyCredentialSource(
			$credentialId,
			PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
			[],
			$attestationType,
			$trustPath,
			$aaguid,
			$decodedPublicKey,
			$registration->getUserId(),
			$registration->getCounter(),
		);
	}
}
