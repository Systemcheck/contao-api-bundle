<?php

/*
 * Copyright (c) 2022 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace Systemcheck\ContaoApiBundle\DependencyInjection;

use HeimrichHannot\CategoriesBundle\CategoriesBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class SystemcheckContaoApiExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    /*public function getAlias(): string
    {
        return 'systemcheck_contao_api';
        
    }*/

    /**
	 * load method
	 * 
	 * @param array $mergedConfig
	 * @param ContainerBuilder $container
	 * @return void
	 */
	public function load(array $mergedConfig, ContainerBuilder $container): void
	{
        $configuration = new Configuration($container->getParameter('kernel.debug'));
        $processedConfig = $this->processConfiguration($configuration, $mergedConfig);

		
        $container->setParameter('systemcheck', $processedConfig);
		//dd($container);
        /*if (!class_exists(CategoriesBundle::class)) {
            $container->removeDefinition('Systemcheck\ContaoApiBundle\EventListener\CategoriesListener');
        }*/
		
;		$loader = new YamlFileLoader(
			$container,
			new FileLocator(__DIR__ . '/../Resources/config')
		);
		
		//$loader->load("config.yml");
		$loader->load('services.yml');
		$loader->load('listener.yml');
		$loader->load('parameters.yml');
		//dd($container);
  	}

}
