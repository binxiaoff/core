<?php

/**
 * @todo
 * Why soo many setters?
 * $oLogger changes from channel to channel everytime we log something and is instanciated many times
 * Assertions not configurable by environment
 * Lenders loaded here?
 */

namespace Unilend\core;

use Unilend\librairies\ULogger;

require_once __DIR__ . '/bdd.class.php';
require_once __DIR__ . '/../data/crud/settings.crud.php';
require_once __DIR__ . '/../data/settings.data.php';
require_once __DIR__ . '/../data/crud/lenders_accounts.crud.php';
require_once __DIR__ . '/../data/lenders_accounts.data.php';

class Bootstrap
{
    /**
     * @var self $oInstance
     */
    private static $oInstance;

    /**
     * @var \bdd
     */
    private $oDatabase;

    /**
     * @var ULogger
     */
    private $oLogger;

    /**
     * @var \settings
     */
    private $oSettings;

    /**
     * @var \lenders_accounts
     */
    private $oLenders;

    /**
     * @array $aConfig file config in root path
     */
    public static $aConfig;

    /**
     * @param array $aConfig
     * @return self
     */
    public static function getInstance(array $aConfig)
    {
        if (true === is_null(self::$oInstance)) {
            self::$oInstance = new self();
            self::$oInstance->setLocales();
            self::$oInstance->setAssert();
            self::$aConfig = $aConfig;
        }

        return self::$oInstance;
    }

    public function setLocales()
    {
        setlocale(LC_TIME, 'fr_FR.utf8');
        setlocale(LC_TIME, 'fr_FR');
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
        $this->oDatabase = \bdd::instance(
            self::$aConfig['bdd_config'][self::$aConfig['env']],
            self::$aConfig['bdd_option'][self::$aConfig['env']]
        );

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

    public function setLenders()
    {
        $this->oLenders = new \lenders_accounts($this->oDatabase);

        return $this;
    }

    public function getLenders()
    {
        assert('is_object($this->oLenders); //Settings is not an object');

        return $this->oLenders;
    }

    /**
     * @param string $sNameChannel name of logger context
     * @param string $sNameLog name of file log
     * @return object $this
     */
    public function setLogger($sNameChannel, $sNameLog)
    {
        //We check, and add if necessary, if log's name have extension .log
        $sNameLog .= (1 !== preg_match('/\.log$/i', $sNameLog)) ? '.log' : '';

        $this->oLogger = new ULogger($sNameChannel, self::$aConfig['log_path'][self::$aConfig['env']], $sNameLog);

        return $this;
    }

    public function getLogger()
    {
        assert('is_object($this->oLogger); //Logger is not an object');

        return $this->oLogger;
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
