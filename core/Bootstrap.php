<?php

namespace Unilend\core;

use Unilend\librairies\ULogger;

require_once __DIR__ . '/bdd.class.php';
require_once __DIR__ . '/../data/crud/settings.crud.php';
require_once __DIR__ . '/../data/settings.data.php';

class Bootstrap
{
    /**
     * @var $oInstance Instance of this object
     */
    private static $oInstance;

    /**
     * @var bdd
     */
    private $oDatabase;

    /**
     * @var ULogger
     */
    private $oLogger;

    /**
     * @var settings
     */
    private $oSettings;

    /**
     * @array $aConfig file config in root path
     */
    public static $aConfig;

    /**
     * @param array $aConfig
     * @return Bootstrap
     */
    public static function getInstance($aConfig)
    {
        if (true === is_null(self::$oInstance)) {
            self::$oInstance = new Bootstrap();
            self::$oInstance->setAssert();
            self::$oInstance->setConfig($aConfig);
        }

        return self::$oInstance;
    }

    /**
     * Active assert, cache error on screen and define callback for errors
     */
    public function setAssert()
    {
        assert_options(ASSERT_ACTIVE, 1);
        assert_options(ASSERT_WARNING, 0);
        assert_options(ASSERT_QUIET_EVAL, 1);
        assert_options(ASSERT_CALLBACK, array($this, 'errorAssert'));
    }

    public function setDatabase()
    {
        $this->oDatabase = new \bdd(self::$aConfig['bdd_config'][self::$aConfig['env']],
            self::$aConfig['bdd_option'][self::$aConfig['env']]);

        return $this;
    }

    public function getDatabase()
    {
        assert('is_object($this->oDatabase); //Database is not an object');

        return $this->oDatabase;
    }

    public function setSettings()
    {
        if (false === is_object($this->oDatabase)) {
            $this->setDatabase();
        }

        $this->oSettings = new \settings($this->oDatabase);

        return $this;
    }

    public function getSettings()
    {
        assert('is_object($this->oSettings); //Settings is not an object');

        return $this->oSettings;
    }

    /**
     * @param string $sNameChannel name of logger context
     * @param string $sNameLog name of file log
     * @return object $this
     */
    public function setLogger($sNameChannel, $sNameLog)
    {
        //We check, and add if necessary, if log's name have extension .log
        $sNameLog .= (!preg_match('/(\.log)$/i', $sNameLog)) ? '.log' : '';

        $this->oLogger = new ULogger($sNameChannel, self::$aConfig['log_path'][self::$aConfig['env']], $sNameLog);

        return $this;
    }

    public function setConfig($sConfig)
    {
        self::$aConfig = $sConfig;

        return $this;
    }

    public function getLogger()
    {
        assert('is_object($this->oLogger); //Logger is not an object');

        return $this->oLogger;
    }

    public function getCron()
    {
        return new Cron($this);
    }

    /**
     * @param string $sFunction name of function where assert it's in error
     * @param string $sLine line of function
     * @param string $sError assert + detail of error (separate by //)
     */
    public function errorAssert($sFunction, $sLine, $sError)
    {
        $this->setLogger('ErrorAssertion', 'assert.log');
        $aErrorDetails = explode('//', $sError);
        $this->oLogger->addRecord(ULogger::ERROR, 'Wrong Assertion in ' . $sFunction . ' at line ' . $sLine . '. ' . $aErrorDetails[1],
            array(__FILE__ . ' at ' . __LINE__));
    }
}
