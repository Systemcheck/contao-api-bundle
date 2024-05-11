<?php

/**
 * Back end modules
 */
$GLOBALS['BE_MOD']['api'] = [
            'api_apps' => [
                'tables' => ['tl_api_app', 'tl_api_app_action'],
            ],
        ];

/**
 * Models
 */
$GLOBALS['TL_MODELS']['tl_api_app']        = 'Systemcheck\ContaoApiBundle\Model\ApiAppModel';
$GLOBALS['TL_MODELS']['tl_api_app_action'] = 'Systemcheck\ContaoApiBundle\Model\ApiAppActionModel';
