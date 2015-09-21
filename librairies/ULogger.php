<?php

namespace Unilend\librairies;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

class ULogger
{
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
        $this->oLogger = new Logger($sNameLogger);
        $this->setStreamHandler();
    }

    public function setFormatterLog()
    {
        $sDateFormat = "d-m-Y H:i:s";
        $sOutput = "[%datetime%] [%channel%] [%level_name%] %message% %context% %extra%\n";
        $this->oFormatter = new LineFormatter($sOutput, $sDateFormat);
        return $this;
    }

    public function setStreamHandler()
    {
        $oRefClass = new \ReflectionClass('Monolog\Logger');

        foreach($oRefClass->getConstants() as $iLevel) {
            $this->oStreamHandler = new StreamHandler($this->sFullPath, $iLevel);
            $this->oStreamHandler->setFormatter($this->oFormatter);
            $this->oLogger->pushHandler($this->oStreamHandler);
            unset($this->oStreamHandler);
        }
        return $this;
    }

    public function addRecord($sType, $sMessage, array $aContext = array())
    {
        return $this->oLogger->addRecord(constant('Monolog\Logger::' . strtoupper($sType)), $sMessage, $aContext);
    }
}