<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\{
    RedirectResponse, Request
};
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    Clients, WalletType
};
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
use Unilend\Bundle\FrontBundle\Security\User\UserLender;

class ClientManager
{
    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;
    /** @var EntityManager */
    private $entityManager;
    /** @var ClientSettingsManager */
    private $clientSettingsManager;
    /** @var TermsOfSaleManager */
    private $termsOfSaleManager;
    /** @var TokenStorageInterface */
    private $tokenStorage;
    /** @var RouterInterface */
    private $router;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param EntityManagerSimulator $entityManagerSimulator
     * @param EntityManager          $entityManager
     * @param ClientSettingsManager  $clientSettingsManager
     * @param TermsOfSaleManager     $termsOfSaleManager
     * @param TokenStorageInterface  $tokenStorage
     * @param RouterInterface        $router
     * @param LoggerInterface        $logger
     */
    public function __construct(
        EntityManagerSimulator $entityManagerSimulator,
        EntityManager $entityManager,
        ClientSettingsManager $clientSettingsManager,
        TermsOfSaleManager $termsOfSaleManager,
        TokenStorageInterface $tokenStorage,
        RouterInterface $router,
        LoggerInterface $logger
    )
    {
        $this->entityManagerSimulator = $entityManagerSimulator;
        $this->entityManager          = $entityManager;
        $this->clientSettingsManager  = $clientSettingsManager;
        $this->termsOfSaleManager     = $termsOfSaleManager;
        $this->tokenStorage           = $tokenStorage;
        $this->router                 = $router;
        $this->logger                 = $logger;
    }

    /**
     * @param Clients $client
     *
     * @return bool
     */
    public function isBetaTester(Clients $client): bool
    {
        try {
            return (bool) $this->clientSettingsManager->getSetting($client, \client_setting_type::TYPE_BETA_TESTER);
        } catch (InvalidArgumentException $exception) {
            $this->logger->warning(
                'Invalid argument exception while retrieving beta tester status: ' . $exception->getMessage(),
                ['id_client' => $client->getIdClient(), 'file' => $exception->getFile(), 'line' => $exception->getLine()]
            );
            return false;
        }
    }

    /**
     * @param \clients|Clients $client
     *
     * @return bool
     */
    public function isLender($client): bool
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
     * @param \clients|Clients $client
     *
     * @return bool
     */
    public function isBorrower($client): bool
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
     * @param Clients $client
     *
     * @return bool
     */
    public function isPartner(Clients $client): bool
    {
        $partnerWallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client->getIdClient(), WalletType::PARTNER);
        return null !== $partnerWallet;
    }

    /**
     * @param Clients $client
     *
     * @return string
     */
    public function getInitials(Clients $client): string
    {
        $initials = substr($client->getPrenom(), 0, 1) . substr($client->getNom(), 0, 1);
        //TODO decide which initials to use in case of company

        return $initials;
    }

    /**
     * @param Clients $client
     *
     * @return bool
     */
    public function isActive(Clients $client): bool
    {
        return Clients::STATUS_ONLINE === $client->getStatus();
    }

    /**
     * @param Request $request
     *
     * @return RedirectResponse|null
     */
    public function checkProgressAndRedirect(Request $request): ?RedirectResponse
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
     *
     * @return string
     */
    public function getSubscriptionStepRedirectRoute(Clients $client): string
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
