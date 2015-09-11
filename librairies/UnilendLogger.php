<?php

namespace librairies;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

class UnilendLogger
{

    /**
     * @object Monolog\Logger
     */
    private $oLogger;

    /**
     * @object Monolog\Formatter\LineFormatter
     */
    private $oFormatter;

    /**
     * @object Monolog\Handler\StreamHandler
     */
    private $oStreamHandlerInfo;

    /**
     * @object Monolog\Handler\StreamHandler
     */
    private $oStreamHandlerDebug;

    /**
     * @object Monolog\Handler\StreamHandler
     */
    private $oStreamHandlerError;

    public function __construct($sNameLogger, $sPathLog, $sNameLog)
    {
        $this->setStreamHandler($sPathLog . $sNameLog);
        $this->setLogger($sNameLogger);
    }

    public function setFormatterLog()
    {
        $sDateFormat = "d-m-Y H:i:s";
        $sOutput = "[%datetime%] [%channel%] [%level_name%] %message% %context% %extra%\n";
        $this->oFormatter = new LineFormatter($sOutput, $sDateFormat);
    }

    public function setStreamHandler($sFullPathLog)
    {
        $this->setFormatterLog();
        $this->oStreamHandlerInfo = new StreamHandler($sFullPathLog, Logger::INFO);
        $this->oStreamHandlerDebug = new StreamHandler($sFullPathLog, Logger::DEBUG);
        $this->oStreamHandlerError = new StreamHandler($sFullPathLog, Logger::ERROR);
        $this->oStreamHandlerInfo->setFormatter($this->oFormatter);
        $this->oStreamHandlerDebug->setFormatter($this->oFormatter);
        $this->oStreamHandlerError->setFormatter($this->oFormatter);
    }

    public function setLogger($sNameLogger)
    {
        $this->oLogger = new Logger($sNameLogger);
        $this->oLogger->pushHandler($this->oStreamHandlerInfo)
            ->pushHandler($this->oStreamHandlerDebug)
            ->pushHandler($this->oStreamHandlerError);
    }

    public function getLogger()
    {
        return $this->oLogger;
    }
}