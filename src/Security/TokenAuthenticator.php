<?php

/*
 * Copyright (c) 2022 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace Systemcheck\ContaoApiBundle\Security;

use Systemcheck\ContaoApiBundleException\ExpiredTokenException;
use Systemcheck\ContaoApiBundleException\InvalidJWTException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Hmac\Sha512;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\SignedWith;

class TokenAuthenticator extends AbstractAuthenticator //implements \Systemcheck\ContaoApiBundle\Security\User\UserInterface
{
    private Configuration $config;

    public function __construct(
        private \Systemcheck\ContaoApiBundle\Api\Security\JWTCoder $jwtCoder,
        private \Systemcheck\ContaoApiBundle\Security\User\UserProvider $userProvider
    )
    {}

    /**
     * Called on every request. Return whatever credentials you want to
     * be passed to getUser() as $credentials.
     */
    public function getCredentials(Request $request)
    {
        if (null !== ($locale = $request->getPreferredLanguage())) {
            $this->translator->setLocale($locale);
        }

        if (!$request->headers->has('Authorization')) {
            throw new AuthenticationException($this->translator->trans('systemcheck.api.exception.auth.missing_authorization_header'));
        }

        $headerParts = explode(' ', $request->headers->get('Authorization'));

        if (!(2 === \count($headerParts) && 'Bearer' === $headerParts[0])) {
            throw new AuthenticationException($this->translator->trans('systemcheck.api.exception.auth.malformed_authorization_header'));
        }

        if (!$request->query->get('key')) {
            throw new AuthenticationException($this->translator->trans('systemcheck.api.exception.auth.missing_api_key'));
        }

        return [
            'token' => $headerParts[1],
            'key' => $request->query->get('key'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        try {
            $payload = $this->jwtCoder->decode($credentials['token']);
        } catch (InvalidJWTException $e) {
            throw new AuthenticationException($this->translator->trans($e->getMessage()));
        } catch (ExpiredTokenException $e) {
            throw new AuthenticationException($this->translator->trans('systemcheck.api.exception.auth.malformed_jwt'));
        }

        if (!isset($payload->username)) {
            throw new AuthenticationException('systemcheck.api.exception.auth.invalid_jwt');
        }

        // if a Member object, checkCredentials() is called
        return $userProvider->loadUserByUsername(['username' => $payload->username, 'entity' => $payload->entity]);
    }

    /**
     * @var \Systemcheck\ContaoApiBundle\Api\Security\User\UserInterface
     *                                                            {@inheritdoc}
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        if (null === ($appModel = $this->container->get('systemcheck.api.model.app')->findPublishedByKey($credentials['key']))) {
            throw new AuthenticationException($this->translator->trans('systemcheck.api.exception.auth.invalid_api_key'));
        }

        if (false === $user->hasAppAccess($appModel)) {
            throw new AuthenticationException($this->translator->trans('systemcheck.api.exception.auth.user_not_allowed_for_api', ['%key%' => $credentials['key']]));
        }

        $user->setApp($appModel);

        if ($appModel->type === $this->container->get('systemcheck.api.manager.resource')::TYPE_ENTITY_RESOURCE) {
            $request = $this->container->get('request_stack')->getCurrentRequest();

            $action = $this->container->get('huh.utils.model')->callModelMethod(
                'tl_api_app_action', 'getCurrentActionConfig',
                $request->attributes->get('_route'), $appModel);

            $user->setAppAction($action);
        }

        // if user object is present here, JWT token did already match
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Request $request): ?bool
    {
        //this is used when try to fetch api
        /*return ('api' === $request->attributes->get('_scope') && $request->headers->has('AUTHORIZATION'));*/
        if ('api' === $request->attributes->get('_scope')) {
            
            return true;
        }

        return false;
    }

    public function authenticate(Request $request): Passport
    {
        
        //TODO neu token
        /*$token = $request->headers->get('authorization');
        if($token) {
            $token = str_replace("Bearer ", "", $token);

            $config = null;
            $secret = 'ed625c764398c552aa7837a8598338dc642003e593b140d27a8a81eea3322292';
            $this->config = $config ?: Configuration::forSymmetricSigner(new Sha512(), InMemory::plainText($secret));
            $this->config->setValidationConstraints(new SignedWith($this->config->signer(), $this->config->signingKey()));
            //$builder = $this->config->builder();

            $token = $this->config->parser()->parse($token);

            if (
                $token->isExpired(new \DateTimeImmutable())
                || !$this->config->validator()->validate($token, ...$this->config->validationConstraints())
            ) {
                return new SelfValidatingPassport(new UserBadge('null'));
                
            }
        
            $arr = array_map(
                static function ($value) {
                    if ($value instanceof \DateTimeInterface) {
                        return $value->format('U');
                    }

                    return (string) $value;
                },
                $token->claims()->all() 
            );
            dd($token);
            return new SelfValidatingPassport(new UserBadge($arr["username"]));
            return new Passport(
                new UserBadge((string)$arr["username"]),
                new PasswordCredentials((string) $arr["password"])
            );
            

            dd($arr);
        }*/
        //$this->userProvider->loadUserByIdentifier($request->getPayload()->get('username'));
        $token = $request->headers->get('Authorization');
        $token = str_replace('Bearer ', '', $token);
        //dd($token);
        try {
            $payload = $this->jwtCoder->decode($token);
        } catch (InvalidJWTException $e) {
            throw new AuthenticationException($this->translator->trans($e->getMessage()));
        } catch (ExpiredTokenException $e) {
            throw new AuthenticationException($this->translator->trans('systemcheck.api.exception.auth.malformed_jwt'));
        }
        
        if (!isset($payload["username"])) {
            throw new AuthenticationException('systemcheck.api.exception.auth.invalid_jwt');
        }

        //dd($request->attributes);
        // if a Member object, checkCredentials() is called
        $user =  $this->userProvider->loadUserByIdentifier($payload["username"]);//['username' => $payload->username, 'entity' => $payload->entity]);

        return new SelfValidatingPassport(new UserBadge($user->getUsername()));

        $data = $request->getContent();
        if($data != "") {
            $data = json_decode($data);
        }
        
        $password = $request->getPayload()->get('password');
        $username = $request->getPayload()->get('username');
        
        return new Passport(
            new UserBadge((string)$username),
            new PasswordCredentials((string) $password)
        );
        $apiToken = $request->headers->get('X-AUTH-TOKEN');
        if (null === $apiToken) {
            // The token header was empty, authentication fails with HTTP Status
            // Code 401 "Unauthorized"
            throw new CustomUserMessageAuthenticationException('No API token provided');
        }

        // implement your own logic to get the user identifier from `$apiToken`
        // e.g. by looking up a user in the database using its API key
        $userIdentifier = 'key';

        
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // on success, let the request continue
        //return new JsonResponse(['ok'], Response::HTTP_OK);
        
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $token = $request->headers->get('authorization');
        $token = str_replace("Bearer ", "", $token);
        $secret = 'ed625c764398c552aa7837a8598338dc642003e593b140d27a8a81eea3322292';
        $config = $this->config;
        $this->config = $config ?: Configuration::forSymmetricSigner(new Sha512(), InMemory::plainText($secret));
        $this->config->setValidationConstraints(new SignedWith($this->config->signer(), $this->config->signingKey()));
            //$builder = $this->config->builder();

            $token = $this->config->parser()->parse($token);

            if($token->isExpired(new \DateTimeImmutable())) {
                return new JsonResponse( [
                    'message' => "Deine Sitzung ist abgelaufen. Erneut einloggen"
                ], Response::HTTP_UNAUTHORIZED );
            }

        $data = [
            // you may want to customize or obfuscate the message first
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData())

            // or to translate this message
            // $this->translator->trans($exception->getMessageKey(), $exception->getMessageData())
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }
}
