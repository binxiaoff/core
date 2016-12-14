<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\AcceptationsLegalDocs;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsAdresses;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\Settings;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;;
use Unilend\Bundle\FrontBundle\Security\User\UserLender;

/**
 * Class ClientManager
 * @package Unilend\Bundle\CoreBusinessBundle\Service
 */
class ClientManager
{
    const SESSION_KEY_TOS_ACCEPTED = 'user_legal_doc_accepted';

    /** @var EntityManagerSimulator */
    private $oEntityManager;
    /** @var ClientSettingsManager */
    private $oClientSettingsManager;
    /** @var TokenStorageInterface */
    private $tokenStorage;
    /** @var RequestStack */
    private $requestStack;
    /** @var WalletCreationManager */
    private $walletCreationManager;
    /** @var EntityManager*/
    private $em;
    /** @var  LoggerInterface */
    private $logger;

    public function __construct(
        EntityManagerSimulator $oEntityManager,
        ClientSettingsManager $oClientSettingsManager,
        TokenStorageInterface $tokenStorage,
        RequestStack $requestStack,
        WalletCreationManager $walletCreationManager,
        EntityManager $em,
        LoggerInterface $logger
    ) {
        $this->oEntityManager         = $oEntityManager;
        $this->oClientSettingsManager = $oClientSettingsManager;
        $this->tokenStorage           = $tokenStorage;
        $this->requestStack           = $requestStack;
        $this->walletCreationManager  = $walletCreationManager;
        $this->em                     = $em;
        $this->logger                 = $logger;
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
     * If the lender has accepted the last TOS, the session will not be set, and we check if there is a new TOS all the time
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

    /**
     * @param \clients|Clients $client
     *
     * @return string
     */
    public function getLastTosId($client)
    {
        if ($client instanceof \clients) {
            $client = $this->em->getRepository('UnilendCoreBusinessBundle:Clients')->find($client->id_client);
        }

        if (in_array($client->getType(), [\clients::TYPE_PERSON, \clients::TYPE_PERSON_FOREIGNER])) {
            $type = 'Lien conditions generales inscription preteur particulier';
        } else {
            $type = 'Lien conditions generales inscription preteur societe';
        }

        /** @var Settings $settingsEntity */
        $settingsEntity =  $this->em->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => $type]);
        return $settingsEntity->getValue();
    }

    /**
     * @param \clients|Clients $client
     */
    public function acceptLastTos($client)
    {
        if ($client instanceof \clients) {
            $client = $this->em->getRepository('UnilendCoreBusinessBundle:Clients')->find($client->id_client);
        }

        if (false === empty($client)) {
            $termsOfUse = new AcceptationsLegalDocs();
            $termsOfUse->setIdLegalDoc($this->getLastTosId($client));
            $termsOfUse->setIdClient($client->getIdClient());

            $this->em->persist($termsOfUse);
            $this->em->flush();

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
     * @param \clients | Clients $client
     * @param \clients_adresses | ClientsAdresses $clientsAddress
     * @param integer $typeId
     * @param Companies|null $company
     * @return string
     */
    public function createClient($client, $clientsAddress, $typeId, Companies $company = null)
    {
        $this->em->beginTransaction();
        $clientEntity = '';
        try {
            if ($client instanceof \clients) {
                $client = $this->matchClientDataOnEntity($client);
            }

            if ($client instanceof Clients){
                if (false === is_null($client->getIdClient())) {
                    return false;
                }
            }

            $this->em->persist($client);
            $this->em->flush();

            if ($clientsAddress instanceof \clients_adresses) {
                $clientsAddress = $this->matchClientAddressDataOnEntity($clientsAddress);
            }

            $clientsAddress->setIdClient($client->getIdClient());
            $this->em->persist($clientsAddress);

            if (null !== $company
                && ($typeId == WalletType::LENDER && in_array($client->getType(), [\clients::TYPE_LEGAL_ENTITY, \clients::TYPE_LEGAL_ENTITY_FOREIGNER])
                    || $typeId == WalletType::BORROWER)
            ){
                $company->setIdClientOwner($client->getIdClient());
                $this->em->persist($company);
            }

            $this->em->flush();
            $this->walletCreationManager->createWallet($client, $typeId);
            $this->em->commit();
        } catch (Exception $exception) {
            $this->em->getConnection()->rollBack();
            $this->logger->error('An error occurred while creating client ' [['class' => __CLASS__, 'function' => __FUNCTION__]]);
        }

        return $clientEntity;
    }

    /**
     * @param \clients $client
     * @return null|object|Clients
     */
    private function matchClientDataOnEntity(\clients $client)
    {
        if (false === empty($client->id_client)) {
            $clientEntity = $this->em->getRepository('UnilendCoreBusinessBundle:Clients')->find($client->id_client);
        } else {
            $clientEntity = new Clients();
        }

        $clientEntity->setCivilite($client->civilite);
        $clientEntity->setNom($client->nom);
        $clientEntity->setNomUsage($client->nom_usage);
        $clientEntity->setPrenom($client->prenom);
        $clientEntity->setFonction($client->fonction);
        $clientEntity->setNaissance($client->naissance);
        $clientEntity->setIdPaysNaissance($client->id_pays_naissance);
        $clientEntity->setVilleNaissance($client->ville_naissance);
        $clientEntity->setInseeBirth($client->insee_birth);
        $clientEntity->setIdNationalite($client->id_nationalite);
        $clientEntity->setTelephone($client->telephone);
        $clientEntity->setMobile($client->mobile);
        $clientEntity->setEmail($client->email);
        $clientEntity->setPassword($client->password);
        $clientEntity->setSecreteQuestion($client->secrete_question);
        $clientEntity->setSecreteReponse($client->secrete_reponse);
        $clientEntity->setType($client->type);
        $clientEntity->setFundsOrigin($client->funds_origin);
        $clientEntity->setFundsOriginDetail($client->funds_origin_detail);
        $clientEntity->setEtapeInscriptionPreteur($client->etape_inscription_preteur);
        $clientEntity->setStatusInscriptionPreteur($client->status_inscription_preteur);
        $clientEntity->setSource($client->source);
        $clientEntity->setSource2($client->source2);
        $clientEntity->setSource3($client->source3);
        $clientEntity->setSlugOrigine($client->slug_origine);
        $clientEntity->setOrigine($client->origine);
        $clientEntity->setLastlogin($client->lastlogin);

        return $clientEntity;
    }

    /**
     * @param \clients_adresses $clientAddress
     * @return null|object|ClientsAdresses
     */
    private function matchClientAddressDataOnEntity(\clients_adresses $clientAddress)
    {
        if (false === empty($clientAddress->id_adresse)) {
            $clientAddressEntity = $this->em->getRepository('UnilendCoreBusinessBundle:ClientsAdresses')->find($clientAddress->id_adresse);
        } else {
            $clientAddressEntity = new ClientsAdresses();
        }

        $clientAddressEntity->setDefaut($clientAddress->defaut);
        $clientAddressEntity->setType($clientAddress->type);
        $clientAddressEntity->setNomAdresse($clientAddress->nom_adresse);
        $clientAddressEntity->setCivilite($clientAddress->civilite);
        $clientAddressEntity->setNom($clientAddress->nom);
        $clientAddressEntity->setPrenom($clientAddress->prenom);
        $clientAddressEntity->setSociete($clientAddress->societe);
        $clientAddressEntity->setAdresse1($clientAddress->adresse1);
        $clientAddressEntity->setAdresse2($clientAddress->adresse2);
        $clientAddressEntity->setAdresse3($clientAddress->adresse3);
        $clientAddressEntity->setCp($clientAddress->cp);
        $clientAddressEntity->setVille($clientAddress->ville);
        $clientAddressEntity->setIdPays($clientAddress->id_pays);
        $clientAddressEntity->setTelephone($clientAddress->telephone);
        $clientAddressEntity->setMobile($clientAddress->mobile);
        $clientAddressEntity->setCommentaire($clientAddress->commentaire);
        $clientAddressEntity->setMemeAdresseFiscal($clientAddress->meme_adresse_fiscal);
        $clientAddressEntity->setAdresseFiscal($clientAddress->adresse_fiscal);
        $clientAddressEntity->setVilleFiscal($clientAddress->ville_fiscal);
        $clientAddressEntity->setCpFiscal($clientAddress->cp_fiscal);
        $clientAddressEntity->setIdPaysFiscal($clientAddress->id_pays_fiscal);
        $clientAddressEntity->setStatus($clientAddress->status);

        return $clientAddressEntity;
    }

}
