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
        $oAcceptationLegalDocs = Loader::loadData('acceptations_legal_docs');
        return $oAcceptationLegalDocs->exist($oClient->id_client, 'id_legal_doc = ' . $iLegalDocId . ' AND id_client ');
    }

    public function changeClientStatusFollowingClientAction(\clients $oClient, $sContent)
    {
        /** @var \clients_status_history $oClientStatusHistory */
        $oClientStatusHistory = Loader::loadData('clients_status_history');
        /** @var \clients_status $oLastClientStatus */
        $oLastClientStatus = Loader::loadData('clients_status');
        $oLastClientStatus->getLastStatut($oClient->id_client);

        switch ($oLastClientStatus->status) {
            case \clients_status::COMPLETENESS:
            case \clients_status::COMPLETENESS_REMINDER:
            case \clients_status::COMPLETENESS_REPLY:
                $oClientStatusHistory->addStatus(\users::USER_ID_FRONT, \clients_status::COMPLETENESS_REPLY, $oClient->id_client, $sContent);
                break;
            case \clients_status::VALIDATED:
                $oClientStatusHistory->addStatus(\users::USER_ID_FRONT, \clients_status::MODIFICATION, $oClient->id_client, $sContent);
                break;
            case \clients_status::TO_BE_CHECKED:
                $oClientStatusHistory->addStatus(\users::USER_ID_FRONT, \clients_status::TO_BE_CHECKED, $oClient->id_client, $sContent);
                break;
        }
    }


    /**
     * @param \clients $oClient
     *
     * @return bool
     */
    public function isLender(\clients $oClient)
    {
        if (empty($oClient->id_client)) {
            return false;
        } else {
            return $oClient->isLender();
        }
    }

    /**
     * @param \clients $oClient
     *
     * @return bool
     */
    public function isBorrower(\clients $oClient)
    {
        if (empty($oClient->id_client)) {
            return false;
        } else {
            return $oClient->isBorrower();
        }
    }
}
