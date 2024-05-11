<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace Systemcheck\ContaoApiBundleTest\Controller;

use Contao\TestCase\ContaoTestCase;
use Systemcheck\ContaoApiBundle\Controller\LoginController;
use Systemcheck\ContaoApiBundleEntity\User;
use Systemcheck\ContaoApiBundle\Api\Security\JWTCoder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class LoginControllerTest extends ContaoTestCase
{
    public function testCanBeInstantiated(): void
    {
        $controller = new LoginController();

        $this->assertInstanceOf('Systemcheck\ContaoApiBundle\Controller\LoginController', $controller);
    }

    /**
     * Test that login actions will return a json response.
     */
    public function testReturnsAResponseInTheActionMethods(): void
    {
        $user = $this->createMock(User::class);

        $container = $this->mockContainer();
        $container->set('systemcheck.api.jwt_coder', new JWTCoder('secret'));
        $authenticatedToken = $this->createMock(TokenInterface::class);
        $authenticatedToken->expects($this->any())->method('getUser')->willReturn($user);

        $tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)->getMock();
        $tokenStorage->method('getToken')->willReturn($authenticatedToken);
        $container->set('security.token_storage', $tokenStorage);

        $controller = new LoginController();
        $controller->setContainer($container);

        $this->assertInstanceOf(JsonResponse::class, $controller->loginMemberAction(new Request()));
        $this->assertInstanceOf(JsonResponse::class, $controller->loginUserAction(new Request()));
    }
}
