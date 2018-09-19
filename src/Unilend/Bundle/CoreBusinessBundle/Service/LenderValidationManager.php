<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Packages;
use Unilend\Bundle\CoreBusinessBundle\Entity\{BankAccount, ClientAddress, Clients, ClientsStatus, CompanyAddress, Users, WalletType};
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessageProvider;

/**
 * @package Unilend\Bundle\CoreBusinessBundle\Service
 */
class LenderValidationManager
{
    const MIN_LEGAL_AGE                = 18;
    const MAX_AGE_AUTOMATIC_VALIDATION = 80;

    /** @var EntityManager */
    private $entityManager;
    /** @var ClientStatusManager */
    private $clientStatusManager;
    /** @var WelcomeOfferManager */
    private $welcomeOfferManager;
    /** @var SponsorshipManager */
    private $sponsorshipManager;
    /** @var BankAccountManager */
    private $bankAccountManager;
    /** @var AddressManager */
    private $addressManager;
    /** @var TaxManager */
    private $taxManager;
    /** @var \NumberFormatter */
    private $numberFormatter;
    /** @var TemplateMessageProvider */
    private $messageProvider;
    /** @var \Swift_Mailer */
    private $mailer;
    /** @var LoggerInterface */
    private $logger;
    /** @var string */
    private $staticUrl;
    /** @var string */
    private $frontUrl;

    /**
     * @param EntityManager           $entityManager
     * @param ClientStatusManager     $clientStatusManager
     * @param WelcomeOfferManager     $welcomeOfferManager
     * @param SponsorshipManager      $sponsorshipManager
     * @param BankAccountManager      $bankAccountManager
     * @param AddressManager          $addressManager
     * @param TaxManager              $taxManager
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
        BankAccountManager $bankAccountManager,
        AddressManager $addressManager,
        TaxManager $taxManager,
        \NumberFormatter $numberFormatter,
        TemplateMessageProvider $messageProvider,
        \Swift_Mailer $mailer,
        LoggerInterface $logger,
        Packages $assetsPackages,
        string $schema,
        string $frontHost
    )
    {
        $this->entityManager       = $entityManager;
        $this->clientStatusManager = $clientStatusManager;
        $this->welcomeOfferManager = $welcomeOfferManager;
        $this->sponsorshipManager  = $sponsorshipManager;
        $this->bankAccountManager  = $bankAccountManager;
        $this->addressManager      = $addressManager;
        $this->taxManager          = $taxManager;
        $this->numberFormatter     = $numberFormatter;
        $this->messageProvider     = $messageProvider;
        $this->mailer              = $mailer;
        $this->logger              = $logger;
        $this->staticUrl           = $assetsPackages->getUrl('');
        $this->frontUrl            = $schema . '://' . $frontHost;
    }

    /**
     * @param Clients  $client
     * @param Users    $user
     * @param array    $duplicatedAccounts
     * @param int|null $idBankAccount
     * @param int|null $idAddress
     *
     * @return bool
     * @throws \Exception
     */
    public function validateClient(Clients $client, Users $user, array &$duplicatedAccounts = [], ?int $idBankAccount = null, ?int $idAddress = null): bool
    {
        if (null !== $idBankAccount) {
            /** @var BankAccount $currentBankAccount */
            $bankAccount = $this->entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount')->find($idBankAccount);
            if (null === $bankAccount) {
                throw new \InvalidArgumentException('BankAccount could not be found with id: ' . $idBankAccount);
            }
        }

        if (null !== $idAddress) {
            if ($client->isNaturalPerson()) {
                $address = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientAddress')->find($idAddress);
                if (null === $address) {
                    throw new \InvalidArgumentException('ClientAddress could not be found with id: ' . $idAddress);
                }

            } else {
                $address = $this->entityManager->getRepository('UnilendCoreBusinessBundle:CompanyAddress')->find($idAddress);
                if (null === $address) {
                    throw new \InvalidArgumentException('CompanyAddress could not be found with id: ' . $idAddress);
                }
            }
        }

        if ($client->isNaturalPerson()) {
            try {
                $duplicates         = $this->getDuplicatedAccounts($client);
                $duplicatedAccounts = $duplicates;

                if (0 < count($duplicates) && in_array($user->getIdUser(), [Users::USER_ID_FRONT, Users::USER_ID_CRON])) {
                    return false;
                } elseif (0 < count($duplicates)) {
                    $this->closeDuplicatedAccounts($client, $user, $duplicates);
                }
            } catch (DBALException $exception) {
                $this->logger->error('Unable to find lender duplicates. Exception: ' . $exception->getMessage(), [
                    'id_client' => $client->getIdClient(),
                    'file'      => $exception->getFile(),
                    'line'      => $exception->getLine(),
                    'class'     => __CLASS__,
                    'function'  => __FUNCTION__
                ]);

                return false;
            }
        }

        $message = $user->getIdUser() === Users::USER_ID_CRON ? 'Validation automatique basée sur Green Point' : null;

        $this->entityManager->beginTransaction();

        try {
            if (isset($bankAccount) && $bankAccount instanceof BankAccount) {
                $this->bankAccountManager->validate($bankAccount);
            }

            if (isset($address) && ($address instanceof ClientAddress || $address instanceof CompanyAddress)) {
                $this->addressManager->validateLenderAddress($address);
            }

            $this->validateClientDataHistory($client);

            if ($client->isNaturalPerson()) {
                $this->taxManager->applyFiscalCountry($client, $user);
            }

            if ($this->clientStatusManager->hasBeenValidatedAtLeastOnce($client)) {
                $this->clientStatusManager->addClientStatus($client, $user->getIdUser(), ClientsStatus::STATUS_VALIDATED, $message);
                $template = 'preteur-validation-modification-compte';
            } else {
                $this->firstClientValidation($client, $user);
                $template = 'preteur-confirmation-activation';
            }

            $this->entityManager->commit();

            $this->sendClientValidationEmail($client, $template);

            return true;
        } catch (\Exception $exception) {
            $this->entityManager->rollback();
            throw $exception;
        }
    }

