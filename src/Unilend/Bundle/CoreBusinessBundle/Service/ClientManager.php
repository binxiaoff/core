<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
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
    private $entityManagerSimulator;
    /** @var ClientSettingsManager */
    private $clientSettingsManager;
    /** @var TokenStorageInterface */
    private $tokenStorage;
    /** @var RequestStack */
    private $requestStack;
    /** @var  RouterInterface */
    private $router;
    /** @var WalletCreationManager */
    private $walletCreationManager;
    /** @var EntityManager*/
    private $entityManager;
    /** @var  LoggerInterface */
    private $logger;

    /**
     * ClientManager constructor.
     *
     * @param EntityManagerSimulator $entityManagerSimulator
     * @param ClientSettingsManager  $clientSettingsManager
     * @param TokenStorageInterface  $tokenStorage
     * @param RequestStack           $requestStack
     * @param WalletCreationManager  $walletCreationManager
     * @param EntityManager          $entityManager
     * @param LoggerInterface        $logger
     * @param RouterInterface        $router
     */
    public function __construct(
        EntityManagerSimulator $entityManagerSimulator,
        ClientSettingsManager $clientSettingsManager,
        TokenStorageInterface $tokenStorage,
        RequestStack $requestStack,
        WalletCreationManager $walletCreationManager,
        EntityManager $entityManager,
        LoggerInterface $logger,
        RouterInterface $router
    )
    {
        $this->entityManagerSimulator  = $entityManagerSimulator;
        $this->clientSettingsManager   = $clientSettingsManager;
        $this->tokenStorage            = $tokenStorage;
        $this->requestStack            = $requestStack;
        $this->walletCreationManager   = $walletCreationManager;
        $this->entityManager           = $entityManager;
        $this->logger                  = $logger;
        $this->router                  = $router;
    }


    /**
     * @param Clients|\clients $client
     *
     * @return bool
     */
    public function isBetaTester($client)
    {
        if ($client instanceof \clients) {
            $client = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($client->id_client);
        }

        return (bool) $this->clientSettingsManager->getSetting($client, \client_setting_type::TYPE_BETA_TESTER);
    }

    /**
     * @param \clients|Clients $client
     * @param int              $legalDocId
     *
     * @return bool
     */
    public function isAcceptedCGV($client, $legalDocId)
    {
        if ($client instanceof \clients) {
            $client = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($client->id_client);
        }

        /** @var \acceptations_legal_docs $oAcceptationLegalDocs */
        $oAcceptationLegalDocs = $this->entityManagerSimulator->getRepository('acceptations_legal_docs');

        return $oAcceptationLegalDocs->exist($client->getIdClient(), 'id_legal_doc = ' . $legalDocId . ' AND id_client ');
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
                $client = $this->entityManagerSimulator->getRepository('clients');

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
            $client = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($client->id_client);
        }

        if (in_array($client->getType(), [Clients::TYPE_PERSON, Clients::TYPE_PERSON_FOREIGNER])) {
            $type = 'Lien conditions generales inscription preteur particulier';
        } else {
            $type = 'Lien conditions generales inscription preteur societe';
        }

        /** @var Settings $settingsEntity */
        $settingsEntity =  $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => $type]);
        return $settingsEntity->getValue();
    }

    /**
     * @param \clients|Clients $client
     */
    public function acceptLastTos($client)
    {
        if ($client instanceof \clients) {
            $client = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($client->id_client);
        }

        if (false === empty($client)) {
            $termsOfUse = new AcceptationsLegalDocs();
            $termsOfUse->setIdLegalDoc($this->getLastTosId($client));
            $termsOfUse->setIdClient($client->getIdClient());

            $this->entityManager->persist($termsOfUse);
            $this->entityManager->flush($termsOfUse);

            $session = $this->requestStack->getCurrentRequest()->getSession();
            $session->remove(self::SESSION_KEY_TOS_ACCEPTED);
        }
    }

    /**
     * @param \clients | Clients $client
     *
     * @return bool
     */
    public function isLender($client)
    {
        if ($client instanceof Clients) {
            $lenderWallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client->getIdClient(), WalletType::LENDER);
            return null !== $lenderWallet;
        }

        if ($client instanceof \clients) {
            if (empty($client->id_client)) {
                return false;
            }
            return $client->isLender();
        }

        return false;
    }

    /**
     * @param \clients | Clients $client
     *
     * @return bool
     */
    public function isBorrower($client)
    {
        if ($client instanceof Clients) {
            $borrowerWallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client->getIdClient(), WalletType::BORROWER);
            return null !== $borrowerWallet;
        }

        if ($client instanceof \clients) {
            if (empty($client->id_client)) {
                return false;
            }
            return $client->isBorrower();
        }

        return false;
    }

    /**
     * @param \clients $client
     *
     * @return string
     */
    public function getClientInitials(\clients $client)
    {
        $initials = substr($client->prenom, 0, 1) . substr($client->nom, 0, 1);
        //TODO decide which initials to use in case of company

        return $initials;
    }

    /**
     * @param \clients $client
     *
     * @return bool
     */
    public function isActive(\clients $client)
    {
        return (bool) $client->status;
    }

    /**
     * @param \clients $client
     *
     * @return bool
     */
    public function hasAcceptedCurrentTerms(\clients $client)
    {
        return $this->isAcceptedCGV($client, $this->getLastTosId($client));
    }

    /**
     * @param \clients $client
     *
     * @return mixed
     */
    public function getClientSubscriptionStep(\clients $client)
    {
        return $client->etape_inscription_preteur;
    }

    /**
     * @param \clients $client
     *
     * @return bool
     */
    public function isValidated(\clients $client)
    {
        /** @var \clients_status $lastClientStatus */
        $lastClientStatus = $this->entityManagerSimulator->getRepository('clients_status');
        $lastClientStatus->getLastStatut($client->id_client);

        return $lastClientStatus->status == \clients_status::VALIDATED;
    }

    /**
     * @param Request $request
     *
     * @return RedirectResponse|null
     */
    public function checkProgressAndRedirect(Request $request)
    {
        $currentPath = $request->getPathInfo();
        $token       = $this->tokenStorage->getToken();

        if ($token && $token->getUser() instanceof UserLender) {
            $client = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($token->getUser()->getClientId());
            if (
                $client && $this->isLender($client) && $client->getEtapeInscriptionPreteur() < Clients::SUBSCRIPTION_STEP_MONEY_DEPOSIT) {
                $redirectPath = $this->getSubscriptionStepRedirectRoute($client);

                if ($redirectPath != $currentPath) {
                    return new RedirectResponse($redirectPath);
                }
            }
        }

        return null;
    }

    /**
     * @param Clients $client
     * @return string
     */
    public function getSubscriptionStepRedirectRoute(Clients $client)
    {
        switch ($client->getEtapeInscriptionPreteur()) {
            case Clients::SUBSCRIPTION_STEP_PERSONAL_INFORMATION:
                return $this->router->generate('lender_subscription_documents', ['clientHash' => $client->getHash()]);
            case Clients::SUBSCRIPTION_STEP_DOCUMENTS:
            case Clients::SUBSCRIPTION_STEP_MONEY_DEPOSIT:
                return $this->router->generate('lender_subscription_money_deposit', ['clientHash' => $client->getHash()]);
            default:
                return $this->router->generate('projects_list');
        }
    }
}
