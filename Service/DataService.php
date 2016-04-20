<?php
namespace Unilend\Service;

use Unilend\Bundle\Doctrine\DBAL\DBAL;
use Unilend\core\Loader;

class DataService
{
    /** @var DBAL */
    private $oDBConn;

    public function setDBConn(DBAL $oDBConn)
    {
        $this->oDBConn = $oDBConn;
    }

    public function loadData($object, $params = array())
    {
        return Loader::loadData($object, $params, $this->oDBConn);
    }
}