<?php

namespace Unilend\librairies;

use Memcache;
use Unilend\librairies\ULogger;

class Cache
{
    CONST SHORT_TIME  = 300;
    CONST MEDIUM_TIME = 1800;
    CONST LONG_TIME   = 3600;

    /**
     * constant for list and count projects
     */
    CONST LIST_PROJECTS = 'List_Counter_Projects';

    /**
     * @var $oInstance Instance of this object
     */
    private static $oInstance;

    /**
     * @var Memcache
     */
    private $oMemcache;

    /**
     * @var ULogger
     */
    private $oLogger;

    /**
     * @param array $aConfig
     * @return Cache|Instance
     */
    public static function getInstance($aConfig = null)
    {
        if (true === is_null(self::$oInstance) && false === is_null($aConfig)) {
            self::$oInstance = new self($aConfig);
        }

        return self::$oInstance;
    }

    /**
     * @param array $aConfig
     */
    private function __construct($aConfig)
    {
        $this->oMemcache = new Memcache();
        $this->oMemcache->connect($aConfig['cache'][$aConfig['env']]['serverAddress'], $aConfig['cache'][$aConfig['env']]['serverPort']);

        $this->oLogger = new ULogger('Cache', __DIR__ . $aConfig['log_path'], 'cache.log');
    }

    public function makeKey()
    {
        $aKey = array();
        foreach (func_get_args() as $mParameters) {
            if (is_scalar($mParameters)) {
                $aKey[] = $mParameters;
            } else {
                $this->oLogger->addRecord(ULogger::ERROR, 'Parameter : ' . $mParameters . ' not a scalar variable.');
            }
        }
        $sKey = implode('_', $aKey);

        return (250 < strlen($sKey)) ? md5($sKey) : $sKey;
    }

    /**
     * @param string $sKey
     * @param mixed $mValue
     * @param int $iTime
     * @return bool
     */
    public function set($sKey, $mValue, $iTime = self::SHORT_TIME)
    {
        if (false === $this->oMemcache->set($sKey, $mValue, false, $iTime)) {
            $this->oLogger->addRecord(ULogger::ERROR, 'Cache impossible for Key : ' . $sKey . ' and Value : ' . $mValue);

            return false;
        }

        return (false !== isset($_GET['noCache']) && $_GET['noCache'] != 'y') ?: false;
    }

    /**
     * @param $mKey array|string
     * @return array|string
     */
    public function get($mKey)
    {
        if (isset($_GET['clearCache']) && $_GET['clearCache'] == 'y' ||
            isset($_GET['noCache']) && $_GET['noCache'] == 'y'
        ) {
            $this->delete($mKey);
        } else if (isset($_GET['flushCache']) && $_GET['flushCache'] == 'y') {
            $this->flush();
        }

        return $this->oMemcache->get($mKey);
    }

    public function delete($mKey)
    {
        $this->oMemcache->delete($mKey);
    }

    public function flush()
    {
        return $this->oMemcache->flush();
    }

    public function close()
    {
        return $this->oMemcache->close();
    }

    /**
     * Return server stats
     * @return array|bool
     */
    public function getStats()
    {
        return $this->oMemcache->getStats();
    }

    /**
     * return if server is online or offline
     * @return bool
     */
    public function getServerStatus()
    {
        if (0 === $this->oMemcache->getServerStatus()) {
            $this->oLogger->addRecord(ULogger::CRITICAL, 'Server Memcache inactive');

            return false;
        }

        return true;
    }
}
