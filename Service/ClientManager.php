<?php
namespace Unilend\Service;

use Unilend\core\Loader;

/**
 * Class ClientManager
 * @package Unilend\Service
 */

class ClientManager extends Service
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
        return (bool)$this->oClientSettingsManager->getSetting($oClient, \client_setting_type::TYPE_BETA_TESTER);
    }

    /**
     * @param \clients $oClient
     * @param          $iLegalDocId
     *
     * @return bool
     */
    public function isAcceptedCGV(\clients $oClient, $iLegalDocId)
    {
        /** @var \acceptations_legal_docs $oAcceptationLegalDocs */
        $oAcceptationLegalDocs = $this->loadData('acceptations_legal_docs');
        return $oAcceptationLegalDocs->exist($oClient->id_client, 'id_legal_doc = ' . $iLegalDocId . ' AND id_client ');
    }
}
