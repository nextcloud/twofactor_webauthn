<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\TwoFactorWebauthn\Service;

use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature\ECDSA;
use Cose\Algorithm\Signature\EdDSA;
use Cose\Algorithm\Signature\RSA;
use Cose\Algorithms;
use Exception;
use OCA\TwoFactorWebauthn\Db\PublicKeyCredentialEntity;
use OCA\TwoFactorWebauthn\Db\PublicKeyCredentialEntityMapper;
use OCA\TwoFactorWebauthn\Event\StateChanged;
use OCA\TwoFactorWebauthn\Model\Device;
use OCA\TwoFactorWebauthn\Repository\WebauthnPublicKeyCredentialSourceRepository;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUser;
use OCP\Security\ISecureRandom;
use Psr\Log\LoggerInterface;
use Throwable;
use Webauthn\AttestationStatement\AndroidKeyAttestationStatementSupport;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\FidoU2FAttestationStatementSupport;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AttestationStatement\PackedAttestationStatementSupport;
use Webauthn\AttestationStatement\TPMAttestationStatementSupport;
use Webauthn\AuthenticationExtensions\AuthenticationExtension;
use Webauthn\AuthenticationExtensions\AuthenticationExtensionsClientInputs;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\TokenBinding\TokenBindingNotSupportedHandler;

class WebAuthnManager {
	public const TWOFACTORAUTH_WEBAUTHN_REGISTRATION = 'twofactorauth_webauthn_regs';
	public const TWOFACTORAUTH_WEBAUTHN_REQUEST = 'twofactorauth_webauthn_request';
	/**
	 * @var ISession
	 */
	private $session;
	/**
	 * @var WebauthnPublicKeyCredentialSourceRepository
	 */
	private $repository;
	/**
	 * @var PublicKeyCredentialEntityMapper
	 */
	private $mapper;
	/**
	 * @var IEventDispatcher
	 */
	private $eventDispatcher;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(
		ISession $session,
		WebauthnPublicKeyCredentialSourceRepository $repository,
		PublicKeyCredentialEntityMapper $mapper,
		IEventDispatcher $eventDispatcher,
		LoggerInterface $logger,
		private readonly IRequest $request,
		private readonly ISecureRandom $random,
		private readonly ITimeFactory $time,
	) {
		$this->session = $session;
		$this->repository = $repository;
		$this->mapper = $mapper;
		$this->eventDispatcher = $eventDispatcher;
		$this->logger = $logger;
	}

	public function startRegistration(IUser $user, string $serverHost): PublicKeyCredentialCreationOptions {
		$rpEntity = new PublicKeyCredentialRpEntity(
			'Nextcloud',
			$this->stripPort($serverHost),
			null,
		);

		$userEntity = new PublicKeyCredentialUserEntity(
			$user->getUID(),
			$user->getUID(),
			$user->getDisplayName(),
			null,
		);

		$challenge = $this->random->generate(32);

		$timeout = 60000;

		$publicKeyCredentialParametersList = [
			new PublicKeyCredentialParameters('public-key', Algorithms::COSE_ALGORITHM_ES256),
			new PublicKeyCredentialParameters('public-key', Algorithms::COSE_ALGORITHM_RS256),
		];

		$excludedPublicKeyDescriptors = [
		];

		$authenticatorSelectionCriteria = new AuthenticatorSelectionCriteria(
			AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_NO_PREFERENCE,
			AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_DISCOURAGED,
			AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_NO_PREFERENCE,
			false,
		);

		$publicKeyCredentialCreationOptions = new PublicKeyCredentialCreationOptions(
			$rpEntity,
			$userEntity,
			$challenge,
			$publicKeyCredentialParametersList,
			$authenticatorSelectionCriteria,
			PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
			$excludedPublicKeyDescriptors,
			$timeout,
		);

		$this->session->set(self::TWOFACTORAUTH_WEBAUTHN_REGISTRATION, $publicKeyCredentialCreationOptions->jsonSerialize());

		return $publicKeyCredentialCreationOptions;
	}

	private function buildPacketAttestationStatementSupport(): PackedAttestationStatementSupport {
		// Cose Algorithm Manager
		$coseAlgorithmManager = new Manager();
		$coseAlgorithmManager->add(new ECDSA\ES256());
		$coseAlgorithmManager->add(new ECDSA\ES512());
		$coseAlgorithmManager->add(new EdDSA\EdDSA());
		$coseAlgorithmManager->add(new RSA\RS1());
		$coseAlgorithmManager->add(new RSA\RS256());
		$coseAlgorithmManager->add(new RSA\RS512());

		return new PackedAttestationStatementSupport($coseAlgorithmManager);
	}

