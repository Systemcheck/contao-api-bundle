<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace Systemcheck\ContaoApiBundleTest\Controller;

use Contao\MemberModel;
use Contao\TestCase\ContaoTestCase;
use Contao\UserModel;
use Systemcheck\ContaoApiBundle\Api\Resource\MemberResource;
use Systemcheck\ContaoApiBundle\Controller\ResourceController;
use Systemcheck\ContaoApiBundleEntity\User;
use Systemcheck\ContaoApiBundle\Manager\ApiResourceManager;
use Systemcheck\ContaoApiBundle\Model\ApiAppModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Translation\Translator;

class ResourceControllerTest extends ContaoTestCase
{
    public function testCanBeInstantiated(): void
    {
        $controller = new ResourceController();

        $this->assertInstanceOf('Systemcheck\ContaoApiBundle\Controller\ResourceController', $controller);
    }

    /**
     * Test that actions will return a json response.
     */
    public function testReturnsAResponseInTheActionMethods(): void
    {
        $container = $this->mockContainer();
        $container->set('systemcheck.api.manager.resource', new ApiResourceManager($this->mockContaoFramework()));
        $container->set('translator', new Translator('en'));

        $controller = new ResourceController();
        $controller->setContainer($container);

        $this->assertInstanceOf(JsonResponse::class, $controller->createAction('member', new Request()));
        $this->assertInstanceOf(JsonResponse::class, $controller->updateAction(1, 'member', new Request()));
        $this->assertInstanceOf(JsonResponse::class, $controller->listAction('member', new Request()));
        $this->assertInstanceOf(JsonResponse::class, $controller->showAction(1, 'member', new Request()));
        $this->assertInstanceOf(JsonResponse::class, $controller->deleteAction(1, 'member', new Request()));
    }

    /**
     * Test that actions will check if resource does exist.
     */
    public function testActionIsAvailable(): void
    {
        $user = $this->createMock(User::class);

        $resourceManager = new ApiResourceManager($this->mockContaoFramework());
        $resourceManager->add(new MemberResource(), 'member', 'systemcheck.api.resource.member');

        $container = $this->mockContainer();
        $authenticatedToken = $this->createMock(TokenInterface::class);
        $authenticatedToken->expects($this->any())->method('getUser')->willReturn($user);
        $tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)->getMock();
        $tokenStorage->method('getToken')->willReturn($authenticatedToken);
        $container->set('security.token_storage', $tokenStorage);
        $container->set('systemcheck.api.manager.resource', $resourceManager);
        $container->set('translator', new Translator('en'));

        $controller = new ResourceController();
        $controller->setContainer($container);

