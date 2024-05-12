<?php

/*
 * Copyright (c) 2022 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace Systemcheck\ContaoApiBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Config\ConfigInterface;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Config\ContainerBuilder;
use Contao\ManagerPlugin\Config\ExtensionPluginInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Systemcheck\ContaoApiBundle\SystemcheckContaoApiBundle;
use HeimrichHannot\UtilsBundle\Util\ContainerUtil;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Yaml;

class Plugin implements BundlePluginInterface, RoutingPluginInterface, ExtensionPluginInterface
{
    /**
     * Gets a list of autoload configurations for this bundle.
     *
     * @return ConfigInterface[]
     */
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create(SystemcheckContaoApiBundle::class)->setLoadAfter(
                [
                    ContaoCoreBundle::class,
                ]
            ),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel)
    {
        return $resolver->resolve(__DIR__ . '/../Resources/config/routes.yml')->load(__DIR__ . '/../Resources/config/routes.yml');
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensionConfig($extensionName, array $extensionConfigs, ContainerBuilder $container)
    {
        
        /*if ('security' !== $extensionName) {
            return $extensionConfigs;
        }*/

        if ('security' === $extensionName) {
            $extensionConfigs = $this->getSecurityExtensionConfig($extensionConfigs, $container);

            return $extensionConfigs;
        }

        

        return $this->mergeConfigFile(
            'systemcheck_contao_api',
            $extensionName,
            $extensionConfigs,
            __DIR__.'/../Resources/config/config.yml'
        );
    }

    /**
     * Get security extension config.
     *
     * @return array
     */
    public function getSecurityExtensionConfig(array $extensionConfigs, ContainerBuilder $container)
    {
        foreach ($extensionConfigs as &$extensionConfig) {
            if (isset($extensionConfig['firewalls'])) {
                
                $extensionConfig['providers']['systemcheck.json_user_provider'] = [
                    'id' => 'systemcheck.security.json_user_provider'
                ];

                $extensionConfig['providers']['systemcheck.api.security.user_provider'] = [
                    'id' => 'systemcheck.api.security.user_provider'
                ];

                $offset = (int) array_search('frontend', array_keys($extensionConfig['firewalls']));
                
                $extensionConfig['firewalls'] = array_merge(
                    array_slice($extensionConfig['firewalls'], 0, $offset, true),
                    [
                        /*'api_login_member' => [
                            'request_matcher' => 'systemcheck.api.routing.login.member.matcher',
                            'stateless' => true,
                            'provider' => 'contao.security.frontend_user_provider',
                        ],
                        'api_login_user' => [
                            'request_matcher' => 'systemcheck.api.routing.login.user.matcher',
                            'stateless' => true,
                            "json_login" => [
                                "provider" => "contao.security.backend_user_provider",
                                "check_path" => "/api/login/user",
                                "success_handler" => "balticworxx.security.authentication_success_handler",
                                "failure_handler" => "contao.security.authentication_failure_handler"
                            ],
                            'provider' => 'contao.security.backend_user_provider',
                        ],*/
                        "json_login" => [
                            'pattern' =>  "^/api/login",
                            "stateless" => true,
                            //"provider" => "balticworxx.json_user_provider",
                            "json_login" => [
                                "provider" => "systemcheck.json_user_provider",
                                "check_path" => "/api/login_check",
                                "success_handler" => "balticworxx.security.authentication_success_handler",
                                "failure_handler" => "balticworxx.security.authentication_failure_handler"
                            ]
                        ],
                        'api' => [
                            'pattern' => '/api/*',
                            'stateless' => true,
                            'custom_authenticators' => [
                                'systemcheck.security.api_authenticator',
                                'systemcheck.api.security.username_password_authenticator'
                            ],
                            //"provider" => "systemcheck.json_user_provider",
                            'provider' => 'systemcheck.api.security.user_provider',
            
                        ]    
                    ],
                    array_slice($extensionConfig['firewalls'], $offset, null, true)
                );
                
                break;
            }
        }

        /*
        $firewalls = [
            'api_login_member' => [
                'request_matcher' => 'systemcheck.api.routing.login.member.matcher',
                'stateless' => true,
                'guard' => [
                    'authenticators' => ['systemcheck.api.security.username_password_authenticator'],
                ],
                'provider' => 'contao.security.frontend_user_provider',
            ],
            'api_login_user' => [
                'request_matcher' => 'systemcheck.api.routing.login.user.matcher',
                'stateless' => true,
                'guard' => [
                    'authenticators' => ['systemcheck.api.security.username_password_authenticator'],
                ],
                'provider' => 'contao.security.backend_user_provider',
            ],
            'api' => [
                'request_matcher' => 'systemcheck.api.routing.matcher',
                'stateless' => true,
                'guard' => [
                    'authenticators' => ['systemcheck.api.security.token_authenticator'],
                ],
                'provider' => 'systemcheck.api.security.user_provider',
            ],
        ];

        $providers = [
            'systemcheck.api.security.user_provider' => [
                'id' => 'systemcheck.api.security.user_provider',
            ],
        ];

        foreach ($extensionConfigs as &$extensionConfig) {
            $extensionConfig['firewalls'] = (isset($extensionConfig['firewalls']) && \is_array($extensionConfig['firewalls']) ? $extensionConfig['firewalls'] : []) + $firewalls;
            $extensionConfig['providers'] = (isset($extensionConfig['providers']) && \is_array($extensionConfig['providers']) ? $extensionConfig['providers'] : []) + $providers;
        }*/

        return $extensionConfigs;
    }

    public function mergeConfigFile(
        string $activeExtensionName,
        string $extensionName,
        array $extensionConfigs,
        string $configFile
    ) {
        
        /*if($extensionName != 'doctrine' && $extensionName != 'framework' && $extensionName != 'monolog' && $extensionName != 'fos_http_cache' && $extensionName != 'nelmio_cors'
            && $extensionName != 'nelmio_security' && $extensionName != 'scheb_two_factor' && $extensionName != 'twig_extra' && $extensionName != 'web_profiler'
            && $extensionName != 'knp_menu' && $extensionName != 'knp_time' && $extensionName != 'debug' && $extensionName != 'cmf_routing' && $extensionName != 'twig'
            && $extensionName != 'contao' && $extensionName != 'flysystem' && $extensionName != 'contao_manager'
        ) {
            dd($extensionName);
        }*/
        if ($activeExtensionName === $extensionName && file_exists($configFile)) {
            
            $config = Yaml::parseFile($configFile);
            //\is_array($config)); => true
            //\is_array($extensionConfigs) => true
            $extensionConfigs = array_merge_recursive(\is_array($extensionConfigs) ? $extensionConfigs : [], \is_array($config) ? $config : []);
            return $extensionConfigs;
        }
        //dd($extensionConfigs);
        return $extensionConfigs;
    }
}
