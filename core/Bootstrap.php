<?php

namespace Unilend\core;

use Unilend\librairies\ToLog;
use Unilend\core\Cron;

require_once __DIR__ . '/bdd.class.php';

class Bootstrap
{
    /**
     * @object $oInstance Instance of this object
     */
    private static $oInstance;

    /**
     * @object $oDatabase core\bdd()
     */
    private $oDatabase;

    /**
     * @object $oLogger Monolog\Logger()
     */
    private $oLogger;

    /**
     * @array $aConfig file config in root path
     */
    public static $aConfig;

    public static function getInstance()
    {
        if (true === is_null(self::$oInstance)) {
            self::$oInstance = new Bootstrap();
            self::$oInstance->setAssert();
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

    /**
     * @param string $sNameChannel name of logger context
     * @param string $sNameLog name of file log
     * @return object $this
     */
    public function setLogger($sNameChannel, $sNameLog)
    {
        //We check, and add if necessary, if log's name have extension .log
        $sNameLog .= (!preg_match('/(\.log)$/i', $sNameLog)) ? '.log' : '';

        $oToLog = new ToLog($sNameChannel, self::$aConfig['log_path'][self::$aConfig['env']], $sNameLog);
        $oToLog->setStreamHandlerInfo()
            ->setStreamHandlerDebug()
            ->setStreamHandlerError()
            ->setStreamHandlerWarning()
            ->setStreamHandlerCritical();
        $this->oLogger = $oToLog->getLogger();

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
        return new Cron($this->setLogger('Cron', 'cron.log')->getLogger());
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
        $this->oLogger->addError('Wrong Assertion in ' . $sFunction . ' at line ' . $sLine . '. ' . $aErrorDetails[1],
            array(__FILE__ . ' at ' . __LINE__));
    }
}
