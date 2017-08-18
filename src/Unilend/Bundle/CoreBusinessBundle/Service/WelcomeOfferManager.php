<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Packages;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\OffresBienvenues;
use Unilend\Bundle\CoreBusinessBundle\Entity\OffresBienvenuesDetails;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage;
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessageProvider;

class WelcomeOfferManager
{
    /** @var OperationManager  */
    private $operationManager;
    /** @var EntityManager  */
    private $entityManager;
    /** @var  \NumberFormatter */
    private $numberFormatter;
    /** @var TemplateMessageProvider */
    private $messageProvider;
    /** @var \Swift_Mailer */
    private $mailer;
    /** @var LoggerInterface */
    private $logger;
    private $surl;
    private $furl;

    public function __construct(
        OperationManager $operationManager,
        EntityManager $entityManager,
        \NumberFormatter $numberFormatter,
        TemplateMessageProvider $messageProvider,
        \Swift_Mailer $mailer,
        LoggerInterface $logger,
        Packages $assetsPackages,
        $schema,
        $frontHost
    )
    {
        $this->operationManager = $operationManager;
        $this->entityManager    = $entityManager;
        $this->numberFormatter  = $numberFormatter;
        $this->messageProvider  = $messageProvider;
        $this->mailer           = $mailer;
        $this->logger           = $logger;
        $this->surl             = $assetsPackages->getUrl('');
        $this->furl             = $schema . '://' . $frontHost;
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

        $offerType       = $this->getWelcomeOfferTypeForClient($client);
        $welcomeOffer    = $this->entityManager->getRepository('UnilendCoreBusinessBundle:OffresBienvenues')->getWelcomeOfferForClient($client, $offerType);
        $offerIsValid    = null !== $welcomeOffer;
        $enoughMoneyLeft = false;

        if ($offerIsValid) {
            $unilendPromotionalWalletType = $this->entityManager->getRepository('UnilendCoreBusinessBundle:WalletType')->findOneBy(['label' => WalletType::UNILEND_PROMOTIONAL_OPERATION]);
            $unilendPromotionalWallet     = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->findOneBy(['idType' => $unilendPromotionalWalletType]);

            $alreadyPaidOutOffer = $this->entityManager->getRepository('UnilendCoreBusinessBundle:OffresBienvenuesDetails')->getSumPaidOutForOffer($welcomeOffer);
            $enoughMoneyLeft     = $alreadyPaidOutOffer + $welcomeOffer->getMontant() <= $welcomeOffer->getMontantLimit() && $unilendPromotionalWallet->getAvailableBalance() >= $welcomeOffer->getMontant();

            if ($enoughMoneyLeft) {
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

        if (false === $offerIsValid) {
            $this->logger->info('Client ID: ' . $client->getIdClient() . ' Welcome offer not paid out. There is no welcome offer corresponding to client\'s origin', [ 'class'     => __CLASS__, 'function'  => __FUNCTION__, 'id_lender' => $client->getIdClient()]);
            return ['code' => 1, 'message' => "Il n'y a pas d'offre de bienvenue correspondant à l'origine du client."];
        }

        if (false === $enoughMoneyLeft) {
            $this->logger->info('Client ID: ' . $client->getIdClient() . ' Welcome offer not paid out. There is not enough money left', [ 'class'     => __CLASS__, 'function'  => __FUNCTION__, 'id_lender' => $client->getIdClient()]);
            return ['code' => 2, 'message' => "Il n'y a plus assez d'argent disponible pour créer l'offre de bienvenue."];
        }
    }

    /**
     * @param Clients $client
     *
     * @return float
     */
    public function getCurrentWelcomeOfferAmount(Clients $client)
    {
        $welcomeOffers = $this->entityManager->getRepository('UnilendCoreBusinessBundle:OffresBienvenuesDetails')->findBy([
            'idClient' => $client->getIdClient(),
            'status'   => OffresBienvenuesDetails::STATUS_NEW
        ]);

        $promotionalAmountTotal = 0;
        foreach ($welcomeOffers as $offer) {
            $offerAmount            = round(bcdiv($offer->getMontant(), 100, 4), 2);
            $promotionalAmountTotal = bcadd($promotionalAmountTotal, $offerAmount, 2);
        }

        return (float) $promotionalAmountTotal;
    }

    /**
     * @param Clients|\clients $client
     *
     * @return bool
     */
    public function clientIsEligibleForWelcomeOffer($client)
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
                } else {
                    return OffresBienvenues::TYPE_HOME;
                }
        }
    }

    /**
     * @param Clients          $client
     * @param OffresBienvenues $welcomeOffer
     */
    public function sendWelcomeOfferEmail(Clients $client, OffresBienvenues $welcomeOffer)
    {
        $varMail = [
            'surl'            => $this->surl,
            'url'             => $this->furl,
            'prenom_p'        => $client->getPrenom(),
            'projets'         => $this->furl . '/projets-a-financer',
            'offre_bienvenue' => $this->numberFormatter->format($welcomeOffer->getMontant() / 100),
            'lien_fb'         => $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Facebook'])->getValue(),
            'lien_tw'         => $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Twitter'])->getValue(),
        ];

        /** @var TemplateMessage $message */
        $message = $this->messageProvider->newMessage('offre-de-bienvenue', $varMail);
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
}
