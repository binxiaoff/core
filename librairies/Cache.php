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
     * @var self
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
     * @return Cache
     */
    public static function getInstance()
    {
        if (true === is_null(self::$oInstance)) {
            self::$oInstance = new self();
        }

        return self::$oInstance;
    }

    private function __construct()
    {
        /* @var array $config */
        include __DIR__ . '/../config.php';

        $this->oMemcache = new Memcache();
        $this->oMemcache->connect($config['cache'][$config['env']]['serverAddress'], $config['cache'][$config['env']]['serverPort']);

        $this->oLogger = new ULogger('Cache', __DIR__ . $config['log_path'][$config['env']], 'cache.log');

        if (isset($_GET['flushCache']) && $_GET['flushCache'] == 'y') {
            $this->flush();
        }
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

        $sKey = ENVIRONMENT . '_' . implode('_', $aKey);

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
            $this->oLogger->addRecord(ULogger::ERROR, 'Cache impossible for Key : ' . $sKey);

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
        if (isset($_GET['noCache']) && $_GET['noCache'] == 'y') {
            return false;
        }

        if (isset($_GET['clearCache']) && $_GET['clearCache'] == 'y') {
            $this->delete($mKey);
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