	private function buildAttestationStatementSupportManager(): AttestationStatementSupportManager {
		// Attestation Statement Support Manager
		$attestationStatementSupportManager = new AttestationStatementSupportManager();
		$attestationStatementSupportManager->add(new NoneAttestationStatementSupport());
		$attestationStatementSupportManager->add(new FidoU2FAttestationStatementSupport());
		$attestationStatementSupportManager->add(new AndroidKeyAttestationStatementSupport());
		$attestationStatementSupportManager->add(new TPMAttestationStatementSupport());
		$attestationStatementSupportManager->add($this->buildPacketAttestationStatementSupport());

		return $attestationStatementSupportManager;
	}

	public function buildPublicKeyCredentialLoader(AttestationStatementSupportManager $attestationStatementSupportManager): PublicKeyCredentialLoader {
		// Attestation Object Loader
		$attestationObjectLoader = new AttestationObjectLoader($attestationStatementSupportManager);

		// Public Key Credential Loader
		$publicKeyCredentialLoader = new PublicKeyCredentialLoader($attestationObjectLoader);
		return $publicKeyCredentialLoader;
	}

	public function finishRegister(IUser $user, string $name, $data): Device {
		if (!$this->session->exists(self::TWOFACTORAUTH_WEBAUTHN_REGISTRATION)) {
			throw new Exception('Twofactor Webauthn registration process was not properly initialized');
		}
		// Retrieve the PublicKeyCredentialCreationOptions object created earlier
		$publicKeyCredentialCreationOptions = PublicKeyCredentialCreationOptions::createFromArray($this->session->get(self::TWOFACTORAUTH_WEBAUTHN_REGISTRATION));

		// The token binding handler
		$tokenBindingHandler = new TokenBindingNotSupportedHandler();

		$attestationStatementSupportManager = $this->buildAttestationStatementSupportManager();

		$publicKeyCredentialLoader = $this->buildPublicKeyCredentialLoader($attestationStatementSupportManager);

		// Extension Output Checker Handler
		$extensionOutputCheckerHandler = new ExtensionOutputCheckerHandler();

		// Authenticator Attestation Response Validator
		$authenticatorAttestationResponseValidator = new AuthenticatorAttestationResponseValidator(
			$attestationStatementSupportManager,
			$this->repository,
			$tokenBindingHandler,
			$extensionOutputCheckerHandler
		);

		// Load the data
		$publicKeyCredential = $publicKeyCredentialLoader->load($data);
		$response = $publicKeyCredential->getResponse();

		// Check if the response is an Authenticator Attestation Response
		if (!$response instanceof AuthenticatorAttestationResponse) {
			throw new \RuntimeException('Not an authenticator attestation response');
		}

		// Check the response against the request
		$publicKeyCredentialSource = $authenticatorAttestationResponseValidator->check(
			$response,
			$publicKeyCredentialCreationOptions,
			$this->stripPort($this->request->getServerHost()),
		);

		$entity = PublicKeyCredentialEntity::fromPublicKeyCrendentialSource(
			$name,
			$publicKeyCredentialSource,
			$this->time->getTime(),
		);
		$entity = $this->mapper->insert($entity);
		$this->eventDispatcher->dispatch(StateChanged::class, new StateChanged($user, true));
		return Device::fromPublicKeyCredentialEntity($entity);
	}

	/**
	 * @param IUser $user
	 * @return Device[]
	 */
	public function getDevices(IUser $user): array {
		$credentials = $this->mapper->findPublicKeyCredentials($user->getUID());
		return array_map(Device::fromPublicKeyCredentialEntity(...), $credentials);
	}

	private function stripPort(string $serverHost): string {
		/** @var ?string $serverHostWithoutPort */
		$serverHostWithoutPort = preg_replace('/(:\d+$)/', '', $serverHost);
		if ($serverHostWithoutPort === null) {
			throw new \RuntimeException("Failed to strip port from server host: preg_replace returned null (serverHost=$serverHost)");
		}

		return $serverHostWithoutPort;
	}

