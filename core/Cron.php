<?php

namespace Unilend\core;

use Unilend\librairies\ULogger;

final class Cron
{
    const OPTION_REQUIRED  = 1;
    const OPTION_OPTIONAL  = 2;
    const MAX_EXECUTE_TIME = 5;

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
    private $oLogger;

    /**
     * @var int
     */
    private $iStartTime;

    /**
     * @var Bootstrap
     */
    private $oBootstrap;


    /**
     * @param Bootstrap $oBootstrap
     */
    public function __construct(Bootstrap $oBootstrap)
    {
        $this->oBootstrap = $oBootstrap;
    }

    /**
     * function for determine differents options for cron manager
     * @param array $aOptions
     * @return $this
     */
    public function setOptions(array $aOptions)
    {
        $this->aOptions = $aOptions;

        return $this;
    }

    /**
     * Set Description for each options
     * @param $sOption
     * @param $sDescription
     * @return $this
     */
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
        return $this->oLogger;
    }

    /**
     * Function for determine if each options declared in cron manager exist
     */
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

    public function executeCron()
    {
        $iMinute = (int)$this->getOptions('t');
        $iMinute = (isset($iMinute) && 0 < $iMinute) ? $iMinute : self::MAX_EXECUTE_TIME;
        if ($this->startCron($this->getOptions('s'), $iMinute)) {
            $sClassName = '\\' . $this->getOptions('d') . '\\' . $this->getOptions('c');
            $oClassCall = new $sClassName($this->oBootstrap);
            $oClassCall->{$this->getOptions('f')}();
            $this->stopCron();
        }
    }

    /**
     * @param $sName  string Cron name (used for settings name)
     * @param $iDelay int    Minimum delay (in minutes) before we consider cron has crashed and needs to be restarted
     * @return bool
     */
    private function startCron($sName, $iDelay)
    {
        $this->iStartTime = time();
        $this->oLogger    = $this->oBootstrap->setLogger($sName, 'cron.log')->getLogger();
        $this->oSemaphore = $this->oBootstrap->setSettings()->getSettings();
        $this->oSemaphore->get('Controle cron ' . $sName, 'type');

        if ($this->oSemaphore->value == 0) {
            $iUpdatedDateTime      = strtotime($this->oSemaphore->updated);
            $iMinimumDelayDateTime = mktime(date('H'), date('i') - $iDelay, 0, date('m'), date('d'), date('Y'));

            if ($iUpdatedDateTime <= $iMinimumDelayDateTime) {
                $this->oSemaphore->value = 1;
                $this->oSemaphore->update();
            }
        }

        if ($this->oSemaphore->value == 1) {
            $this->oSemaphore->value = 0;
            $this->oSemaphore->update();

            $this->oLogger->addRecord(ULogger::INFO, 'Start cron', array('ID' => $this->iStartTime));

            return true;
        }

        $this->oLogger->addRecord(ULogger::INFO, 'Semaphore locked', array('ID' => $this->iStartTime));

        return false;
    }

    private function stopCron()
    {
        $this->oSemaphore->value = 1;
        $this->oSemaphore->update();

        $this->oLogger->addRecord(ULogger::INFO, 'End cron', array('ID' => $this->iStartTime));
    }
}