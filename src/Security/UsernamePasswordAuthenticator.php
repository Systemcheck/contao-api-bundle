<?php

/*
 * Copyright (c) 2022 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace Systemcheck\ContaoApiBundle\Security;

use Contao\Config;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Hmac\Sha512;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\SignedWith;

class UsernamePasswordAuthenticator extends AbstractAuthenticator
{
    /**
     * {@inheritdoc}
     */
    public function getCredentials(Request $request)
    {
        if (null !== ($locale = $request->getPreferredLanguage())) {
            $this->translator->setLocale($locale);
        }

        if ('POST' !== $request->getMethod()) {
            throw new AuthenticationException($this->translator->trans('systemcheck.api.exception.auth.post_method_only'));
        }

        return [
            'username' => $request->getUser() ?: $request->request->get('username'),
            'password' => $request->getPassword() ?: $request->request->get('password'),
            'entity' => $request->attributes->get('_entity'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        if (method_exists($userProvider, 'loadUserByIdentifier')) {
            return $userProvider->loadUserByIdentifier($credentials['username']);
        }

        return $userProvider->loadUserByUsername($credentials['username']);
    }

    /**
     * {@inheritdoc}
     *
     * @var \Systemcheck\ContaoApiBundle\Api\Security\User\UserInterface
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        $time = time();
        $authenticated = password_verify($credentials['password'], $user->getPassword());
        $needsRehash = password_needs_rehash($user->getPassword(), \PASSWORD_DEFAULT);

        // Re-hash the password if the algorithm has changed
        if ($authenticated && $needsRehash) {
            $this->password = password_hash($credentials['password'], \PASSWORD_DEFAULT);
        }

        // HOOK: pass credentials to callback functions
        if (!$authenticated && isset($GLOBALS['TL_HOOKS']['checkCredentials']) && \is_array($GLOBALS['TL_HOOKS']['checkCredentials'])) {
            /** @var System $system */
            $system = $this->framework->getAdapter(System::class);

            foreach ($GLOBALS['TL_HOOKS']['checkCredentials'] as $callback) {
                $authenticated = $system->importStatic($callback[0], 'auth', true)->{$callback[1]}($credentials['username'], $credentials['password'], $user);

                // Authentication successfull
                if (true === $authenticated) {
                    break;
                }
            }
        }

        if (!$authenticated) {
            $user->loginCount = ($user->loginCount - 1);
            $user->save();

            throw new AuthenticationException($this->translator->trans('systemcheck.api.exception.auth.invalid_credentials'));
        }

        /** @var Config $config */
        $config = $this->framework->getAdapter(Config::class);

        $user->lastLogin = $user->currentLogin;
        $user->currentLogin = $time;
        $user->loginCount = (int) $config->get('loginCount');
        $user->save();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Request $request): ?bool
    {
        
        if (\in_array($request->attributes->get('_scope'), ['api', 'api_login_user', 'api_login_member'])) {
            return true;
        }

        return false;
    }

    public function authenticate(Request $request): Passport
    {
        
        $token = $request->headers->get('authorization');
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
            return new SelfValidatingPassport(new UserBadge($arr["username"]));
            return new Passport(
                new UserBadge((string)$arr["username"]),
                new PasswordCredentials((string) $arr["password"])
            );
            

            dd($arr);
        }
        dd($token);
        //dd($request->headers->get('AUTHORIZATION'));
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
