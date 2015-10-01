<?php

namespace Unilend\librairies;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

class ULogger
{
    /**
     * Detailed debug information
     */
    const DEBUG = Logger::DEBUG;

    /**
     * Interesting events
     */
    const INFO = Logger::INFO;

    /**
     * Uncommon events
     */
    const NOTICE = Logger::NOTICE;

    /**
     * Exceptional occurrences that are not errors
     */
    const WARNING = Logger::WARNING;

    /**
     * Runtime errors
     */
    const ERROR = Logger::ERROR;

    /**
     * Critical conditions
     */
    const CRITICAL = Logger::CRITICAL;

    /**
     * Action must be taken immediately
     */
    const ALERT = Logger::ALERT;

    /**
     * Urgent alert.
     */
    const EMERGENCY = Logger::EMERGENCY;

    /**
     * @var LoggerInterface
     */
    private $oLogger;

    /**
     * @var LineFormatter
     */
    private $oFormatter;

    /**
     * @var StreamHandler
     */
    private $oStreamHandler;

    /**
     * @var string fullpath log
     */
    private $sFullPath;

    public function __construct($sNameLogger, $sPathLog, $sNameLog)
    {
        $this->setFormatterLog();
        $this->sFullPath = $sPathLog . $sNameLog;
        $this->oLogger   = new Logger($sNameLogger);
        $this->setStreamHandler();
    }

    public function setFormatterLog()
    {
        $sDateFormat      = "d-m-Y H:i:s";
        $sOutput          = "[%datetime%] [%channel%] [%level_name%] %message% %context% %extra%\n";
        $this->oFormatter = new LineFormatter($sOutput, $sDateFormat);
        return $this;
    }

    public function setStreamHandler()
    {
        $this->oStreamHandler = new StreamHandler($this->sFullPath);
        $this->oStreamHandler->setFormatter($this->oFormatter);
        $this->oLogger->pushHandler($this->oStreamHandler);

        return $this;
    }

    public function addRecord($sType, $sMessage, array $aContext = array())
    {
        return $this->oLogger->addRecord(constant('self::' . strtoupper($sType)), $sMessage, $aContext);
    }
}