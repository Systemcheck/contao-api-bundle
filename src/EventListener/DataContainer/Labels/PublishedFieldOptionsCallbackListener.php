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
use Symfony\Contracts\Translation\TranslatorInterface;
use Contao\Database;
use Doctrine\DBAL\Connection;

#[AsCallback(table: 'tl_api_app_action', target: 'list.label.label')]
class PublishedFieldOptionsCallbackListener
{
    public function __construct(Connection $connection) {
        $this->connection = $connection;
    }

    public function __invoke(DataContainer $dc)
    {
        
        //->findModelInstanceByPk('tl_api_app', $dc->activeRecord->pid
        
        $results = $this->connection->fetchAllAssociative("SELECT * FROM tl_api_app_action WHERE pid = " . $dc->__get('id'));
    
        $arrFormdataTables = [];
        $i = 0;

        foreach($results as $result)
        {
            $key = $result['id'];
            $arrFormdataTables[$key] = $result["type"] . ' [ ' . $result['id'] . ' ]';
            //$arrFormdataTables[$key][$slider["title"]] = $slider["alias"];
            $i++;
        }
			
        return $arrFormdataTables;
        
    }
}