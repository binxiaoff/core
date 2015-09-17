<?php

namespace Unilend\librairies;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

class ToLog
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

    /**
     * @var string fullpath log
     */
    private $sFullPath;

    public function __construct($sNameLogger, $sPathLog, $sNameLog)
    {
        $this->setFormatterLog()
            ->setFullPath($sPathLog . $sNameLog)
            ->setLogger($sNameLogger);
    }

    public function setFullPath($sFullPath)
    {
        $this->sFullPath = $sFullPath;
        return $this;
    }

    public function setFormatterLog()
    {
        $sDateFormat = "d-m-Y H:i:s";
        $sOutput = "[%datetime%] [%channel%] [%level_name%] %message% %context% %extra%\n";
        $this->oFormatter = new LineFormatter($sOutput, $sDateFormat);
        return $this;
    }

    public function setStreamHandlerInfo()
    {
        $this->oStreamHandlerInfo = new StreamHandler($this->sFullPath, Logger::INFO);
        $this->oStreamHandlerInfo->setFormatter($this->oFormatter);
        $this->oLogger->pushHandler($this->oStreamHandlerInfo);

        return $this;
    }

    public function setStreamHandlerDebug()
    {
        $this->oStreamHandlerDebug = new StreamHandler($this->sFullPath, Logger::DEBUG);
        $this->oStreamHandlerDebug->setFormatter($this->oFormatter);
        $this->oLogger->pushHandler($this->oStreamHandlerDebug);

        return $this;
    }

    public function setStreamHandlerError()
    {
        $this->oStreamHandlerError = new StreamHandler($this->sFullPath, Logger::ERROR);
        $this->oStreamHandlerError->setFormatter($this->oFormatter);
        $this->oLogger->pushHandler($this->oStreamHandlerError);

        return $this;
    }

    public function setStreamHandlerAlert()
    {
        $this->oStreamHandlerAlert = new StreamHandler($this->sFullPath, Logger::ALERT);
        $this->oStreamHandlerAlert->setFormatter($this->oFormatter);
        $this->oLogger->pushHandler($this->oStreamHandlerAlert);

        return $this;
    }

    public function setStreamHandlerNotice()
    {
        $this->oStreamHandlerNotice = new StreamHandler($this->sFullPath, Logger::NOTICE);
        $this->oStreamHandlerNotice->setFormatter($this->oFormatter);
        $this->oLogger->pushHandler($this->oStreamHandlerNotice);

        return $this;
    }

    public function setStreamHandlerWarning()
    {
        $this->oStreamHandlerWarning = new StreamHandler($this->sFullPath, Logger::NOTICE);
        $this->oStreamHandlerWarning->setFormatter($this->oFormatter);
        $this->oLogger->pushHandler($this->oStreamHandlerWarning);

        return $this;
    }

    public function setStreamHandlerCritical()
    {
        $this->oStreamHandlerCritical = new StreamHandler($this->sFullPath, Logger::NOTICE);
        $this->oStreamHandlerCritical->setFormatter($this->oFormatter);
        $this->oLogger->pushHandler($this->oStreamHandlerCritical);

        return $this;
    }

    /**
     * @param string $sNameLogger name of logger context
     */
    public function setLogger($sNameLogger)
    {
        $this->oLogger = new Logger($sNameLogger);
    }

    public function getLogger()
    {
        return $this->oLogger;
    }
}