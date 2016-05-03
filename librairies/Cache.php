<?php

namespace Unilend\librairies;

/**
 * Class Cache
 * @package Unilend\librairies
 * @deprecated
 */
class Cache extends \Memcache
{
    const SHORT_TIME  = 300;
    const MEDIUM_TIME = 1800;
    const LONG_TIME   = 3600;

    /**
     * constant for list and count projects
     */
    const LIST_PROJECTS = 'List_Counter_Projects';
    const AVG_RATE_PROJECTS = 'projects_getAvgRate';
    const BID_ACCEPTATION_POSSIBILITY = 'bids_getAcceptationPossibilityRounded';

    /**
     * @var self
     */
    private static $oInstance;

    public function __construct()
    {
        /* @var array $config */
        include __DIR__ . '/../config.php';
        $this->connect($config['cache'][$config['env']]['serverAddress'], $config['cache'][$config['env']]['serverPort']);

        if (isset($_GET['flushCache']) && $_GET['flushCache'] == 'y') {
            $this->flush();
        }
    }

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

    public function makeKey()
    {
        $aKey = array();
        foreach (func_get_args() as $mParameters) {
            if (null === $mParameters) {
                $mParameters = '';
            }
            if (is_scalar($mParameters)) {
                $aKey[] = $mParameters;
            } else {
                trigger_error('Parameter : ' . serialize($mParameters) . ' not a scalar variable.', E_USER_WARNING);
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
        if (false === parent::set($sKey, $mValue, false, $iTime)) {
            trigger_error('Cache impossible for Key : ' . $sKey, E_USER_WARNING);

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

        return parent::get($mKey);
    }
}
