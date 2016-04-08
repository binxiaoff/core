<?php
namespace Unilend\core\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Unilend\librairies\Cache;
use Unilend\librairies\ULogger;
use Unilend\core\Loader;

class ContainerAwareCommand extends Command implements ContainerAwareInterface
{
    /** @var Cache */
    public $oCache;
    /** @var array */
    public $aConfig;
    /** @var \settings */
    public $oSemaphore;
    /** @var ULogger */
    public $oLogger;
    /** @var integer */
    public $iStartTime;

    public function __construct($name = null)
    {
        parent::__construct($name);

        setlocale(LC_TIME, 'fr_FR.utf8');
        setlocale(LC_TIME, 'fr_FR');

        require_once __DIR__ . '/../Autoloader.php';
        \Autoloader::register();

        $this->oCache = Cache::getInstance();
        $this->aConfig = Loader::loadConfig();
        $this->oSemaphore = $this->loadData('settings');
    }

    protected function setContainer()
    {

    }

    protected function loadData($object, $params = array())
    {
        return Loader::loadData($object, $params);
    }

    protected function loadLib($library, $params = array())
    {
        return Loader::loadLib($library, $params);
    }

    protected function get($sService, $aParams = array())
    {
        return Loader::loadService($sService, $aParams);
    }

    /**
     * @param $sName  string Cron name (used for settings name)
     * @param $iDelay int    Minimum delay (in minutes) before we consider cron has crashed and needs to be restarted
     * @return bool
     */
    protected function startCron($sName, $iDelay)
    {
        $this->iStartTime = time();
        $this->oLogger    = new ULogger($sName, $this->aConfig['log_path'][$this->aConfig['env']], 'cron.' . date('Ymd') . '.log');

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

    protected function stopCron()
    {
        $this->oSemaphore->value = 1;
        $this->oSemaphore->update();

        $this->oLogger->addRecord(ULogger::INFO, 'End cron', array('ID' => $this->iStartTime));
    }

}