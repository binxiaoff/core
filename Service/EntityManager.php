<?php
/**
 * Created by PhpStorm.
 * User: binxiao
 * Date: 22/04/2016
 * Time: 12:54
 */

namespace Unilend\Service;

use Unilend\Bundle\Doctrine\DBAL\DBAL;
use Unilend\core\Loader;

class EntityManager
{
    private $oDbConnection;

    public function __construct(DBAL $oDbConnection)
    {
        $this->oDbConnection = $oDbConnection;
    }
    
    public function getRepository($table, $params = array())
    {
        return Loader::loadData($table, $params, $this->oDbConnection);
    }
}