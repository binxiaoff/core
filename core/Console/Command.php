<?php
namespace Unilend\core\Console;

use Symfony\Component\Console\Command\Command as BaseCommand;
use Unilend\librairies\Cache;
use Unilend\librairies\ULogger;
use Unilend\core\Loader;

class Command extends BaseCommand
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

    public function __construct()
    {
        parent::__construct();

        setlocale(LC_TIME, 'fr_FR.utf8');
        setlocale(LC_TIME, 'fr_FR');
        date_default_timezone_set('Europe/Paris');

        $this->oCache     = Cache::getInstance();
        $this->aConfig    = Loader::loadConfig();
        $this->oSemaphore = $this->loadData('settings');
    }

    protected function loadData($object, $params = array())
    {
        return Loader::loadData($object, $params);
    }
}
