<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\OffresBienvenues;
use Unilend\Bundle\CoreBusinessBundle\Entity\OffresBienvenuesDetails;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationSubType;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage;
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessageProvider;

class WelcomeOfferManager
{
    /** @var OperationManager  */
    private $operationManager;
    /** @var EntityManagerInterface  */
    private $entityManager;
    /** @var \NumberFormatter */
    private $numberFormatter;
    /** @var TemplateMessageProvider */
    private $messageProvider;
    /** @var \Swift_Mailer */
    private $mailer;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param OperationManager        $operationManager
     * @param EntityManagerInterface  $entityManager
     * @param \NumberFormatter        $numberFormatter
     * @param TemplateMessageProvider $messageProvider
     * @param \Swift_Mailer           $mailer
     * @param LoggerInterface         $logger
     */
    public function __construct(
        OperationManager $operationManager,
        EntityManagerInterface $entityManager,
        \NumberFormatter $numberFormatter,
        TemplateMessageProvider $messageProvider,
        \Swift_Mailer $mailer,
        LoggerInterface $logger
    )
    {
        $this->operationManager = $operationManager;
        $this->entityManager    = $entityManager;
        $this->numberFormatter  = $numberFormatter;
        $this->messageProvider  = $messageProvider;
        $this->mailer           = $mailer;
        $this->logger           = $logger;
    }

    /**
     * @return bool
     */
    public function displayOfferOnHome()
    {
        $welcomeOfferHomepage = $this->entityManager->getRepository('UnilendCoreBusinessBundle:OffresBienvenues')->findOneBy([
            'status' => OffresBienvenues::STATUS_ONLINE,
            'type'   => OffresBienvenues::TYPE_HOME
        ]);

        if (null === $welcomeOfferHomepage) {
            return false;
        }

        return true;
    }

    /**
     * @param string $type
     *
     * @return int
     */
    public function getWelcomeOfferAmount($type)
    {
        $welcomeOffer = $this->entityManager->getRepository('UnilendCoreBusinessBundle:OffresBienvenues')->findOneBy([
            'status' => OffresBienvenues::STATUS_ONLINE,
            'type'   => $type
        ]);

        return null !== $welcomeOffer ? $welcomeOffer->getMontant() / 100 : 0;
    }

    /**
     * @return bool
     */
    public function displayOfferOnLandingPage()
    {
        $welcomeOfferLandingPage = $this->entityManager->getRepository('UnilendCoreBusinessBundle:OffresBienvenues')->findOneBy([
            'status' => OffresBienvenues::STATUS_ONLINE,
            'type'   => OffresBienvenues::TYPE_LANDING_PAGE
        ]);

        if (null === $welcomeOfferLandingPage) {
            return false;
        }

        return true;
    }

    /**
     * @param Clients|\clients $client
     *
     * @return array
     * @throws \Exception
     */
    public function payOutWelcomeOffer($client)
    {
        if ($client instanceof \clients) {
            /** @var Wallet $wallet */
            $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client->id_client, WalletType::LENDER);
            $client = $wallet->getIdClient();
        }

