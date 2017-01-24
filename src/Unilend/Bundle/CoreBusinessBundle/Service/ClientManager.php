<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\FrontBundle\Security\ClientRole;
use Unilend\Bundle\FrontBundle\Security\User\BaseUser;
use Unilend\Bundle\FrontBundle\Security\User\UserLender;

/**
 * Class ClientManager
 * @package Unilend\Bundle\CoreBusinessBundle\Service
 */
class ClientManager
{
    const SESSION_KEY_TOS_ACCEPTED = 'user_legal_doc_accepted';

    /** @var EntityManager  */
    private $entityManager;
    /** @var ClientSettingsManager */
    private $clientSettingsManager;
    /** @var TokenStorageInterface */
    private $tokenStorage;
    /** @var RequestStack */
    private $requestStack;
    /** @var  RouterInterface */
    private $router;
    /** @var  ClientRole */
    private $clientRole;

    /**
     * ClientManager constructor.
     * @param EntityManager         $entityManager
     * @param ClientSettingsManager $clientSettingsManager
     * @param TokenStorageInterface $tokenStorage
     * @param RequestStack          $requestStack
     * @param RouterInterface       $router
     * @param ClientRole            $clientRole
     */
    public function __construct(
        EntityManager $entityManager,
        ClientSettingsManager $clientSettingsManager,
        TokenStorageInterface $tokenStorage,
        RequestStack $requestStack,
        RouterInterface $router,
        ClientRole $clientRole
    ) {
        $this->entityManager        = $entityManager;
        $this->clientSettingsManager = $clientSettingsManager;
        $this->tokenStorage          = $tokenStorage;
        $this->requestStack          = $requestStack;
        $this->router                = $router;
        $this->clientRole            = $clientRole;
    }


    /**
     * @param \clients $oClient
     *
     * @return bool
     */
    public function isBetaTester(\clients $client)
    {
        return (bool) $this->clientSettingsManager->getSetting($client, \client_setting_type::TYPE_BETA_TESTER);
    }

    /**
     * @param \clients $client
     * @param          $legalDocId
     *
     * @return bool
     */
    public function isAcceptedCGV(\clients $client, $legalDocId)
    {
        /** @var \acceptations_legal_docs $oAcceptationLegalDocs */
        $oAcceptationLegalDocs = $this->entityManager->getRepository('acceptations_legal_docs');
        return $oAcceptationLegalDocs->exist($client->id_client, 'id_legal_doc = ' . $legalDocId . ' AND id_client ');
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
                $client = $this->entityManager->getRepository('clients');

                if ($client->get($user->getClientId()) && false === $user->hasAcceptedCurrentTerms()) {
                    $session->set(self::SESSION_KEY_TOS_ACCEPTED, false);
                }
            }
        }
    }

    public function getLastTosId(\clients $client)
    {
        /** @var \settings $settings */
        $settings = $this->entityManager->getRepository('settings');
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
            $tosAccepted               = $this->entityManager->getRepository('acceptations_legal_docs');
            $tosAccepted->id_client    = $client->id_client;
            $tosAccepted->id_legal_doc = $this->getLastTosId($client);
            $tosAccepted->create();

            $session = $this->requestStack->getCurrentRequest()->getSession();
            $session->remove(self::SESSION_KEY_TOS_ACCEPTED);
        }
    }

    /**
     * @param \clients $client
     *
     * @return bool
     */
    public function isLender(\clients $client)
    {
        if (empty($client->id_client)) {
            return false;
        } else {
            return $client->isLender();
        }
    }

    /**
     * @param \clients $client
     *
     * @return bool
     */
    public function isBorrower(\clients $client)
    {
        if (empty($client->id_client)) {
            return false;
        }
        return $client->isBorrower();
    }

    /**
     * @param \clients $client
     *
     * @return float|int|mixed
     */
    public function getClientBalance(\clients $client)
    {
        /** @var \transactions $transactions */
        $transactions = $this->entityManager->getRepository('transactions');
        $balance      = $transactions->getSolde($client->id_client);

        return $balance;
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
        $lastClientStatus = $this->entityManager->getRepository('clients_status');
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
        /** @var \clients $client */
        $client      = $this->entityManager->getRepository('clients');
        $currentPath = $request->getPathInfo();
        $token       = $this->tokenStorage->getToken();

        if ($token) {
            /** @var BaseUser $user */
            $user = $token->getUser();

            if ($user instanceof UserLender
                && $this->clientRole->isGranted('ROLE_LENDER', $user)
                && $client->get($user->getClientId())
                && empty($user->getClientStatus())
            ) {
                $redirectPath = $this->getSubscriptionStepRedirectRoute($client);

                if ($redirectPath != $currentPath) {
                    return new RedirectResponse($redirectPath);
                }
            }
        }

        return null;
    }

    /**
     * @param \clients $client
     *
     * @return string
     */
    public function getSubscriptionStepRedirectRoute(\clients $client)
    {
        switch ($client->etape_inscription_preteur) {
            case \clients::SUBSCRIPTION_STEP_PERSONAL_INFORMATION:
                return $this->router->generate('lender_subscription_documents', ['clientHash' => $client->hash]);
            case \clients::SUBSCRIPTION_STEP_DOCUMENTS:
            case \clients::SUBSCRIPTION_STEP_MONEY_DEPOSIT:
                return $this->router->generate('lender_subscription_money_deposit', ['clientHash' => $client->hash]);
            default:
                return $this->router->generate('projects_list');
        }
    }
}
