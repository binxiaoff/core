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
        $welcomeOfferHomepage = $this->entityManager->getRepository('UnilendCoreBusinessBundle:OffresBienvenues')->findBy([
            'status'  => OffresBienvenues::STATUS_ONLINE,
            'display' => OffresBienvenues::DISPLAY_HOME
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
        $welcomeOfferLandingPage = $this->entityManager->getRepository('UnilendCoreBusinessBundle:OffresBienvenues')->findBy([
            'status'  => OffresBienvenues::STATUS_ONLINE,
            'display' => OffresBienvenues::DISPLAY_LANDING_PAGE
        ]);

        if (null === $welcomeOfferLandingPage) {
            return false;
        }

        return true;
    }

    /**
     * @param \clients|Clients $client
     *
     * @return array
     * @throws \Exception
     */
    public function payOutWelcomeOffer($client)
    {
        if ($client instanceof \clients) {
            /** @var Wallet $wallet */
            $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client->id_client, WalletType::LENDER);
        }

        if ($client instanceof Clients) {
            /** @var Wallet $wallet */
            $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);
        }

        if (false === $wallet->getIdClient()->isLender()) {
            throw new \Exception('Client ' . $wallet->getIdClient()->getIdClient() . ' is not a Lender');
        }


        $offerDisplay = $this->getWelcomeOfferForClient($client);
        $welcomeOffer = $this->entityManager->getRepository('UnilendCoreBusinessBundle:OffresBienvenues')->findOneBy(['status' => OffresBienvenues::STATUS_ONLINE, 'display' => $offerDisplay]);
        //TODO ask julien si on distribue l'offre qui etaits en cours aund le client s'est inscrit? et pas quand il est validé

        $offerIsValid    = null !== $welcomeOffer;
        $enoughMoneyLeft = false;

        if ($offerIsValid) {
            $unilendPromotionalWalletType = $this->entityManager->getRepository('UnilendCoreBusinessBundle:WalletType')->findOneBy(['label' => WalletType::UNILEND_PROMOTIONAL_OPERATION]);
            $unilendPromotionalWallet     = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->findOneBy(['idType' => $unilendPromotionalWalletType]);
            $alreadyPaidOutAllOffers      = $this->entityManager->getRepository('UnilendCoreBusinessBundle:OffresBienvenuesDetails')->getSumPaidOutForOffer();
            $sumAvailableOffers           = $unilendPromotionalWallet->getAvailableBalance();
            $enoughMoneyLeft              = $alreadyPaidOutAllOffers + $welcomeOffer->getMontant() <= $welcomeOffer->getMontantLimit() && $sumAvailableOffers >= $welcomeOffer->getMontant();

            if ($enoughMoneyLeft) {
                $paidOutWelcomeOffer = new OffresBienvenuesDetails();
                $paidOutWelcomeOffer->setIdOffreBienvenue($welcomeOffer->getIdOffreBienvenue());
                $paidOutWelcomeOffer->setIdClient($client->getIdClient());
                $paidOutWelcomeOffer->setMontant($welcomeOffer->getMontant());
                $paidOutWelcomeOffer->setStatus(OffresBienvenuesDetails::STATUS_NEW);

                $this->entityManager->persist($paidOutWelcomeOffer);
                $this->entityManager->flush($paidOutWelcomeOffer);

                $this->operationManager->newWelcomeOffer($wallet, $paidOutWelcomeOffer);

                $this->sendWelcomeOfferEmail($client, $welcomeOffer);

                return ['code' => 0, 'message' => 'Offre de bienvenue créée.'];
            }
        }

        if (false === $offerIsValid) {
            return ['code' => 1, 'message' => "Il n'y a pas d'offre de bienvenue correspondant à l'origine du client en cours."];
        }

        if (false === $enoughMoneyLeft) {
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
    public function clientIsEligibleToWelcomeOffer($client)
    {
        if ($client instanceof \clients) {
            $client = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($client->id_client);
        }

        $hasOrigin            = in_array($client->getOrigine(), [Clients::ORIGIN_WELCOME_OFFER_HOME, Clients::ORIGIN_WELCOME_OFFER_LP]);
        $noPreviousValidation = (null === $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientsStatusHistory')->getFirstClientValidation($client));

        return ($hasOrigin && $noPreviousValidation);
    }

    /**
     * @param Clients $client
     *
     * @return bool|string
     */
    public function getWelcomeOfferForClient(Clients $client)
    {
        switch ($client->getOrigine()) {
            case Clients::ORIGIN_WELCOME_OFFER:
                return 'Offre de bienvenue';
            case Clients::ORIGIN_WELCOME_OFFER_HOME:
                return OffresBienvenues::DISPLAY_HOME;
            case Clients::ORIGIN_WELCOME_OFFER_LP:
                return OffresBienvenues::DISPLAY_LANDING_PAGE;
            default:
                return OffresBienvenues::DISPLAY_HOME;
        }
    }

    /**
     * @param Clients $client
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
            'lien_fb'         => $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Facebook']),
            'lien_tw'         => $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Twitter']),
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
