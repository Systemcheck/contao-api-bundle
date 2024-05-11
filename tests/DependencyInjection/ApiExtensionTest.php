<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace Systemcheck\ContaoApiBundleTest\DependencyInjection;

use Systemcheck\ContaoApiBundleDependencyInjection\ApiExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class ApiExtensionTest extends TestCase
{
    public function testLoad()
    {
        $extension = new ApiExtension();
        $container = new ContainerBuilder(new ParameterBag(['kernel.debug' => false]));

        $extension->load([], $container);

        $this->assertTrue($container->hasParameter('systemcheck.api.entity.user'));
        $this->assertTrue($container->hasParameter('systemcheck.api.entity.member'));

        $this->assertTrue($container->hasDefinition('systemcheck.api.backend.api_app'));
        $this->assertTrue($container->hasDefinition('systemcheck.api.routing.matcher'));
        $this->assertTrue($container->hasDefinition('systemcheck.api.routing.login.member.matcher'));
        $this->assertTrue($container->hasDefinition('systemcheck.api.routing.login.user.matcher'));
        $this->assertTrue($container->hasDefinition('systemcheck.api.jwt_coder'));
        $this->assertTrue($container->hasDefinition('systemcheck.api.security.token_authenticator'));
        $this->assertTrue($container->hasDefinition('systemcheck.api.security.user_provider'));
        $this->assertTrue($container->hasDefinition('systemcheck.api.security.username_password_authenticator'));
        //$this->assertTrue($container->hasDefinition('systemcheck.api.manager.resource'));
        $this->assertTrue($container->hasDefinition('systemcheck.api.resource.member'));
    }
}
