<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace Systemcheck\ContaoApiBundle\ApiResource;

use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Systemcheck\ContaoApiBundle\Api\Security\User\UserInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Request;

class DefaultResource implements ResourceInterface
{
    use FrameworkAwareTrait;
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function create(Request $request, $user): ?array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function update($id, Request $request, UserInterface $user): ?array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function list(Request $request, $user): ?array
    {
        
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function show($id, Request $request, UserInterface $user): ?array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id, Request $request, UserInterface $user): ?array
    {
        return [];
    }
}
