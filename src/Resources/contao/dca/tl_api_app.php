<?php

/*
 * Copyright (c) 2022 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */
use Contao\System;
use Contao\DC_Table;

use Systemcheck\ContaoApiBundle\Manager\ApiResourceManager;

$GLOBALS['TL_DCA']['tl_api_app'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ctable' => ['tl_api_app_action'],
        'enableVersioning' => true,
        'onload_callback' => [
            ['systemcheck.api.backend.api_app', 'checkPermission'],
        ],
        /*'onsubmit_callback' => [
            ['huh.utils.dca', 'setDateAdded'],
        ],
        'oncopy_callback' => [
            ['huh.utils.dca', 'setDateAddedOnCopy'],
        ],*/
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'start,stop,published' => 'index',
                'key' => 'unique',
            ],
        ],
    ],
    'list' => [
        'label' => [
            'fields' => ['title'],
            'format' => '%s',
        ],
        'sorting' => [
            'mode' => 2,
            'fields' => ['title DESC'],
            'panelLayout' => 'filter;sort,search,limit',
        ],
        'global_operations' => [
            'all' => [
                'label' => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset();"',
            ],
        ],
        'operations' => [
            'edit' => [
                'label' => &$GLOBALS['TL_LANG']['tl_api_app']['edit'],
                'href' => 'table=tl_api_app_action',
                'icon' => 'edit.svg',
                'button_callback' => ['systemcheck.api.backend.api_app', 'editButton'],
            ],
            'editheader' => [
                'label' => &$GLOBALS['TL_LANG']['tl_api_app']['editheader'],
                'href' => 'act=edit',
                'icon' => 'header.svg',
            ],
            'copy' => [
                'label' => &$GLOBALS['TL_LANG']['tl_api_app']['copy'],
                'href' => 'act=copy',
                'icon' => 'copy.gif',
            ],
            'delete' => [
                'label' => &$GLOBALS['TL_LANG']['tl_api_app']['delete'],
                'href' => 'act=delete',
                'icon' => 'delete.gif',
                'attributes' => 'onclick="if(!confirm(\''. ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? 'delete') .'\'))return false;Backend.getScrollOffset()"',
            ],
            'toggle' => [
                'label' => &$GLOBALS['TL_LANG']['tl_api_app']['toggle'],
                'icon' => 'visible.gif',
                'attributes' => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"',
                'button_callback' => ['systemcheck.api.backend.api_app', 'toggleIcon'],
            ],
            'show' => [
                'label' => &$GLOBALS['TL_LANG']['tl_api_app']['show'],
                'href' => 'act=show',
                'icon' => 'show.gif',
            ],
        ],
    ],
    'palettes' => [
        '__selector__' => ['type', 'limitFields', 'limitFormattedFields', 'published'],
        'default' => '{general_legend},title,type',
        'resource' => '{general_legend},title,type,author;{resource_legend},resource,resourceActions;{security_legend},key,groups,mGroups;{publish_legend},published',
        'entity_resource' => '{general_legend},title,type,author;{resource_legend},resource;{security_legend},key,groups,mGroups;{publish_legend},published',
    ],
    'subpalettes' => [
        'published' => 'start,stop',
    ],
    'fields' => [
        'id' => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'tstamp' => [
            'label' => &$GLOBALS['TL_LANG']['tl_api_app']['tstamp'],
            'eval' => ['rgxp' => 'datim'],
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'dateAdded' => [
            'sorting' => true,
            'flag' => 6,
            'eval' => ['rgxp' => 'datim', 'doNotCopy' => true],
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'type' => [
            'label' => &$GLOBALS['TL_LANG']['tl_api_app']['type'],
            'flag' => 1,
            'exclude' => true,
            'filter' => true,
            'inputType' => 'select',
            'options' => System::getContainer()->get('systemcheck.api.manager.resource')::RESOURCE_TYPES,
            'reference' => &$GLOBALS['TL_LANG']['tl_api_app']['reference'],
            'eval' => ['maxlength' => 32, 'tl_class' => 'w50 chosen', 'submitOnChange' => true, 'mandatory' => true, 'includeBlankOption' => true],
            'sql' => "varchar(32) NOT NULL default ''",
        ],
        'title' => [
            'label' => &$GLOBALS['TL_LANG']['tl_api_app']['title'],
            'flag' => 1,
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'resource' => [
            'label' => &$GLOBALS['TL_LANG']['tl_api_app']['resource'],
            'flag' => 1,
            'exclude' => true,
            'filter' => true,
            'inputType' => 'select',
            'options_callback' => ['systemcheck.api.manager.resource', 'choices'], //[ApiResourceManager::class, 'choices'],
            'reference' => &$GLOBALS['TL_LANG']['tl_api_app']['reference'],
            'eval' => ['maxlength' => 32, 'tl_class' => 'w50 chosen', 'submitOnChange' => true, 'mandatory' => true, 'includeBlankOption' => true],
            'sql' => "varchar(32) NOT NULL default ''",
        ],
        'resourceActions' => [
            'label' => &$GLOBALS['TL_LANG']['tl_api_app']['resourceActions'],
            'flag' => 1,
            'exclude' => true,
            'filter' => true,
            'inputType' => 'checkbox',
            'options' => ['api_resource_create', 'api_resource_update', 'api_resource_list', 'api_resource_show', 'api_resource_delete'],
            'reference' => &$GLOBALS['TL_LANG']['tl_api_app']['reference'],
            'sql' => 'blob NULL',
            'eval' => ['multiple' => true, 'tl_class' => 'w50 autoheight'],
        ],
        'mGroups' => [
            'label' => &$GLOBALS['TL_LANG']['tl_api_app']['mGroups'],
            'exclude' => true,
            'inputType' => 'checkbox',
            'foreignKey' => 'tl_member_group.name',
            'eval' => ['multiple' => true, 'tl_class' => 'w50 autoheight'],
            'sql' => 'blob NULL',
            'relation' => ['type' => 'hasMany', 'load' => 'lazy'],
        ],
        'groups' => [
            'label' => &$GLOBALS['TL_LANG']['tl_api_app']['groups'],
            'exclude' => true,
            'inputType' => 'checkbox',
            'foreignKey' => 'tl_user_group.name',
            'eval' => ['multiple' => true, 'tl_class' => 'w50 autoheight'],
            'sql' => 'blob NULL',
            'relation' => ['type' => 'hasMany', 'load' => 'lazy'],
        ],
        'key' => [
            'label' => &$GLOBALS['TL_LANG']['tl_api_app']['key'],
            'search' => true,
            'inputType' => 'text',
            'load_callback' => [['systemcheck.api.backend.api_app', 'generateApiToken']],
            'eval' => ['tl_class' => 'clr long', 'unique' => true],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'published' => [
            'label' => &$GLOBALS['TL_LANG']['tl_api_app']['published'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'checkbox',
            'eval' => ['doNotCopy' => true, 'submitOnChange' => true, 'tl_class' => 'w50'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'start' => [
            'label' => &$GLOBALS['TL_LANG']['tl_api_app']['start'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(10) NOT NULL default ''",
        ],
        'stop' => [
            'label' => &$GLOBALS['TL_LANG']['tl_api_app']['stop'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(10) NOT NULL default ''",
        ],
    ],
];
