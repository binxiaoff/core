<?php

use Psr\Log\LoggerInterface;
use Unilend\librairies\greenPoint\greenPoint;
use Unilend\librairies\greenPoint\greenPointStatus;

class cronController extends bootstrap
{
    /**
     * @var string $sHeadersDebug headers for mail to debug
     */
    private $sHeadersDebug;

    /**
     * @var string $sDestinatairesDebug Destinataires for mail to debug
     */
    private $sDestinatairesDebug;

    /**
     * @var int
     */
    private $iStartTime;

    /**
     * @var settings
     */
    private $oSemaphore;

    /** @var  LoggerInterface */
    private $oLogger;

    public function initialize()
    {
        parent::initialize();

        // Inclusion controller pdf
        include_once $this->path . '/apps/default/controllers/pdf.php';

        $this->hideDecoration();
        $this->autoFireView = false;
        $this->oLogger = $this->get('monolog.logger.console');

        $this->settings->get('DebugMailFrom', 'type');
        $debugEmail = $this->settings->value;
        $this->settings->get('DebugMailIt', 'type');
        $this->sDestinatairesDebug = $this->settings->value;
        $this->sHeadersDebug       = 'MIME-Version: 1.0' . "\r\n";
        $this->sHeadersDebug .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        $this->sHeadersDebug .= 'From: ' . $debugEmail . "\r\n";
    }

    /**
     * @param $sName  string Cron name (used for settings name)
     * @param $iDelay int    Minimum delay (in minutes) before we consider cron has crashed and needs to be restarted
     * @return bool
     */
    private function startCron($sName, $iDelay)
    {
        $this->iStartTime = time();
        $this->oSemaphore = $this->loadData('settings');
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
            $this->oLogger->info('Started cron ' . $sName . ' - Cron ID=' . $this->iStartTime, array('class' => __CLASS__, 'function' => __FUNCTION__));

            return true;
        }
        $this->oLogger->info('Semaphore locked', array('class' => __CLASS__, 'function' => __FUNCTION__));

        return false;
    }

    private function stopCron()
    {
        $this->oSemaphore->value = 1;
        $this->oSemaphore->update();
        $this->oLogger->info('End cron ID=' . $this->iStartTime, array('class' => __CLASS__, 'function' => __FUNCTION__));
    }

    public function _default()
    {
        die;
    }

}
