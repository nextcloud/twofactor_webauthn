<?php

/**
 * @author Michael Blumenstein <M.Flower@gmx.de>
 * @copyright Copyright (c) 2019 Michael Blumenstein <M.Flower@gmx.de>
 *
 * Two-factor webauthn
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
 *
 * Software Credits
 *
 * The development of this software was made possible using the following components:
 *
 * twofactor_u2f (https://github.com/nextcloud/twofactor_u2f) by Christoph Wurst (https://github.com/ChristophWurst)
 * Licensed Under: AGPL
 * This project used the great twofactor provider u2f created by Christoph Wurst as a template.
 *
 * webauthn-framework (https://github.com/web-auth/webauthn-framework) by Florent Morselli (https://github.com/Spomky)
 * Licensed Under: MIT
 * The webauthn-framework provided most of the code and documentation for implementing the webauthn authentication.
 */

namespace OCA\TwoFactorWebauthn\Service;

use Assert\Assertion;
use CBOR\Decoder;
use CBOR\OtherObject\OtherObjectManager;
use CBOR\Tag\TagObjectManager;
use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature\ECDSA;
use Cose\Algorithm\Signature\EdDSA;
use Cose\Algorithm\Signature\RSA;
use Cose\Algorithms;
use Exception;
use OCA\TwoFactorWebauthn\Db\PublicKeyCredentialEntity;
use OCA\TwoFactorWebauthn\Db\PublicKeyCredentialEntityMapper;
use OCA\TwoFactorWebauthn\Event\StateChanged;
use OCA\TwoFactorWebauthn\Repository\WebauthnPublicKeyCredentialSourceRepository;
use OCP\ISession;
use OCP\IUser;
use Slim\Http\Environment;
use Slim\Http\Request;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
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
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\TokenBinding\TokenBindingNotSupportedHandler;

class WebauthnManager
{
    const TWOFACTORAUTH_WEBAUTHN_REGISTRATION = 'twofactorauth_webauthn_registration';
    const TWOFACTORAUTH_WEBAUTHN_REQUEST = 'twofactorauth_webauthn_request';
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
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;


