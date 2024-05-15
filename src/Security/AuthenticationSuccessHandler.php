<?php

namespace Systemcheck\ContaoApiBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\FrontendUser;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\User;
use Psr\Log\LoggerInterface;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Trusted\TrustedDeviceManagerInterface;
use Symfony\Bundle\SecurityBundle\Security\FirewallMap;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Hmac\Sha512;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
/**
 * AuthenticationSuccessHandler.
 *
 * @author Dev Lexik <dev@lexik.fr>
 * @author Robin Chalas <robin.chalas@gmail.com>
 *
 * @final
 */
class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    private User|null $user = null;

    /**
     * @internal
     */
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly TrustedDeviceManagerInterface $trustedDeviceManager,
        private readonly FirewallMap $firewallMap,
        private readonly LoggerInterface|null $logger = null,
    ) {
    }

    /**
     * Redirects the authenticated user.
     *
     * @return RedirectResponse
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        
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
            "id" => $this->user->id,       
            "member" => [
                "firstname" => $this->user->firstname,
                "lastname" => $this->user->lastname,
                "id" => $this->user->id           
            ],
            "notifications" => []
        ]);
        
    }

    protected function determineTargetUrl(Request $request): string
    {
        if (!$this->user instanceof FrontendUser || $request->request->get('_always_use_target_path')) {
            return $this->decodeTargetPath($request);
        }

        $pageModelAdapter = $this->framework->getAdapter(PageModel::class);
        $groups = StringUtil::deserialize($this->user->groups, true);
        $groupPage = $pageModelAdapter->findFirstActiveByMemberGroups($groups);

        if ($groupPage instanceof PageModel) {
            return $groupPage->getAbsoluteUrl();
        }

        return $this->decodeTargetPath($request);
    }

    private function decodeTargetPath(Request $request): string
    {
        $targetPath = $request->request->get('_target_path');

        if (!\is_string($targetPath)) {
            throw new BadRequestHttpException('Missing form field "_target_path". You probably need to adjust your custom login template.');
        }

        return base64_decode($targetPath, true);
    }
}
