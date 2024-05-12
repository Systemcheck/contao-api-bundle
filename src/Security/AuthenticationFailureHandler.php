<?php

namespace Systemcheck\ContaoApiBundle\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;
use Psr\Log\LoggerInterface;

/**
 * AuthenticationSuccessHandler.
 *
 * @author Dev Lexik <dev@lexik.fr>
 * @author Robin Chalas <robin.chalas@gmail.com>
 *
 * @final
 */
class AuthenticationFailureHandler implements AuthenticationFailureHandlerInterface
{
    private User|null $user = null;

    /**
     * @internal
     */
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly FirewallMap $firewallMap,
        private readonly LoggerInterface|null $logger = null,
    ) {
    }

    /**
     * Redirects the authenticated user.
     *
     * @return RedirectResponse
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {

        return  new JsonResponse(['message' => $exception->getMessage(), "code" => Response::HTTP_UNAUTHORIZED] , Response::HTTP_UNAUTHORIZED);
        $user = $token->getUser();

        if (!$user instanceof User) {
            return new RedirectResponse($this->determineTargetUrl($request));
        }
        
        $this->user = $user;

        if ($token instanceof TwoFactorTokenInterface) {
            $this->user->save();

            $response = new RedirectResponse($request->getUri());

            // Used by the TwoFactorListener to redirect a user back to the authentication page
            if ($request->hasSession() && $request->isMethodSafe() && !$request->isXmlHttpRequest()) {
                $this->saveTargetPath($request->getSession(), $token->getFirewallName(), $request->getUri());
            }

            return $response;
        }

        $this->user->lastLogin = $this->user->currentLogin;
        $this->user->currentLogin = time();
        $this->user->save();
        
        if ($request->request->has('trusted')) {
            $firewallConfig = $this->firewallMap->getFirewallConfig($request);

            if (!$this->trustedDeviceManager->isTrustedDevice($user, $firewallConfig->getName())) {
                $this->trustedDeviceManager->addTrustedDevice($token->getUser(), $firewallConfig->getName());
            }
        }


        $this->logger?->info(
            sprintf('User "%s" has logged in', $this->user->username),
            ['contao' => new ContaoContext(__METHOD__, ContaoContext::ACCESS, $this->user->username)]
        );

        $config = null;
        $secret = 'ed625c764398c552aa7837a8598338dc642003e593b140d27a8a81eea3322292';
        $this->config = $config ?: Configuration::forSymmetricSigner(new Sha512(), InMemory::plainText($secret));
        $this->config->setValidationConstraints(new SignedWith($this->config->signer(), $this->config->signingKey()));
        $builder = $this->config->builder();

        $payload = [
            'username' => $this->user->username
        ];
        
        foreach ($payload as $k => $v) {
            $builder = $builder->withClaim($k, $v);
        }

        $token = $builder
            ->issuedAt(new \DateTimeImmutable())
            ->expiresAt(new \DateTimeImmutable('now +30 minutes'))
            ->getToken($this->config->signer(), $this->config->signingKey())
        ;

        return new JsonResponse([
            "token" => $token->toString(),
            "username" => $this->user->username,
            'roles' => $this->user->getRoles(),
            "email" => $this->user->email,        
            "user" => [
                "firstname" => 'firstname',
                "lastname" => "lastname"            
            ]
        ]);
        
    }


}
