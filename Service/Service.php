<?php
namespace Unilend\Service;

use Unilend\core\DataBase;
use Unilend\core\Loader;

class Service
{
    /** @var  DataBase */
    private $oDBConn;

    public function setDBConn(DataBase $oDBConn)
    {
        $this->oDBConn = $oDBConn;
    }

    public function loadData($object, $params = array())
    {
        return Loader::loadData($object, $params, $this->oDBConn);
    }
}