<?php

/*
 * Copyright (c) 2018 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace Systemcheck\ContaoApiBundle\ApiResource;

use Systemcheck\ContaoApiBundle\Api\Security\User\UserInterface;
use Symfony\Component\HttpFoundation\Request;

interface ResourceInterface
{
    /**
     * Create an item for this resource.
     *
     * @param Request       $request The request
     * @param UserInterface $user    Current user
     *
     * @return array
     */
    public function create(Request $request, UserInterface $user): ?array;

    /**
     * Update an item for this resource.
     *
     * @param mixed         $id      Unique entity id
     * @param Request       $request The request
     * @param UserInterface $user    Current user
     *
     * @return array
     */
    public function update($id, Request $request, UserInterface $user): ?array;

    /**
     * List items of this resource.
     *
     * @param Request       $request The request
     * @param UserInterface $user    Current user
     *
     * @return array|null
     */
    public function list(Request $request, UserInterface $user): ?array;

    /**
     * Show item of this resource.
     *
     * @param mixed         $id      Unique entity id
     * @param Request       $request The request
     * @param UserInterface $user    Current user
     *
     * @return array|null
     */
    public function show($id, Request $request, UserInterface $user): ?array;

    /**
     * Delete item of this resource.
     *
     * @param mixed         $id      Unique entity id
     * @param Request       $request The request
     * @param UserInterface $user    Current user
     *
     * @return array|null
     */
    public function delete($id, Request $request, UserInterface $user): ?array;
}
