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

    public function __construct(EntityManager $oEntityManager, ClientSettingsManager $oClientSettingsManager)
    {
        $this->oEntityManager         = $oEntityManager;
        $this->oClientSettingsManager = $oClientSettingsManager;
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
        $oAcceptationLegalDocs = $this->oEntityManager->getRepository('acceptations_legal_docs');
        return $oAcceptationLegalDocs->exist($oClient->id_client, 'id_legal_doc = ' . $iLegalDocId . ' AND id_client ');
    }

    /**
     * @param int    $iClientId
     * @param string $sContent
     */
    public function changeClientStatusTriggeredByClientAction($iClientId, $sContent)
    {
        /** @var \clients_status_history $oClientStatusHistory */
        $oClientStatusHistory = $this->oEntityManager->getRepository('clients_status_history');
        /** @var \clients_status $oLastClientStatus */
        $oLastClientStatus = $this->oEntityManager->getRepository('clients_status');
        $oLastClientStatus->getLastStatut($iClientId);

        switch ($oLastClientStatus->status) {
            case \clients_status::COMPLETENESS:
            case \clients_status::COMPLETENESS_REMINDER:
            case \clients_status::COMPLETENESS_REPLY:
                $oClientStatusHistory->addStatus(\users::USER_ID_FRONT, \clients_status::COMPLETENESS_REPLY, $iClientId, $sContent);
                break;
            case \clients_status::VALIDATED:
                $oClientStatusHistory->addStatus(\users::USER_ID_FRONT, \clients_status::MODIFICATION, $iClientId, $sContent);
                break;
            case \clients_status::TO_BE_CHECKED:
                $oClientStatusHistory->addStatus(\users::USER_ID_FRONT, \clients_status::TO_BE_CHECKED, $iClientId, $sContent);
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
