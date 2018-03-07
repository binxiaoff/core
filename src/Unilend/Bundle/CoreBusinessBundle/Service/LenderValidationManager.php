<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Packages;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage;
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessageProvider;

/**
 * Class LenderValidationManager
 * @package Unilend\Bundle\CoreBusinessBundle\Service
 */
class LenderValidationManager
{
    /** @var EntityManager */
    private $entityManager;
    /** @var ClientStatusManager */
    private $clientStatusManager;
    /** @var WelcomeOfferManager */
    private $welcomeOfferManager;
    /** @var SponsorshipManager */
    private $sponsorshipManager;
    /** @var \NumberFormatter */
    private $numberFormatter;
    /** @var TemplateMessageProvider */
    private $messageProvider;
    /** @var \Swift_Mailer */
    private $mailer;
    /** @var LoggerInterface */
    private $logger;
    private $staticUrl;
    private $frontUrl;

    /**
     * @param EntityManager           $entityManager
     * @param ClientStatusManager     $clientStatusManager
     * @param WelcomeOfferManager     $welcomeOfferManager
     * @param SponsorshipManager      $sponsorshipManager
     * @param \NumberFormatter        $numberFormatter
     * @param TemplateMessageProvider $messageProvider
     * @param \Swift_Mailer           $mailer
     * @param LoggerInterface         $logger
     * @param Packages                $assetsPackages
     * @param string                  $schema
     * @param string                  $frontHost
     */
    public function __construct(
        EntityManager $entityManager,
        ClientStatusManager $clientStatusManager,
        WelcomeOfferManager $welcomeOfferManager,
        SponsorshipManager $sponsorshipManager,
        \NumberFormatter $numberFormatter,
        TemplateMessageProvider $messageProvider,
        \Swift_Mailer $mailer,
        LoggerInterface $logger,
        Packages $assetsPackages,
        $schema,
        $frontHost
    )
    {
        $this->entityManager       = $entityManager;
        $this->clientStatusManager = $clientStatusManager;
        $this->welcomeOfferManager = $welcomeOfferManager;
        $this->sponsorshipManager  = $sponsorshipManager;
        $this->numberFormatter     = $numberFormatter;
        $this->messageProvider     = $messageProvider;
        $this->mailer              = $mailer;
        $this->logger              = $logger;
        $this->staticUrl           = $assetsPackages->getUrl('');
        $this->frontUrl            = $schema . '://' . $frontHost;
    }

    /**
     * @param Clients|\clients $client
     * @param Users            $user
     *
     * @return bool|int
     */
    public function validateClient($client, Users $user)
    {
        if ($client instanceof \clients) {
            $client = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($client->id_client);
        }

        $message = $user->getIdUser() == Users::USER_ID_CRON ? 'Validation automatique basée sur Green Point': '';

        if ($client->isNaturalPerson()) {
            $duplicateClient = $this->checkLenderUniqueness($client, $user);
            if (true !== $duplicateClient) {
                return $duplicateClient;
            }
        }

        if ($this->clientStatusManager->hasBeenValidatedAtLeastOnce($client)) {
            $this->clientStatusManager->addClientStatus($client, $user->getIdUser(), ClientsStatus::VALIDATED, $message);
            $this->sendClientValidationEmail($client, 'preteur-validation-modification-compte');
        } else {
            $this->firstClientValidation($client, $user);
            $this->sendClientValidationEmail($client, 'preteur-confirmation-activation');
        }

        return true;
    }

    /**
     * @param Clients $client
     * @param Users   $user
     */
    public function firstClientValidation(Clients $client, Users $user)
    {
        $isSponsee                 = $this->sponsorshipManager->isEligibleForSponseeReward($client);
        $isEligibleForWelcomeOffer = $this->welcomeOfferManager->isClientEligibleForWelcomeOffer($client);

        if ($isSponsee) {
            $this->sponsorshipManager->attributeSponseeReward($client);
        }

        if ($isEligibleForWelcomeOffer && false === $isSponsee) {
            $this->welcomeOfferManager->payOutWelcomeOffer($client);
        }

        $this->clientStatusManager->addClientStatus($client, $user->getIdUser(), ClientsStatus::VALIDATED);
    }

    /**
     * @param Clients $client
     * @param string  $mailType
     */
    public function sendClientValidationEmail(Clients $client, $mailType)
    {
        $wallet   = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);
        $keywords = [
            'firstName'     => $client->getPrenom(),
            'lenderPattern' => $wallet->getWireTransferPattern()
        ];

        /** @var TemplateMessage $message */
        $message = $this->messageProvider->newMessage($mailType, $keywords);
        try {
            $message->setTo($client->getEmail());
            $this->mailer->send($message);
        } catch (\Exception $exception){
            $this->logger->warning(
                'Could not send email: ' . $mailType . ' - Exception: ' . $exception->getMessage(),
                ['id_mail_template' => $message->getTemplateId(), 'id_client' => $client->getIdClient(), 'class' => __CLASS__, 'function' => __FUNCTION__]
            );
        }
    }

    /**
     * @param Clients $client
     * @param Users  $user
     *
     * @return int|bool
     */
    private function checkLenderUniqueness(Clients $client, Users $user)
    {
        $clientRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients');
        $existingClient   = $clientRepository->getDuplicatesByName($client->getNom(), $client->getPrenom(), $client->getNaissance());
        $existingClient   = array_shift($existingClient);

        if (false === empty($existingClient) && $existingClient['id_client'] != $client->getIdClient()) {
            $this->clientStatusManager->addClientStatus($client, $user->getIdUser(), ClientsStatus::CLOSED_BY_UNILEND, 'Doublon avec client ID : ' . $existingClient['id_client']);

            return $existingClient['id_client'];
        }

        return true;
    }
}