	public function startAuthenticate(IUser $user, string $serverHost): PublicKeyCredentialRequestOptions {
		// Extensions
		$extensions = new AuthenticationExtensionsClientInputs();
		$extensions->add(new AuthenticationExtension('loc', true));
		$extensions->add(new AuthenticationExtension('appid', "https://$serverHost"));

		$activeDevices = array_filter(
			$this->mapper->findPublicKeyCredentials($user->getUID()),
			function ($device) {
				return ($device->isActive() === true);
			}
		);

		// List of registered PublicKeyCredentialDescriptor classes associated to the user
		$registeredPublicKeyCredentialDescriptors = array_map(function (PublicKeyCredentialEntity $credential) {
			return $credential->toPublicKeyCredentialSource()->getPublicKeyCredentialDescriptor();
		}, $activeDevices);

		$publicKeyCredentialRequestOptions = new PublicKeyCredentialRequestOptions(
			$this->random->generate(32),
			null,
			[],
			null,
			60000,
			$extensions,
		);
		$publicKeyCredentialRequestOptions
			->setRpId($this->stripPort($serverHost))
			->allowCredentials(...$registeredPublicKeyCredentialDescriptors)
			->setUserVerification(PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_DISCOURAGED);

		$this->session->set(self::TWOFACTORAUTH_WEBAUTHN_REQUEST, $publicKeyCredentialRequestOptions->jsonSerialize());

		return $publicKeyCredentialRequestOptions;
	}

	public function finishAuthenticate(IUser $user, string $data) {
		if (!$this->session->exists(self::TWOFACTORAUTH_WEBAUTHN_REQUEST)) {
			throw new Exception('Twofactor Webauthn request process was not properly initialized');
		}

		// Retrieve the Options passed to the device
		$publicKeyCredentialRequestOptions = PublicKeyCredentialRequestOptions::createFromArray($this->session->get(self::TWOFACTORAUTH_WEBAUTHN_REQUEST));

		$attestationStatementSupportManager = $this->buildAttestationStatementSupportManager();

		$publicKeyCredentialLoader = $this->buildPublicKeyCredentialLoader($attestationStatementSupportManager);

		// Public Key Credential Source Repository
		$publicKeyCredentialSourceRepository = $this->repository;

		// The token binding handler
		$tokenBindingHandler = new TokenBindingNotSupportedHandler();

		// Extension Output Checker Handler
		$extensionOutputCheckerHandler = new ExtensionOutputCheckerHandler();

		$coseAlgorithmManager = new Manager();
		$coseAlgorithmManager->add(new ECDSA\ES256());
		$coseAlgorithmManager->add(new RSA\RS256());

		// Authenticator Assertion Response Validator
		$authenticatorAssertionResponseValidator = new AuthenticatorAssertionResponseValidator(
			$publicKeyCredentialSourceRepository,
			$tokenBindingHandler,
			$extensionOutputCheckerHandler,
			$coseAlgorithmManager
		);

		try {

			// Load the data
			$publicKeyCredential = $publicKeyCredentialLoader->load($data);
			$response = $publicKeyCredential->getResponse();

			// Check if the response is an Authenticator Assertion Response
			if (!$response instanceof AuthenticatorAssertionResponse) {
				throw new \RuntimeException('Not an authenticator assertion response');
			}

			// Check the response against the attestation request
			$authenticatorAssertionResponseValidator->check(
				$publicKeyCredential->getRawId(),
				$publicKeyCredential->getResponse(),
				$publicKeyCredentialRequestOptions,
				$this->stripPort($this->request->getServerHost()),
				$user->getUID() // User handle
			);

			return true;
		} catch (Throwable $e) {
			$this->logger->error('Could not verify WebAuthn: ' . $e->getMessage(), [
				'exception' => $e,
			]);

			return false;
		}
	}

	public function removeDevice(IUser $user, int $id) {
		$credential = $this->mapper->findById($id, $user->getUID());

		$this->mapper->delete($credential);

		$this->eventDispatcher->dispatchTyped(new StateChanged($user, false));
	}

	public function deactivateAllDevices(IUser $user) {
		foreach ($this->mapper->findPublicKeyCredentials($user->getUID()) as $credential) {
			$credential->setActive(false);
			$this->mapper->update($credential);
		}

		$this->eventDispatcher->dispatchTyped(new StateChanged($user, false, true));
	}

	public function changeActivationState(IUser $user, int $id, bool $active) {
		$credential = $this->mapper->findById($id, $user->getUID());

		$credential->setActive($active);

		$this->mapper->update($credential);

		$this->eventDispatcher->dispatchTyped(new StateChanged($user, $active));
	}
}
