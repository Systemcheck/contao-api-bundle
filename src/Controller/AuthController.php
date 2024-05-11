<?php

namespace Systemcheck\ContaoApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Connection;
//use Symfony\Component\Security\Core\Security;
use Symfony\Bundle\SecurityBundle\Security;
use Contao\CoreBundle\Controller\AbstractBackendController;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Driver\PDO\MySQL\Driver;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Hmac\Sha512;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\SignedWith;

use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;

#[Route('/api', name: ApiController::class, defaults: ['_scope' => 'frontend', '_token_check' => false])]
class AuthController  extends AbstractBackendController
{
    private $twig;
    private $security;

    public function __construct(private Connection $connection, Security $security)
    {
        //$this->twig = $twig;
        $this->security = $security;
    }

    #[Route('/login_check', name: 'index')]
    public function index(Request $request, )
    {
        $username = 'Systemcheck';
        $config = null;
        $secret = 'ed625c764398c552aa7837a8598338dc642003e593b140d27a8a81eea3322292';
        $this->config = $config ?: Configuration::forSymmetricSigner(new Sha512(), InMemory::plainText($secret));
        $this->config->setValidationConstraints(new SignedWith($this->config->signer(), $this->config->signingKey()));
        $builder = $this->config->builder();

        $payload = [
            'username' => 'systemcheck',
            'password' => '12345qwertz'
        ];

        foreach ($payload as $k => $v) {
            $builder = $builder->withClaim($k, $v);
        }

        $token = $builder
            ->issuedAt(new \DateTimeImmutable())
            ->expiresAt(new \DateTimeImmutable('now +30 minutes'))
            ->getToken($this->config->signer(), $this->config->signingKey())
        ;

        //dd($this->security->getFirewallConfig());$jwt = $this->jwt;
        $user = new Passport(
                new UserBadge((string)$payload["username"]),
                new PasswordCredentials((string) $payload["password"])
        );
        //$user = $this->userProvider->loadUserByIdentifier($username);

        //$usernamePasswordToken = new UsernamePasswordToken($username, $password, 'frontend', $user->getRoles());
        //$this->tokenStorage->setToken($usernamePasswordToken);

        $memberModel = \Contao\MemberModel::findByUsername($username);

        if (!$memberModel) {
            throw new UsernameNotFoundException(sprintf('User "%s" not found.', $username));
        }
        //\Contao\FrontendUser::getInstance()->authenticate();

        //$user =  \Contao\FrontendUser::find($membermodel->id);
        //$memberModel->login();
        //dd(new \Contao\CustomUser($memberModel->username, $memberModel->password, $memberModel->roles));
        //dd($user);
        return new JsonResponse(["token" => $token->toString()]); 
        dd($user);
        dd($this->getUser());
        dd($token->toString());

        return new Response('tst');
        if (!$this->security->isGranted('ROLE_MEMBER') ) {
            return new JsonResponse(['message' => 'Forbidden']);
        }
        return new JsonResponse(['Hello World!']);
    }
}