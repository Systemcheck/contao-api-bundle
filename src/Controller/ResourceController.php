<?php

/*
 * Copyright (c) 2022 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace Systemcheck\ContaoApiBundle\Controller;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\StringUtil;
use Systemcheck\ContaoApiBundle\ApiResource\ResourceInterface;
use Systemcheck\ContaoApiBundle\Manager\ApiResourceManager;
use Systemcheck\ContaoApiBundle\Model\ApiAppActionModel;
use Systemcheck\ContaoApiBundle\Model\ApiAppModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Connection;
//use Symfony\Component\Security\Core\Security;
use Symfony\Bundle\SecurityBundle\Security;
use Contao\CoreBundle\Translation\Translator;
use Contao\CoreBundle\Controller\AbstractController as ContaoAbstractController;
use Systemcheck\ContaoApiBundle\ApiResource\EntityResource;

#[Route('/api', name: ApiController::class, defaults: ['_scope' => 'api', '_token_check' => false])]
class ResourceController extends ContaoAbstractController// extends AbstractController
{
    public function __construct(private Translator $translator, private Connection $connection, Security $security, private ApiResourceManager $resourceManager)
    {
        //$this->twig = $twig;
        $this->security = $security;
    }

    #[Route('/{alias}', name: "api_resource_create"::class, methods: ["POST"])]
    public function createAction(string $alias, Request $request)
    {
        /** @var ResourceInterface $resource */
        if (null === ($resource = $this->resourceManager->get($alias))) {
            return $this->json(['message' => $this->translator->trans('systemcheck.api.exception.resource_not_existing', ['%alias%' => $alias])]);
        }


        if (false === $this->isActionAllowed($request)) {
            return $this->json(['message' => $this->translator->trans('systemcheck.api.exception.resource_action_not_allowed', ['%resource%' => $alias, '%action%' => $request->attributes->get('_route')])]);
        }
    
        return $this->json($resource->create($request, $this->getUser()));
    }

    #[Route('/{alias}/{id}', name: "api_resource_update"::class, methods: ["PUT"])]
    public function updateAction(string $alias, $id, Request $request)
    {
        /** @var ResourceInterface $resource */
        if (null === ($resource = $this->container->get('systemcheck.api.manager.resource')->get($alias))) {
            return $this->json(['message' => $this->translator->trans('systemcheck.api.exception.resource_not_existing', ['%alias%' => $alias])]);
        }

        if (false === $this->isActionAllowed($request)) {
            return $this->json(['message' => $this->translator->trans('systemcheck.api.exception.resource_action_not_allowed', ['%resource%' => $alias, '%action%' => $request->attributes->get('_route')])]);
        }

        return $this->json($resource->update($id, $request, $this->getUser()));
    }

    #[Route('/{alias}', name: "api_resource_list"::class, methods: ["GET"])]
    public function listAction(Request $request, string $alias )
    {
            
        $framework = $this->container->get('contao.framework'); //->initialize();
        $framework->initialize();
        $s = $this->getParameter('systemcheck');
        
        //$this->setApp($request->attributes->get('_route_params'));
        
        /** @var ResourceInterface $resource */
        if (null === ($resource = $this->resourceManager->get($alias))) {
            return $this->json(['message' => $this->translator->trans('systemcheck.api.exception.resource_not_existing', ['%alias%' => $alias])]);
        }

        if (false === $this->isActionAllowed($request)) {
            return $this->json(['message' => $this->translator->trans('systemcheck.api.exception.resource_action_not_allowed', ['%resource%' => $alias, '%action%' => $request->attributes->get('_route')])]);
        }
        //dd($resource->list($request, $this->getUser()));
        return $this->json($resource->list($request, $this->getUser()));
    }

    #[Route('/{alias}/{id}', name: "api_resource_show"::class, methods: ["GET"])]
    public function showAction(string $alias, $id, Request $request)
    {
        /** @var ResourceInterface $resource */
            if (null === ($resource = $this->resourceManager->get($alias))) {
            return $this->json(['message' => $this->translator->trans('systemcheck.api.exception.resource_not_existing', ['%alias%' => $alias])]);
        }

        if (false === $this->isActionAllowed($request)) {
            return $this->json(['message' => $this->translator->trans('systemcheck.api.exception.resource_action_not_allowed', ['%resource%' => $alias, '%action%' => $request->attributes->get('_route')])]);
        }

        return $this->json($resource->show($id, $request, $this->getUser()));
    }

    #[Route('/{alias}/{id}', name: "api_resource_delete"::class, methods: ["DELETE"])]
    public function deleteAction(string $alias, $id, Request $request)
    {
        /** @var ResourceInterface $resource */
        if (null === ($resource = $this->container->get('systemcheck.api.manager.resource')->get($alias))) {
            return $this->json(['message' => $this->translator->trans('systemcheck.api.exception.resource_not_existing', ['%alias%' => $alias])]);
        }

        if (false === $this->isActionAllowed($request)) {
            return $this->json(['message' => $this->translator->trans('systemcheck.api.exception.resource_action_not_allowed', ['%resource%' => $alias, '%action%' => $request->attributes->get('_route')])]);
        }

        return $this->json($resource->delete($id, $request, $this->getUser()));
    }

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        //$services['systemcheck.api.manager.resource'] = ApiResourceManager::class;
        $services['contao.framework'] = ContaoFramework::class;

        return $services;
    }

    /**
     * Determine if action is allowed.
     */
    protected function isActionAllowed(Request $request): bool
    {
        return true;
        if (null === ($app = $this->getUser()->getApp())) {
            return false;
        }

        
        $app = 'SW';
        
        /*if (null === ($app = $this->getUser() ? $this->getUser() : null)) {
            return false;
        }*/
        $type = 'resource'; //$app->type
        //$resourceManager = $this->container->get('systemcheck.api.manager.resource');
        $resourceManager = $this->resourceManager;
        switch ($type) {
            case $resourceManager::TYPE_ENTITY_RESOURCE:
                if (null === ($action = ApiAppActionModel::findOneBy(['tl_api_app_action.pid=?', 'tl_api_app_action.type=?'], [2 /*$app->id*/, $request->attributes->get('_route')]))) {
                    return false;
                }

                break;

            default:
                $allowed = StringUtil::deserialize($app->resourceActions, true);

                if (!\in_array($request->attributes->get('_route'), $allowed)) {
                    return false;
                }

                break;
        }

        return true;
    }

    private function setApp($resource) 
    {
        
        $app = ApiAppModel::findByResource('%'.$resource['alias'].'%');
        
    }
}