        if ($client instanceof Clients) {
            /** @var Wallet $wallet */
            $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);
        }

        if (false === $client->isLender()) {
            throw new \Exception('Client ' . $client->getIdClient() . ' is not a Lender');
        }

        $offerType                = $this->getWelcomeOfferTypeForClient($client);
        $welcomeOffer             = $this->entityManager->getRepository('UnilendCoreBusinessBundle:OffresBienvenues')->getWelcomeOfferForClient($client, $offerType);
        $isOfferValid             = null !== $welcomeOffer;
        $hasEnoughMoneyLeft       = false;
        $alreadyReceivedPromotion = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Operation')->sumCreditOperationsByTypeUntil($wallet, [OperationType::UNILEND_PROMOTIONAL_OPERATION]);

        if ($isOfferValid && 0 == $alreadyReceivedPromotion) {
            $unilendPromotionalWalletType = $this->entityManager->getRepository('UnilendCoreBusinessBundle:WalletType')->findOneBy(['label' => WalletType::UNILEND_PROMOTIONAL_OPERATION]);
            $unilendPromotionalWallet     = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->findOneBy(['idType' => $unilendPromotionalWalletType]);

            $alreadyPaidOutOffer = $this->entityManager->getRepository('UnilendCoreBusinessBundle:OffresBienvenuesDetails')->getSumPaidOutForOffer($welcomeOffer);
            $hasEnoughMoneyLeft  = bcadd($alreadyPaidOutOffer, bcdiv($welcomeOffer->getMontant(),100, 2), 2) <= bcdiv($welcomeOffer->getMontantLimit(), 100, 2)
                && $unilendPromotionalWallet->getAvailableBalance() >= bcdiv($welcomeOffer->getMontant(), 100, 2);

            if ($hasEnoughMoneyLeft) {
                $paidOutWelcomeOffer = new OffresBienvenuesDetails();
                $paidOutWelcomeOffer->setIdOffreBienvenue($welcomeOffer->getIdOffreBienvenue())
                    ->setIdClient($client->getIdClient())
                    ->setMontant($welcomeOffer->getMontant())
                    ->setStatus(OffresBienvenuesDetails::STATUS_NEW)
                    ->setType(OffresBienvenuesDetails::TYPE_OFFER);

                $this->entityManager->persist($paidOutWelcomeOffer);
                $this->entityManager->flush($paidOutWelcomeOffer);

                $this->operationManager->newWelcomeOffer($wallet, $paidOutWelcomeOffer);

                $this->sendWelcomeOfferEmail($client, $welcomeOffer);

                $this->logger->info('Client ID: ' . $client->getIdClient() . ' Welcome offer paid. ', ['class'     => __CLASS__, 'function'  => __FUNCTION__, 'id_lender' => $client->getIdClient()]);
                return ['code' => 0, 'message' => 'Offre de bienvenue créée.'];
            }
        }

        if (false === $isOfferValid) {
            $this->logger->info('Client ID: ' . $client->getIdClient() . ' Welcome offer not paid out. There is no welcome offer corresponding to client\'s origin', [ 'class'     => __CLASS__, 'function'  => __FUNCTION__, 'id_lender' => $client->getIdClient()]);
            return ['code' => 1, 'message' => "Il n'y a pas d'offre de bienvenue correspondant à l'origine du client."];
        }

        if (0 < $alreadyReceivedPromotion) {
            $this->logger->info('Client ID: ' . $client->getIdClient() . ' Welcome offer not paid out. The client has already received a promotional operation', [ 'class'     => __CLASS__, 'function'  => __FUNCTION__, 'id_lender' => $client->getIdClient()]);
            return ['code' => 3, 'message' => "Le client a déjà reçu une offre commerciale. "];
        }

        if (false === $hasEnoughMoneyLeft) {
            $this->logger->info('Client ID: ' . $client->getIdClient() . ' Welcome offer not paid out. There is not enough money left', [ 'class'     => __CLASS__, 'function'  => __FUNCTION__, 'id_lender' => $client->getIdClient()]);
            return ['code' => 2, 'message' => "Il n'y a plus assez d'argent disponible pour créer l'offre de bienvenue."];
        }
    }

    /**
     * @param Clients|\clients $client
     *
     * @return bool
     */
    public function isClientEligibleForWelcomeOffer($client)
    {
        if ($client instanceof \clients) {
            $client = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($client->id_client);
        }

        $hasOrigin            = in_array($client->getOrigine(), [Clients::ORIGIN_WELCOME_OFFER_HOME, Clients::ORIGIN_WELCOME_OFFER_LP, Clients::ORIGIN_WELCOME_OFFER]);
        $noPreviousValidation = (null === $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientsStatusHistory')->getFirstClientValidation($client));

        return ($hasOrigin && $noPreviousValidation);
    }

    /**
     * @param Clients $client
     *
     * @return bool|string
     */
    public function getWelcomeOfferTypeForClient(Clients $client)
    {
        switch ($client->getOrigine()) {
            case Clients::ORIGIN_WELCOME_OFFER:
                return 'Offre de bienvenue';
            case Clients::ORIGIN_WELCOME_OFFER_HOME:
                return OffresBienvenues::TYPE_HOME;
            case Clients::ORIGIN_WELCOME_OFFER_LP:
                return OffresBienvenues::TYPE_LANDING_PAGE;
            default:
                $legacyWelcomeOffer = $this->entityManager->getRepository('UnilendCoreBusinessBundle:OffresBienvenues')->findOneBy(['type' => 'Offre de bienvenue']);
                if ($client->getAdded() < $legacyWelcomeOffer->getFin()) {
                    return 'Offre de bienvenue';
                }
                return OffresBienvenues::TYPE_HOME;
        }
    }

    /**
     * @param Clients          $client
     * @param OffresBienvenues $welcomeOffer
     */
    public function sendWelcomeOfferEmail(Clients $client, OffresBienvenues $welcomeOffer)
    {
        $wallet   = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);
        $keyWords = [
            'firstName'          => $client->getPrenom(),
            'welcomeOfferAmount' => $this->numberFormatter->format($welcomeOffer->getMontant() / 100),
            'validityInMonth'    => $this->getWelcomeOfferValidityInMonth(),
            'lenderPattern'      => $wallet->getWireTransferPattern()
        ];

        /** @var TemplateMessage $message */
        $message = $this->messageProvider->newMessage('offre-de-bienvenue', $keyWords);
        try {
            $message->setTo($client->getEmail());
            $this->mailer->send($message);
        } catch (\Exception $exception) {
            $this->logger->warning('Could not send email: offre-de-bienvenue - Exception: ' . $exception->getMessage(), [
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
     * @return bool
     * @throws \Exception
     */
    public function clientHasReceivedWelcomeOffer(Clients $client)
    {
        $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);
        if (null === $wallet) {
            throw new \Exception('Client has no lender wallet');
        }

        $receivedWelcomeOffer = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Operation')
            ->sumCreditOperationsByTypeUntil($wallet, [OperationType::UNILEND_PROMOTIONAL_OPERATION], [OperationSubType::UNILEND_PROMOTIONAL_OPERATION_WELCOME_OFFER]);

        if (null === $receivedWelcomeOffer) {
             return false;
        }

        return true;
    }

    /**
     * Returns true even when there is no welcome offer for client
     * @param Clients $client
     *
     * @return bool
     * @throws \Exception
     */
    public function getUnusedWelcomeOfferAmount(Clients $client)
    {
        $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);
        if (null === $wallet) {
            throw new \Exception('Client has no lender wallet');
        }

        $operationRepository   = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Operation');
        $welcomeOffer          = $operationRepository->sumCreditOperationsByTypeUntil($wallet, [OperationType::UNILEND_PROMOTIONAL_OPERATION], [OperationSubType::UNILEND_PROMOTIONAL_OPERATION_WELCOME_OFFER]);
        $cancelledWelcomeOffer = $operationRepository->sumDebitOperationsByTypeUntil($wallet, [OperationType::UNILEND_PROMOTIONAL_OPERATION_CANCEL], [OperationSubType::UNILEND_PROMOTIONAL_OPERATION_CANCEL_WELCOME_OFFER]);
        $totalWelcomeOffer     = bcsub($welcomeOffer, $cancelledWelcomeOffer, 4);
        $loans                 = $operationRepository->sumDebitOperationsByTypeUntil($wallet, [OperationType::LENDER_LOAN]);

        $unusedAmount = round(bcsub($totalWelcomeOffer, $loans, 4), 2);
        if ($unusedAmount <= 0 ) {
            return 0;
        }

        return $unusedAmount;
    }

    /**
     * @return float
     */
    private function getWelcomeOfferValidityInMonth()
    {
        $validitySetting = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Durée validité Offre de bienvenue']);

        return round($validitySetting->getValue() / 30, PHP_ROUND_HALF_DOWN);
    }
}
