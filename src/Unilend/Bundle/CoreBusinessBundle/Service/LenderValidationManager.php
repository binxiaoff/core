<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Packages;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
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
    private $surl;
    private $furl;

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
     * @param                         $schema
     * @param                         $frontHost
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
        $this->surl                = $assetsPackages->getUrl('');
        $this->furl                = $schema . '://' . $frontHost;
    }

    /**
     * @param Clients|\clients $client
     * @param Users            $user
     *
     * @return bool
     */
    public function validateClient($client, Users $user)
    {
        if ($client instanceof \clients) {
            $client = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($client->id_client);
        }

        $message = $user->getIdUser() == Users::USER_ID_CRON ? 'Validation automatique basée sur Green Point': '';

        if ($client->isNaturalPerson()) {
            $duplicateClient = $this->checkLenderUniqueness($client, $user);
            if (true  !== $duplicateClient) {
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
        $isEligibleForWelcomeOffer = $this->welcomeOfferManager->clientIsEligibleForWelcomeOffer($client);

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
        $varMail = [
            'surl'    => $this->surl,
            'url'     => $this->furl,
            'prenom'  => $client->getPrenom(),
            'projets' => $this->furl . '/projets-a-financer',
            'lien_fb' => $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Facebook'])->getValue(),
            'lien_tw' => $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Twitter'])->getValue(),
        ];

        /** @var TemplateMessage $message */
        $message = $this->messageProvider->newMessage($mailType, $varMail);
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
     * @param Clients|\clients $client
     *
     * @return int|bool
     */
    private function checkLenderUniqueness($client, Users $user)
    {
        if ($client instanceof \clients) {
            $client = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($client->id_client);
        }

        $clientRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients');
        $existingClient   = $clientRepository->getDuplicates($client->getNom(), $client->getPrenom(), $client->getNaissance());
        $existingClient   = array_shift($existingClient);

        if (false === empty($existingClient) && $existingClient['id_client'] != $client->getIdClient()) {
            $this->clientStatusManager->addClientStatus($client, $user->getIdUser(), \clients_status::CLOSED_BY_UNILEND, 'Doublon avec client ID : ' . $existingClient['id_client']);

            return $existingClient['id_client'];
        }

        return true;
    }
}
