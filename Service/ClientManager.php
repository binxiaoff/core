<?php
namespace Unilend\Service;

use Unilend\core\Loader;

/**
 * Class ClientManager
 * @package Unilend\Service
 */

class ClientManager
{
    /** @var ClientSettingsManager */
    private $oClientSettingsManager;

    public function __construct()
    {
        $this->oClientSettingsManager = Loader::loadService('ClientSettingsManager');
    }


    /**
     * @param \clients $oClient
     *
     * @return bool
     */
    public function isBetaTester(\clients $oClient)
    {
        return (bool) $this->oClientSettingsManager->getSetting($oClient, \client_setting_type::TYPE_BETA_TESTER);
    }

}
