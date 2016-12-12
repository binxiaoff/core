<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\FrontBundle\Security\User\UserLender;

/**
 * Class ClientManager
 * @package Unilend\Bundle\CoreBusinessBundle\Service
 */
class ClientManager
{
    const SESSION_KEY_TOS_ACCEPTED = 'user_legal_doc_accepted';

    /** @var ClientSettingsManager */
    private $oClientSettingsManager;
    /** @var TokenStorageInterface */
    private $tokenStorage;
    /** @var RequestStack */
    private $requestStack;

    public function __construct(EntityManager $oEntityManager, ClientSettingsManager $oClientSettingsManager, TokenStorageInterface $tokenStorage, RequestStack $requestStack)
    {
        $this->oEntityManager         = $oEntityManager;
        $this->oClientSettingsManager = $oClientSettingsManager;
        $this->tokenStorage           = $tokenStorage;
        $this->requestStack           = $requestStack;
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
     * If the lender has accepted the last TOS, the seesion will not be set, and we check if there is a new TOS all the time
     * Otherwise, the session will be set with accepted = false. We check no longer the now TOS, but we read the value from the session.
     */
    public function ifLastTOSAccepted()
    {
        $session = $this->requestStack->getCurrentRequest()->getSession();
        if ($session->has(self::SESSION_KEY_TOS_ACCEPTED))
        {
            return; // already checked and not accepted
        }

        $token = $this->tokenStorage->getToken();
        if ($token) {
            $user = $token->getUser();
            if ($user instanceof UserLender) {
                /** @var \clients $client */
                $client = $this->oEntityManager->getRepository('clients');
                if ($client->get($user->getClientId())) {
                    if (false === $this->isAcceptedCGV($client, $this->getLastTosId($client))) {
                        $session->set(self::SESSION_KEY_TOS_ACCEPTED, false);
                    }
                }
            }
        }
    }

    public function getLastTosId(\clients $client)
    {
        /** @var \settings $settings */
        $settings = $this->oEntityManager->getRepository('settings');
        if (in_array($client->type, array(\clients::TYPE_LEGAL_ENTITY, \clients::TYPE_LEGAL_ENTITY_FOREIGNER))) {
            $settingsType = 'Lien conditions generales inscription preteur societe';
        } else {
            $settingsType = 'Lien conditions generales inscription preteur particulier';
        }
        $settings->get($settingsType, 'type');

        return $settings->value;
    }

    public function acceptLastTos(\clients $client)
    {
        if (false === empty($client->id_client)) {
            /** @var \acceptations_legal_docs $tosAccepted */
            $tosAccepted = $this->oEntityManager->getRepository('acceptations_legal_docs');
            $tosAccepted->id_client = $client->id_client;
            $tosAccepted->id_legal_doc = $this->getLastTosId($client);
            $tosAccepted->create();

            $session = $this->requestStack->getCurrentRequest()->getSession();
            $session->remove(self::SESSION_KEY_TOS_ACCEPTED);
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

    public function getClientSubscriptionStep(\clients $oClient)
    {
        return $oClient->etape_inscription_preteur;
    }

    /**
     * @param \clients $client
     * @return bool
     */
    public function isValidated(\clients $client)
    {
        /** @var \clients_status $lastClientStatus */
        $lastClientStatus = $this->oEntityManager->getRepository('clients_status');
        $lastClientStatus->getLastStatut($client->id_client);
        return $lastClientStatus->status == \clients_status::VALIDATED;
    }

}
