<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace Systemcheck\ContaoApiBundle\Util;

use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Systemcheck\ContaoApiBundle\Model\ApiAppModel;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class ApiUtil implements FrameworkAwareInterface, ContainerAwareInterface
{
    use FrameworkAwareTrait;
    use ContainerAwareTrait;

    public function getResourceConfigByName(string $resourceName)
    {

        $r = \Contao\System::getContainer()->getParameter('systemcheck');
        
        $resources = $r;

    
        if (!isset($resources['api']['resources'])) {
            return false;
        }
        
        foreach ($resources['api']['resources'] as $resource) {
            if ($resource['name'] === $resourceName) {
                return $resource;
            }
        }

        return false;
    }

    public function getResourceConfigByModelClass(string $modelClass)
    {
        $resources = $this->container->getParameter('systemcheck.api');

        if (!isset($resources['api']['resources'])) {
            return false;
        }

        foreach ($resources['api']['resources'] as $resource) {
            if ($resource['modelClass'] === $modelClass) {
                return $resource;
            }
        }

        return false;
    }

    public function getResourceFieldOptions(string $resourceName)
    {
        $resourceConfig = $this->container->get('systemcheck.api.util.api_util')->getResourceConfigByName($resourceName);

        if (!\is_array($resourceConfig) || !class_exists($resourceConfig['modelClass'])) {
            return [];
        }

        return $this->container->get('huh.utils.choice.field')->getCachedChoices([
            'dataContainer' => $resourceConfig['modelClass']::getTable(),
        ]);
    }

    public function getEntityTableByApp(ApiAppModel $app)
    {
        $config = $this->getResourceConfigByName($app->resource);

        if (!isset($config['modelClass']) || !class_exists($config['modelClass'])) {
            return false;
        }

        return $config['modelClass']::getTable();
    }
}
