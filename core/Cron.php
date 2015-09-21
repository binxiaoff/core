<?php

namespace Unilend\core;



use Unilend\librairies\ULogger;

final class Cron
{
    const OPTION_REQUIRED = 1;
    const OPTION_OPTIONAL = 2;

    /**
     * @var array with options of cron
     */
    private $aOptions;

    /**
     * @var array with parameters of cron command
     */
    private $aParameters;

    /**
     * @var ULogger
     */
    private $oLoggerCron;

    public function __construct(ULogger $oLoggerCron){
        $this->oLoggerCron = $oLoggerCron;
    }

    public function setOptions(array $aOptions)
    {
        $this->aOptions = $aOptions;

        return $this;
    }

    public function setDescription($sOption, $sDescription)
    {
        assert('is_string($sOption); //Option for description is not a string');

        $this->aDescription[$sOption] = $sDescription;

        return $this;
    }

    public function setParameters()
    {
        $this->aParameters = getopt(implode(':', array_keys($this->aOptions)) . ':');

        return $this;
    }

    public function getOptions($sNameOption)
    {
        assert('in_array($sNameOption,array_keys($this->aOptions)); //Option ' . $sNameOption . ' not exist');

        return (true === isset($this->aParameters[$sNameOption])) ? $this->aParameters[$sNameOption] : false;
    }

    public function getLogger()
    {
        return $this->oLoggerCron;
    }

    public function parseCommand()
    {
        foreach ($this->aOptions as $sOption => $sMode) {
            switch ($sMode) {
                case self::OPTION_REQUIRED:
                    if (false === array_key_exists($sOption, $this->aParameters)) {
                        throw new \UnexpectedValueException('Option ' . $sOption .
                            ' (' . $this->aDescription[$sOption] . ') is required.');
                    }
                    break;
            }
        }
    }

    public function executeCron(Bootstrap $oBootstrap)
    {
        $iTimeStartCron = microtime(true);
        $sTextLogCron = '';

        $sClassName = '\\' . $this->getOptions('d') . '\\' . $this->getOptions('c');
        $oClassCall = new $sClassName($oBootstrap);

        $sFunctionToCall = $this->getOptions('f');
        if (false !== $sFunctionToCall) {
            $oClassCall->$sFunctionToCall();
            $sTextLogCron = ', Function : ' . $sFunctionToCall;
        }

        $iTimeEndCron = microtime(true) - $iTimeStartCron;
        $this->oLoggerCron->addRecord('info','Call class ' . $sClassName . $sTextLogCron . ' and execute in '
            . round($iTimeEndCron, 2), array(__FILE__ . ' at ' . __LINE__));
    }
}