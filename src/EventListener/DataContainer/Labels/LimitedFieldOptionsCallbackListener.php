<?php

/**
 * Contao bundle contao-info-slider
 *
 * @copyright balticworxx 2024 <http://www.ai-designer.de>
 * @author    Daniel Richter <daniel@ai-designer.de>
 * @license   Commercial
*/

namespace Systemcheck\ContaoApiBundle\EventListener\DataContainer\Labels;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Image;
use Contao\System;
use Symfony\Contracts\Translation\TranslatorInterface;
use Contao\Database;
use Doctrine\DBAL\Connection;
use HeimrichHannot\UtilsBundle\Util\ModelUtil;
use Systemcheck\ContaoApiBundle\Model\ApiAppActionModel;
use Systemcheck\ContaoApiBundle\Model\ApiAppModel;

#[AsCallback(table: 'tl_api_app_action', target: 'list.label.label')]
class LimitedFieldOptionsCallbackListener
{
    public function __construct(private Connection $connection) 
    {}

    public function __invoke(DataContainer $dc)
    {
        //dd($dc);

        /*function (DataContainer $dc) {
                if (null === ($app = System::getContainer()->get('huh.utils.model')->findModelInstanceByPk('tl_api_app', $dc->activeRecord->pid))) {
                    return [];
                }

                if (!$app->resource) {
                    return [];
                }

                return System::getContainer()->get('systemcheck.api.util.api_util')->getResourceFieldOptions($app->resource);
            },*/
            
        //$app = ApiAppModel::findbyPk($dc->__get('id'));
        $appAction = ApiAppActionModel::findByIdOrAlias($dc->__get('id'));
        $app = ApiAppModel::findByIdOrAlias($appAction->pid);
        
        $this->connection->fetchAllAssociative("SELECT * FROM tl_api_app_action WHERE pid = " . $dc->__get('id'));
    
        if (!$app->resource) {
            return [];
        }
        
        $arrFormdataTables[] = $app->title;

        return $arrFormdataTables;
        $results = $this->connection->fetchAllAssociative("SELECT * FROM tl_api_app_action WHERE pid = " . $dc->__get('id'));  
        //return $results;
        
        
        $arrFormdataTables = [];
        $i = 0;

        foreach($sliders as $slider)
        {
            $key = $slider['id'];
            $arrFormdataTables[$key] = $slider["lastname"] . ', ' . $slider["firstname"] ;
            //$arrFormdataTables[$key][$slider["title"]] = $slider["alias"];
            $i++;
        }
			
        return $arrFormdataTables;
        
    }
}