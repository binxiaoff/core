<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\AcceptationsLegalDocs;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\Settings;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
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

    /**
     * ClientManager constructor.
     * @param EntityManagerSimulator $oEntityManager
     * @param ClientSettingsManager $oClientSettingsManager
     * @param TokenStorageInterface $tokenStorage
     * @param RequestStack $requestStack
     * @param WalletCreationManager $walletCreationManager
     * @param EntityManager $em
     * @param LoggerInterface $logger
     */
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
    public function checkLastTOSAccepted()
    {
        $session = $this->requestStack->getCurrentRequest()->getSession();

        if ($session->has(self::SESSION_KEY_TOS_ACCEPTED)) {
            return; // already checked and not accepted
        }

        $token = $this->tokenStorage->getToken();

        if ($token) {
            $user = $token->getUser();

            if ($user instanceof UserLender) {
                /** @var \clients $client */
                $client = $this->oEntityManager->getRepository('clients');

                if ($client->get($user->getClientId()) && false === $user->hasAcceptedCurrentTerms()) {
                    $session->set(self::SESSION_KEY_TOS_ACCEPTED, false);
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
     * @param \clients | Clients $oClient
     *
     * @return bool
     */
    public function isLender($oClient)
    {
        if ($oClient instanceof Clients) {
            $lenderWallet = $this->em->getRepository('UnilendCoreBusinessBundle:Clients')->getWalletByType($oClient->getIdClient(), WalletType::LENDER);
            return null !== $lenderWallet;
        }

        if ($oClient instanceof \clients) {
            if (empty($oClient->id_client)) {
                return false;
            }
            return $oClient->isLender();
        }

        return false;
    }

    /**
     * @param \clients | Clients $oClient
     *
     * @return bool
     */
    public function isBorrower($oClient)
    {
        if ($oClient instanceof Clients) {
            $borrowerWallet = $this->em->getRepository('UnilendCoreBusinessBundle:Clients')->getWalletByType($oClient->getIdClient(), WalletType::BORROWER);
            return null !== $borrowerWallet;
        }

        if ($oClient instanceof \clients) {
            if (empty($oClient->id_client)) {
                return false;
            }
            return $oClient->isBorrower();
        }

        return false;
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
        return $this->isAcceptedCGV($oClient, $this->getLastTosId($oClient));
    }

    public function getClientSubscriptionStep(\clients $oClient)
    {
        return $oClient->etape_inscription_preteur;
    }
}
