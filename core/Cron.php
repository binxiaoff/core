<?php

/**
 * @todo
 * If option "f" if not provided, cron crashes
 */

namespace Unilend\core;

use Unilend\librairies\ULogger;

final class Cron
{
    const OPTION_REQUIRED    = 1;
    const OPTION_OPTIONAL    = 2;
    const MAX_EXECUTION_TIME = 5;

    /**
     * @var array
     */
    private $aOptions;

    /**
     * @var array
     */
    private $aParameters;

    /**
     * @var array
     */
    private $aDescription;

    /**
     * @var Bootstrap
     */
    private $oBootstrap;

    /**
     * @var ULogger
     */
    private $oLogger;

    /**
     * @var \settings
     */
    private $oSemaphore;

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

        return (true === isset($this->aParameters[$sNameOption])) ? $this->aParameters[$sNameOption] : null;
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
        // @todo rename var
        $iMinute = $this->getOptions('t');
        $iMinute = (false === is_null($iMinute) && 0 < $iMinute) ? (int) $iMinute : self::MAX_EXECUTION_TIME;

        if ($this->startCron($this->getOptions('s'), $iMinute)) {
            $sClassName = '\\' . $this->getOptions('d') . '\\' . $this->getOptions('c');
            $oClassCall = new $sClassName($this->oBootstrap);
            $oClassCall->{$this->getOptions('f')}();
            $this->stopCron();
        }
    }

    /**
     * @param string $sName  Cron name (used for settings name)
     * @param int    $iDelay Minimum delay (in minutes) before we consider cron has crashed and needs to be restarted
     * @return bool
     */
    private function startCron($sName, $iDelay)
    {
        $this->oLogger    = $this->oBootstrap->setLogger($sName, 'cron.' . date('Ymd') . '.log')->getLogger();
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

            $this->oLogger->addRecord(ULogger::INFO, 'Start cron');

            return true;
        }

        $this->oLogger->addRecord(ULogger::INFO, 'Semaphore locked');

        return false;
    }

    private function stopCron()
    {
        $this->oSemaphore->value = 1;
        $this->oSemaphore->update();

        $this->oLogger->addRecord(ULogger::INFO, 'End cron');
    }
}
