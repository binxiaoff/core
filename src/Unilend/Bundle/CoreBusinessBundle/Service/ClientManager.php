<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

/**
 * Class ClientManager
 * @package Unilend\Bundle\CoreBusinessBundle\Service
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

    public function getClientBalance(\clients $oClient)
    {
        /** @var \transactions $transactions */
        $transactions = $this->oEntityManager->getRepository('transactions');
        $balance      = $transactions->getSolde($oClient->id_client);
        return $balance;
    }

    public function getClientInitials(\clients $oClient)
    {
        $initials = substr($oClient->prenom, 0, 1) . substr($oClient->nom, 0, 1);
        //TODO decide which initials to use in case of company

        return $initials;
    }

    public function isActive(\clients $oClient)
    {
        return (bool)$oClient->status;
    }

    public function getCurrentClientStatus(\clients $oClient)
    {
        /** @var \clients_status $lastClientStatus */
        $lastClientStatus = $this->oEntityManager->getRepository('clients_status');
        $lastClientStatus->getLastStatut($oClient->id_client);
        return $lastClientStatus->status;
    }

    public function hasAcceptedCurrentTerms(\clients $oClient)
    {
        /** @var \acceptations_legal_docs $acceptedTerms */
        $acceptedTerms = $this->oEntityManager->getRepository('acceptations_legal_docs');
        /** @var \settings $settings */
        $settings = $this->oEntityManager->getRepository('settings');

        if (in_array($oClient->type, array(\clients::TYPE_LEGAL_ENTITY, \clients::TYPE_LEGAL_ENTITY_FOREIGNER))) {
            $settings->get('Lien conditions generales inscription preteur societe', 'type');
            $sTermsAndConditionsLink = $settings->value;
        } else {
            $settings->get('Lien conditions generales inscription preteur particulier', 'type');
            $sTermsAndConditionsLink = $settings->value;
        }

        $aAcceptedTermsByClient = $acceptedTerms->selectAccepts('id_client = ' . $oClient->id_client);

        return in_array($sTermsAndConditionsLink, $aAcceptedTermsByClient);
    }

}
