<?php

/*
 * Copyright (c) 2022 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace Systemcheck\ContaoApiBundle\Security\User;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\System;
use Contao\FrontendUser;
use Contao\User;
use Systemcheck\ContaoApiBundle\Entity\User as UserEntity;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Contao\CoreBundle\Framework\ContaoFramework;

class UserProvider implements ContainerAwareInterface, UserProviderInterface
{
    use ContainerAwareTrait;

    /**
     * @var string
     */
    protected $modelClass;

    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * Constructor.
     */
    public function __construct(ContaoFramework $framework, private $userClass, TranslatorInterface $translator)
    {
        $this->framework = $framework;
        $this->translator = $translator;
    }

    public function loadUserByIdentifier(string $identifier): User
    {
        $this->framework->initialize();
        /** @var Adapter<User> $adapter */
        $adapter = $this->framework->getAdapter($this->userClass);
        $user = $adapter->loadUserByIdentifier($identifier);
        
        if (is_a($user, $this->userClass)) {
            return $user;
        }

        throw new UsernameNotFoundException(sprintf('Could not find user "%s"', $identifier));
    }
    
    /*public function loadUserByEntityAndUsername(UserInterface $user, $username)
    {
        dd('loadUserByEntityAndUsername');
        $this->framework->initialize();

        if (!$username) {
            throw new UsernameNotFoundException($this->translator->trans('systemcheck.api.exception.auth.invalid_username'));
        }

        if (null === ($userFound = $user->findBy('username', $username))) {
            $loaded = false;

            // HOOK: pass credentials to callback functions
            if (isset($GLOBALS['TL_HOOKS']['importUser']) && \is_array($GLOBALS['TL_HOOKS']['importUser'])) {
                /** @var System $system */
                /*$system = $this->framework->getAdapter(System::class);

                foreach ($GLOBALS['TL_HOOKS']['importUser'] as $callback) {
                    $loaded = $system->importStatic($callback[0], 'import', true)->{$callback[1]}($username, $this->container->get('request_stack')->getCurrentRequest()->getPassword() ?: $this->container->get('request_stack')->getCurrentRequest()->request->get('password'), $user->getModelTable());

                    // Load successfull
                    if (true === $loaded) {
                        break;
                    }
                }
            }

            // Return if the user still cannot be loaded
            if (true === $loaded && null === ($userFound = $user->findBy('username', $username))) {
                throw new UsernameNotFoundException($this->translator->trans('systemcheck.api.exception.auth.user_not_found', ['%username%' => $username]));
            }

            throw new UsernameNotFoundException($this->translator->trans('systemcheck.api.exception.auth.user_not_existing', ['%username%' => $username]));
        }

        return $userFound;
    }

    /**
     * @var array
     *            {@inheritdoc}
     */
    /*public function loadUserByUsername($attributes)
    {
        dd('loadUserByUsername');
        $this->framework->initialize();

        if (!isset($attributes['entity']) || empty($attributes['entity'])) {
            throw new AuthenticationException($this->translator->trans('systemcheck.api.exception.auth.missing_entity', ['%entity%' => $attributes['entity']]));
        }

        $class = $this->container->getParameter($attributes['entity']);

        if (!class_exists($class)) {
            throw new AuthenticationException($this->translator->trans('systemcheck.api.exception.auth.missing_entity_class', ['%entity%' => $attributes['entity']]));
        }

        /** @var UserInterface $user */
        /*$user = $this->framework->createInstance($class, [$this->framework]);

        return $this->loadUserByEntityAndUsername($user, $attributes['username']);
    }*/

    /**
     * {@inheritdoc}
     */
    public function refreshUser(\Symfony\Component\Security\Core\User\UserInterface $user)
    {
        throw new UnsupportedUserException($this->translator->trans('systemcheck.api.exception.auth.refresh_not_possible'));
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class)
    {
        dd('supportsClass');
        return is_subclass_of($class, User::class);
    }
}