    /**
     * WebauthnManager constructor.
     * @param ISession $session
     * @param WebauthnPublicKeyCredentialSourceRepository $repository
     * @param PublicKeyCredentialEntityMapper $mapper
     */
    public function __construct(
        ISession $session,
        WebauthnPublicKeyCredentialSourceRepository $repository,
        PublicKeyCredentialEntityMapper $mapper,
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->session = $session;
        $this->repository = $repository;
        $this->mapper = $mapper;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function startRegistration(IUser $user): PublicKeyCredentialCreationOptions
    {
        $rpEntity = new PublicKeyCredentialRpEntity(
            'Nextcloud', //Name
            null,           //ID
            null                            //Icon
        );

        $userEntity = new PublicKeyCredentialUserEntity(
            $user->getUID(),                                                //Name
            $user->getUID(),                              //ID
            $user->getDisplayName()                                                       //Display name
//            'https://foo.example.co/avatar/123e4567-e89b-12d3-a456-426655440000' //Icon
        );

        $challenge = random_bytes(32); // 32 bytes challenge

        $publicKeyCredentialParametersList = [
            new PublicKeyCredentialParameters('public-key', Algorithms::COSE_ALGORITHM_ES256),
            new PublicKeyCredentialParameters('public-key', Algorithms::COSE_ALGORITHM_RS256),
        ];

        $timeout = 60000;

        $excludedPublicKeyDescriptors = [
        ];

        $authenticatorSelectionCriteria = new AuthenticatorSelectionCriteria();

        $publicKeyCredentialCreationOptions = new PublicKeyCredentialCreationOptions(
            $rpEntity,
            $userEntity,
            $challenge,
            $publicKeyCredentialParametersList,
            $timeout,
            $excludedPublicKeyDescriptors,
            $authenticatorSelectionCriteria,
            PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            null
        );

        $this->session->set(self::TWOFACTORAUTH_WEBAUTHN_REGISTRATION, $publicKeyCredentialCreationOptions->jsonSerialize());

        return $publicKeyCredentialCreationOptions;
    }

    private function buildPacketAttestationStatementSupport(Decoder $decoder): PackedAttestationStatementSupport {
        // Cose Algorithm Manager
        $coseAlgorithmManager = new Manager();
        $coseAlgorithmManager->add(new ECDSA\ES256());
        $coseAlgorithmManager->add(new ECDSA\ES512());
        $coseAlgorithmManager->add(new EdDSA\EdDSA());
        $coseAlgorithmManager->add(new RSA\RS1());
        $coseAlgorithmManager->add(new RSA\RS256());
        $coseAlgorithmManager->add(new RSA\RS512());

        return new PackedAttestationStatementSupport($decoder, $coseAlgorithmManager);
    }

    private function buildAttestationStatementSupportManager(Decoder $decoder): AttestationStatementSupportManager {
        // Attestation Statement Support Manager
        $attestationStatementSupportManager = new AttestationStatementSupportManager();
        $attestationStatementSupportManager->add(new NoneAttestationStatementSupport());
        $attestationStatementSupportManager->add(new FidoU2FAttestationStatementSupport($decoder));
        $attestationStatementSupportManager->add(new AndroidKeyAttestationStatementSupport($decoder));
        $attestationStatementSupportManager->add(new TPMAttestationStatementSupport());
        $attestationStatementSupportManager->add($this->buildPacketAttestationStatementSupport($decoder));

        return $attestationStatementSupportManager;
    }

    private function buildDecoder(): Decoder {
        // Create a CBOR Decoder object
        $otherObjectManager = new OtherObjectManager();
        $tagObjectManager = new TagObjectManager();
        return new Decoder($tagObjectManager, $otherObjectManager);
    }

    public function buildPublicKeyCredentialLoader(AttestationStatementSupportManager $attestationStatementSupportManager, Decoder $decoder): PublicKeyCredentialLoader
    {
        // Attestation Object Loader
        $attestationObjectLoader = new AttestationObjectLoader($attestationStatementSupportManager, $decoder);

        // Public Key Credential Loader
        $publicKeyCredentialLoader = new PublicKeyCredentialLoader($attestationObjectLoader, $decoder);
        return $publicKeyCredentialLoader;
    }

    public function finishRegister(IUser $user, string $name, $data): array
    {
        if (!$this->session->exists(self::TWOFACTORAUTH_WEBAUTHN_REGISTRATION)) {
            throw new Exception('Twofactor Webauthn registration process was not properly initialized');
        }
        // Retrieve the PublicKeyCredentialCreationOptions object created earlier
        $publicKeyCredentialCreationOptions = PublicKeyCredentialCreationOptions::createFromArray($this->session->get(self::TWOFACTORAUTH_WEBAUTHN_REGISTRATION));

        $decoder = $this->buildDecoder();

        // The token binding handler
        $tokenBindingHandler = new TokenBindingNotSupportedHandler();

        $attestationStatementSupportManager = $this->buildAttestationStatementSupportManager($decoder);

        $publicKeyCredentialLoader = $this->buildPublicKeyCredentialLoader($attestationStatementSupportManager, $decoder);

        // Extension Output Checker Handler
        $extensionOutputCheckerHandler = new ExtensionOutputCheckerHandler();

// Authenticator Attestation Response Validator
        $authenticatorAttestationResponseValidator = new AuthenticatorAttestationResponseValidator(
            $attestationStatementSupportManager,
            $this->repository,
            $tokenBindingHandler,
            $extensionOutputCheckerHandler
        );

        try {
            // Load the data
            $publicKeyCredential = $publicKeyCredentialLoader->load($data);
            $response = $publicKeyCredential->getResponse();

            // Check if the response is an Authenticator Attestation Response
            if (!$response instanceof AuthenticatorAttestationResponse) {
                throw new \RuntimeException('Not an authenticator attestation response');
            }

            // Check the response against the request
            $request = Request::createFromEnvironment(new Environment($_SERVER));
            $authenticatorAttestationResponseValidator->check($response, $publicKeyCredentialCreationOptions, $request);
        } catch (Throwable $exception) {
            throw $exception;
        }

        // Everything is OK here.

        // You can get the Public Key Credential Source. This object should be persisted using the Public Key Credential Source repository
        $publicKeyCredentialSource = PublicKeyCredentialSource::createFromPublicKeyCredential(
            $publicKeyCredential,
            $publicKeyCredentialCreationOptions->getUser()->getId()
        );

        $this->repository->saveCredentialSource($publicKeyCredentialSource, $name);
        $this->eventDispatcher->dispatch(StateChanged::class, new StateChanged($user, true));

        return [
            'id' => base64_encode($publicKeyCredentialSource->getPublicKeyCredentialId()),
            'name' => $name
        ];
    }

    public function getDevices(IUser $user): array
    {
        $credentials = $this->mapper->findPublicKeyCredentials($user->getUID());
        return array_map(function (PublicKeyCredentialEntity $credential) {
            return [
                'id' => $credential->getPublicKeyCredentialId(),
                'name' => $credential->getName(),
            ];
        }, $credentials);
    }

    public function startAuthenticate(IUser $user): PublicKeyCredentialRequestOptions
    {
        // Extensions
        $extensions = new AuthenticationExtensionsClientInputs();
        $extensions->add(new AuthenticationExtension('loc', true));

        // List of registered PublicKeyCredentialDescriptor classes associated to the user
        $registeredPublicKeyCredentialDescriptors = array_map(function (PublicKeyCredentialEntity $credential) {
            return new PublicKeyCredentialDescriptor(
                $credential->getType(),
                base64_decode($credential->getPublicKeyCredentialId())
            );
        }, $this->mapper->findPublicKeyCredentials($user->getUID()));

        // Public Key Credential Request Options
        $publicKeyCredentialRequestOptions = new PublicKeyCredentialRequestOptions(
            random_bytes(32),                                                    // Challenge
            60000,                                                              // Timeout
            null,                                                                  // Relying Party ID
            $registeredPublicKeyCredentialDescriptors,                                  // Registered PublicKeyCredentialDescriptor classes
            PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_PREFERRED, // User verification requirement
            $extensions
        );

        $this->session->set(self::TWOFACTORAUTH_WEBAUTHN_REQUEST, $publicKeyCredentialRequestOptions->jsonSerialize());

        return $publicKeyCredentialRequestOptions;
    }

    public function finishAuthenticate(IUser $user, string $data)
    {

        if (!$this->session->exists(self::TWOFACTORAUTH_WEBAUTHN_REQUEST)) {
            throw new Exception('Twofactor Webauthn request process was not properly initialized');
        }

        // Retrieve the Options passed to the device
        $publicKeyCredentialRequestOptions = PublicKeyCredentialRequestOptions::createFromArray($this->session->get(self::TWOFACTORAUTH_WEBAUTHN_REQUEST));

        $decoder = $this->buildDecoder();

        $attestationStatementSupportManager = $this->buildAttestationStatementSupportManager($decoder);

        $publicKeyCredentialLoader = $this->buildPublicKeyCredentialLoader($attestationStatementSupportManager, $decoder);

        // Public Key Credential Source Repository
        $publicKeyCredentialSourceRepository = $this->repository;

        // The token binding handler
        $tokenBindingHandler = new TokenBindingNotSupportedHandler();

        // Extension Output Checker Handler
        $extensionOutputCheckerHandler = new ExtensionOutputCheckerHandler();

        // Authenticator Assertion Response Validator
        $authenticatorAssertionResponseValidator = new AuthenticatorAssertionResponseValidator(
            $publicKeyCredentialSourceRepository,
            $decoder,
            $tokenBindingHandler,
            $extensionOutputCheckerHandler
        );

        try {

            // Load the data
            $publicKeyCredential = $publicKeyCredentialLoader->load($data);
            $response = $publicKeyCredential->getResponse();

            // Check if the response is an Authenticator Assertion Response
            if (!$response instanceof AuthenticatorAssertionResponse) {
                throw new \RuntimeException('Not an authenticator assertion response');
            }

            $request = Request::createFromEnvironment(new Environment($_SERVER));

            // Check the response against the attestation request
            $authenticatorAssertionResponseValidator->check(
                $publicKeyCredential->getRawId(),
                $publicKeyCredential->getResponse(),
                $publicKeyCredentialRequestOptions,
                $request,
                $user->getUID() // User handle
            );

            return true;
        } catch (Exception $exception) {
            return false;
        }
    }

    public function removeDevice(IUser $user, string $id)
    {
        $credential = $this->mapper->findPublicKeyCredential($id);
        Assertion::eq($credential->getUserHandle(), $user->getUID());

        $this->mapper->delete($credential);

        $this->eventDispatcher->dispatch(StateChanged::class, new StateChanged($user, false));
    }

    public function removeAllDevices(IUser $user)
    {
        foreach ($this->mapper->findPublicKeyCredentials($user->getUID()) as $credential) {
            $this->mapper->delete($credential);
        }

        $this->eventDispatcher->dispatch(StateChanged::class, new StateChanged($user, false));
    }
}