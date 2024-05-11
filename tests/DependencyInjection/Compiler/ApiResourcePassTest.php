<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace Systemcheck\ContaoApiBundleTest\DependencyInjection\Compiler;

use Contao\TestCase\ContaoTestCase;
use Systemcheck\ContaoApiBundle\Api\Resource\MemberResource;
use Systemcheck\ContaoApiBundleDependencyInjection\Compiler\ApiResourcePass;
use Systemcheck\ContaoApiBundle\Manager\ApiResourceManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

class ApiResourcePassTest extends ContaoTestCase
{
    /**
     * Test without existing resource manager service.
     */
    public function testProcessWithMissingManagerService()
    {
        $this->expectException(ServiceNotFoundException::class);

        $container = new ContainerBuilder();

        $pass = new ApiResourcePass();
        $pass->process($container);
    }

    /**
     * Test without tagged resources.
     */
    public function testProcessWithoutTaggedResources()
    {
        $container = new ContainerBuilder();

        $definition = new Definition(ApiResourceManager::class, [$this->mockContaoFramework()]);
        $container->setDefinition('systemcheck.api.manager.resource', $definition);

        $pass = new ApiResourcePass();
        $pass->process($container);

        /** @var ApiResourceManager $manager */
        $manager = $container->get('systemcheck.api.manager.resource');
        $this->assertEmpty($manager->all());
    }

    /**
     * Test without tagged resources.
     */
    public function testProcessWithoutResourceAlias()
    {
        $this->expectException(InvalidArgumentException::class);

        $container = new ContainerBuilder();

        $definition = new Definition(ApiResourceManager::class, [$this->mockContaoFramework()]);

        $container->setDefinition('systemcheck.api.manager.resource', $definition);
        $container->register('systemcheck.api.resource.member', MemberResource::class)->addTag('systemcheck.api.resource', []);

        $pass = new ApiResourcePass();
        $pass->process($container);

        /** @var ApiResourceManager $manager */
        $manager = $container->get('systemcheck.api.manager.resource');
        $this->assertEmpty($manager->all());
    }

    /**
     * Test without tagged resources.
     */
    public function testProcessWithResourceAlias()
    {
        $container = new ContainerBuilder();

        $definition = new Definition(ApiResourceManager::class, [$this->mockContaoFramework()]);

        $container->setDefinition('systemcheck.api.manager.resource', $definition);
        $container->register('systemcheck.api.resource.member', MemberResource::class)->addTag('systemcheck.api.resource', ['alias' => 'member']);

        $pass = new ApiResourcePass();
        $pass->process($container);

        /** @var ApiResourceManager $manager */
        $manager = $container->get('systemcheck.api.manager.resource');
        $this->assertCount(1, $manager->all());
    }
}