    /**
     * @param Clients $client
     *
     * @return array
     * @throws DBALException
     */
    private function getDuplicatedAccounts(Clients $client): array
    {
        $existingClient = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')
            ->getDuplicatesByName($client->getNom(), $client->getPrenom(), $client->getNaissance());
        $existingClient = array_column($existingClient, 'id_client', 'id_client');

        if (isset($existingClient[$client->getIdClient()])) {
            unset($existingClient[$client->getIdClient()]);
        }

        return $existingClient;
    }

    /**
     * @param Clients $client
     * @param Users   $user
     * @param array   $duplicates
     */
    private function closeDuplicatedAccounts(Clients $client, Users $user, array $duplicates): void
    {
        $clientRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients');

        foreach ($duplicates as $idClient) {
            $clientToClose = $clientRepository->find($idClient);
            if ($clientToClose->isLender()) {
                $this->clientStatusManager->addClientStatus($clientToClose, $user->getIdUser(), ClientsStatus::STATUS_CLOSED_BY_UNILEND, 'Doublon avec client ID : ' . $client->getIdClient());
            }
        }
    }

    /**
     * @param Clients $client
     * @param Users   $user
     *
     * @throws \Exception
     */
    private function firstClientValidation(Clients $client, Users $user): void
    {
        $isSponsee                 = $this->sponsorshipManager->isEligibleForSponseeReward($client);
        $isEligibleForWelcomeOffer = $this->welcomeOfferManager->isClientEligibleForWelcomeOffer($client);

        if ($isSponsee) {
            $this->sponsorshipManager->attributeSponseeReward($client);
        }

        if ($isEligibleForWelcomeOffer && false === $isSponsee) {
            $this->welcomeOfferManager->payOutWelcomeOffer($client);
        }

        $this->clientStatusManager->addClientStatus($client, $user->getIdUser(), ClientsStatus::STATUS_VALIDATED);
    }

    /**
     * @param Clients $client
     * @param string  $mailType
     */
    private function sendClientValidationEmail(Clients $client, string $mailType): void
    {
        $wallet   = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);
        $keywords = [
            'firstName'     => $client->getPrenom(),
            'lenderPattern' => $wallet->getWireTransferPattern()
        ];

        $message = $this->messageProvider->newMessage($mailType, $keywords);

        try {
            $message->setTo($client->getEmail());
            $this->mailer->send($message);
        } catch (\Exception $exception) {
            $this->logger->warning('Could not send email: ' . $mailType . ' - Exception: ' . $exception->getMessage(), [
                'id_mail_template' => $message->getTemplateId(),
                'id_client'        => $client->getIdClient(),
                'class'            => __CLASS__,
                'function'         => __FUNCTION__
            ]);
        }
    }

    /**
     * @param Clients $client
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function validateClientDataHistory(Clients $client): void
    {
        $clientDataHistory = $this->entityManager
            ->getRepository('UnilendCoreBusinessBundle:ClientDataHistory')
            ->findLastModifiedDataToValidate($client);

        foreach ($clientDataHistory as $history) {
            $history->setDateValidated(new \DateTime());
        }

        $this->entityManager->flush();
    }

    /**
     * @param \DateTime $birthday
     *
     * @return bool
     */
    public function validateAge(\DateTime $birthday): bool
    {
        $yesterday = new \DateTime('today midnight');

        $birthday->setTime(0, 0);

        $interval = $birthday->diff($yesterday);

        if ($interval->y >= self::MIN_LEGAL_AGE) {
            return true;
        }

        return false;
    }
}
