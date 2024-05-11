<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace Systemcheck\ContaoApiBundle;

use Systemcheck\ContaoApiBundle\DependencyInjection\SystemcheckContaoApiExtension;
use Systemcheck\ContaoApiBundle\DependencyInjection\Compiler\ApiResourcePass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class SystemcheckContaoApiBundle extends Bundle
{
    /**
     * {@inheritdoc}
    */
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new SystemcheckContaoApiExtension();
    } 


    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ApiResourcePass());
    }
} 