        $this->assertNotEquals('systemcheck.api.exception.resource_not_existing', json_decode($controller->createAction('member', new Request())->getContent())->message);
        $this->assertEquals('systemcheck.api.exception.resource_not_existing', json_decode($controller->createAction('test', new Request())->getContent())->message);
        $this->assertNotEquals('systemcheck.api.exception.resource_not_existing', json_decode($controller->updateAction('member', 1, new Request())->getContent())->message);
        $this->assertEquals('systemcheck.api.exception.resource_not_existing', json_decode($controller->updateAction('test', 1, new Request())->getContent())->message);
        $this->assertNotEquals('systemcheck.api.exception.resource_not_existing', json_decode($controller->listAction('member', new Request())->getContent())->message);
        $this->assertEquals('systemcheck.api.exception.resource_not_existing', json_decode($controller->listAction('test', new Request())->getContent())->message);
        $this->assertNotEquals('systemcheck.api.exception.resource_not_existing', json_decode($controller->showAction('member', 1, new Request())->getContent())->message);
        $this->assertEquals('systemcheck.api.exception.resource_not_existing', json_decode($controller->showAction('test', 1, new Request())->getContent())->message);
        $this->assertNotEquals('systemcheck.api.exception.resource_not_existing', json_decode($controller->deleteAction('member', 1, new Request())->getContent())->message);
        $this->assertEquals('systemcheck.api.exception.resource_not_existing', json_decode($controller->deleteAction('test', 1, new Request())->getContent())->message);
    }

    /**
     * Test that actions will check if user has access to resource.
     */
    public function testActionWithoutGroups(): void
    {
        $user = $this->createMock(User::class);

        $resourceManager = new ApiResourceManager($this->mockContaoFramework());
        $resourceManager->add(new MemberResource(), 'member', 'systemcheck.api.resource.member');

        $container = $this->mockContainer();
        $authenticatedToken = $this->createMock(TokenInterface::class);
        $authenticatedToken->expects($this->any())->method('getUser')->willReturn($user);
        $tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)->getMock();
        $tokenStorage->method('getToken')->willReturn($authenticatedToken);
        $container->set('security.token_storage', $tokenStorage);
        $container->set('systemcheck.api.manager.resource', $resourceManager);
        $container->set('translator', new Translator('en'));

        $controller = new ResourceController();
        $controller->setContainer($container);

        $this->assertEquals('systemcheck.api.exception.resource_action_not_allowed', json_decode($controller->createAction('member', new Request())->getContent())->message);
        $this->assertEquals('systemcheck.api.exception.resource_action_not_allowed', json_decode($controller->updateAction('member', 1, new Request())->getContent())->message);
        $this->assertEquals('systemcheck.api.exception.resource_action_not_allowed', json_decode($controller->listAction('member', new Request())->getContent())->message);
        $this->assertEquals('systemcheck.api.exception.resource_action_not_allowed', json_decode($controller->showAction('member', 1, new Request())->getContent())->message);
        $this->assertEquals('systemcheck.api.exception.resource_action_not_allowed', json_decode($controller->deleteAction('member', 1, new Request())->getContent())->message);
    }

    /**
     * Test that actions will check if user has access to resource if no resource actions available.
     */
    public function testActionWithoutResourceActions(): void
    {
        $userModel = $this->mockClassWithProperties(UserModel::class, []);
        $userModel->method('current')->willReturnSelf();

        $user = new User($this->mockContaoFramework());
        $user->setModel($userModel);

        $appModel = $this->mockClassWithProperties(ApiAppModel::class, ['resourceActions' => []]);
        $appModel->method('current')->willReturnSelf();
        $user->setApp($appModel);

        $memberModelAdapter = $this->mockAdapter(['getPk', 'findByPk']);
        $memberModelAdapter->method('getPk')->willReturn('id');
        $memberModelAdapter->method('findByPk')->willReturn(null);

        $framework = $this->mockContaoFramework(
            [
                MemberModel::class => $memberModelAdapter,
            ]
        );

        $container = $this->mockContainer();
        $container->set('translator', new Translator('en'));

        $memberResource = new MemberResource();
        $memberResource->setFramework($framework);
        $memberResource->setContainer($container);

        $resourceManager = new ApiResourceManager($framework);
        $resourceManager->add($memberResource, 'member', 'systemcheck.api.resource.member');

        $authenticatedToken = $this->createMock(TokenInterface::class);
        $authenticatedToken->expects($this->any())->method('getUser')->willReturn($user);
        $tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)->getMock();
        $tokenStorage->method('getToken')->willReturn($authenticatedToken);
        $container->set('security.token_storage', $tokenStorage);
        $container->set('systemcheck.api.manager.resource', $resourceManager);

        $controller = new ResourceController();
        $controller->setContainer($container);

        $request = new Request();
        $request->attributes->set('_route', 'api_resource_create');
        $this->assertEquals('systemcheck.api.exception.resource_action_not_allowed', json_decode($controller->createAction('member', $request)->getContent())->message);
        $request->attributes->set('_route', 'api_resource_update');
        $this->assertEquals('systemcheck.api.exception.resource_action_not_allowed', json_decode($controller->updateAction('member', 1, $request)->getContent())->message);
        $request->attributes->set('_route', 'api_resource_list');
        $this->assertEquals('systemcheck.api.exception.resource_action_not_allowed', json_decode($controller->listAction('member', $request)->getContent())->message);
        $request->attributes->set('_route', 'api_resource_show');
        $this->assertEquals('systemcheck.api.exception.resource_action_not_allowed', json_decode($controller->showAction('member', 1, $request)->getContent())->message);
        $request->attributes->set('_route', 'api_resource_delete');
        $this->assertEquals('systemcheck.api.exception.resource_action_not_allowed', json_decode($controller->deleteAction('member', 1, $request)->getContent())->message);
    }

    /**
     * Test that actions will check if user has access to resource.
     */
    public function testActionWithoutActionAccess(): void
    {
        $userModel = $this->mockClassWithProperties(UserModel::class, []);
        $userModel->method('current')->willReturnSelf();

        $user = new User($this->mockContaoFramework());
        $user->setModel($userModel);

        $appModel = $this->mockClassWithProperties(ApiAppModel::class, ['resourceActions' => ['api_resource_create', 'api_resource_show']]);
        $appModel->method('current')->willReturnSelf();
        $user->setApp($appModel);

        $memberModelAdapter = $this->mockAdapter(['getPk', 'findByPk']);
        $memberModelAdapter->method('getPk')->willReturn('id');
        $memberModelAdapter->method('findByPk')->willReturn(null);

        $framework = $this->mockContaoFramework(
            [
                MemberModel::class => $memberModelAdapter,
            ]
        );

        $container = $this->mockContainer();
        $container->set('translator', new Translator('en'));

        $memberResource = new MemberResource();
        $memberResource->setFramework($framework);
        $memberResource->setContainer($container);

        $resourceManager = new ApiResourceManager($framework);
        $resourceManager->add($memberResource, 'member', 'systemcheck.api.resource.member');

        $authenticatedToken = $this->createMock(TokenInterface::class);
        $authenticatedToken->expects($this->any())->method('getUser')->willReturn($user);
        $tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)->getMock();
        $tokenStorage->method('getToken')->willReturn($authenticatedToken);
        $container->set('security.token_storage', $tokenStorage);
        $container->set('systemcheck.api.manager.resource', $resourceManager);

        $controller = new ResourceController();
        $controller->setContainer($container);

        $request = new Request();
        $request->attributes->set('_route', 'api_resource_create');
        $this->assertNotEquals('systemcheck.api.exception.resource_action_not_allowed', json_decode($controller->createAction('member', $request)->getContent())->message);
        $request->attributes->set('_route', 'api_resource_update');
        $this->assertEquals('systemcheck.api.exception.resource_action_not_allowed', json_decode($controller->updateAction('member', 1, $request)->getContent())->message);
        $request->attributes->set('_route', 'api_resource_list');
        $this->assertEquals('systemcheck.api.exception.resource_action_not_allowed', json_decode($controller->listAction('member', $request)->getContent())->message);
        $request->attributes->set('_route', 'api_resource_show');
        $this->assertNotEquals('systemcheck.api.exception.resource_action_not_allowed', json_decode($controller->showAction('member', 1, $request)->getContent())->message);
        $request->attributes->set('_route', 'api_resource_delete');
        $this->assertEquals('systemcheck.api.exception.resource_action_not_allowed', json_decode($controller->deleteAction('member', 1, $request)->getContent())->message);
    }

    /**
     * Test that actions will check if user has access to all actions.
     */
    public function testActionWithAllAccess(): void
    {
        $userModel = $this->mockClassWithProperties(UserModel::class, []);
        $userModel->method('current')->willReturnSelf();

        $user = new User($this->mockContaoFramework());
        $user->setModel($userModel);

        $appModel = $this->mockClassWithProperties(ApiAppModel::class, ['resourceActions' => ['api_resource_create', 'api_resource_update', 'api_resource_list', 'api_resource_show', 'api_resource_delete']]);
        $appModel->method('current')->willReturnSelf();
        $user->setApp($appModel);

        $memberModelAdapter = $this->mockAdapter(['getPk', 'findByPk', 'count']);
        $memberModelAdapter->method('getPk')->willReturn('id');
        $memberModelAdapter->method('findByPk')->willReturn(null);
        $memberModelAdapter->method('count')->willReturn(0);

        $framework = $this->mockContaoFramework(
            [
                MemberModel::class => $memberModelAdapter,
            ]
        );

        $container = $this->mockContainer();
        $container->set('translator', new Translator('en'));

        $memberResource = new MemberResource();
        $memberResource->setFramework($framework);
        $memberResource->setContainer($container);

        $resourceManager = new ApiResourceManager($framework);
        $resourceManager->add($memberResource, 'member', 'systemcheck.api.resource.member');

        $authenticatedToken = $this->createMock(TokenInterface::class);
        $authenticatedToken->expects($this->any())->method('getUser')->willReturn($user);
        $tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)->getMock();
        $tokenStorage->method('getToken')->willReturn($authenticatedToken);
        $container->set('security.token_storage', $tokenStorage);
        $container->set('systemcheck.api.manager.resource', $resourceManager);

        $controller = new ResourceController();
        $controller->setContainer($container);

        $request = new Request();
        $request->attributes->set('_route', 'api_resource_create');
        $this->assertNotEquals('systemcheck.api.exception.resource_not_existing', json_decode($controller->createAction('member', $request)->getContent())->message);
        $this->assertNotEquals('systemcheck.api.exception.resource_action_not_allowed', json_decode($controller->createAction('member', $request)->getContent())->message);
        $request->attributes->set('_route', 'api_resource_update');
        $this->assertNotEquals('systemcheck.api.exception.resource_not_existing', json_decode($controller->updateAction('member', 1, $request)->getContent())->message);
        $this->assertNotEquals('systemcheck.api.exception.resource_action_not_allowed', json_decode($controller->updateAction('member', 1, $request)->getContent())->message);
        $request->attributes->set('_route', 'api_resource_list');
        $this->assertNotEquals('systemcheck.api.exception.resource_not_existing', json_decode($controller->listAction('member', $request)->getContent())->message);
        $this->assertNotEquals('systemcheck.api.exception.resource_action_not_allowed', json_decode($controller->listAction('member', $request)->getContent())->message);
        $request->attributes->set('_route', 'api_resource_show');
        $this->assertNotEquals('systemcheck.api.exception.resource_not_existing', json_decode($controller->showAction('member', 1, $request)->getContent())->message);
        $this->assertNotEquals('systemcheck.api.exception.resource_action_not_allowed', json_decode($controller->showAction('member', 1, $request)->getContent())->message);
        $request->attributes->set('_route', 'api_resource_delete');
        $this->assertNotEquals('systemcheck.api.exception.resource_not_existing', json_decode($controller->deleteAction('member', 1, $request)->getContent())->message);
        $this->assertNotEquals('systemcheck.api.exception.resource_action_not_allowed', json_decode($controller->deleteAction('member', 1, $request)->getContent())->message);
    }
}
