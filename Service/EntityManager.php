<?php
namespace Unilend\Service;

use Unilend\core\Loader;
use Doctrine\DBAL\Driver\Connection;

class EntityManager
{
    private $oDbConnection;

    public function __construct(Connection $oDbConnection)
    {
        $this->oDbConnection = $oDbConnection;
    }

    public function getRepository($table, $params = array())
    {
        return Loader::loadData($table, $params, $this->oDbConnection);
    }
}
