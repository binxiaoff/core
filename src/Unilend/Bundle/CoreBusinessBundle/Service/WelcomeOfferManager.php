<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\OffresBienvenues;
use Unilend\Bundle\CoreBusinessBundle\Entity\OffresBienvenuesDetails;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
use Unilend\Bundle\FrontBundle\Service\SourceManager;

class WelcomeOfferManager
{
    /** @var EntityManagerSimulator  */
    private $entityManagerSimulator;
    /** @var MailerManager  */
    private $mailerManager;
    /** @var OperationManager  */
    private $operationManager;
    /** @var EntityManager  */
    private $entityManager;

    public function __construct(EntityManagerSimulator $entityManagerSimulator, MailerManager $mailerManager, EntityManager $entityManager, OperationManager $operationManager)
    {
        $this->entityManagerSimulator = $entityManagerSimulator;
        $this->mailerManager          = $mailerManager;
        $this->operationManager       = $operationManager;
        $this->entityManager          = $entityManager;
    }

    public function displayOfferOnHome()
    {
        /** @var \settings $settings */
        $settings = $this->entityManagerSimulator->getRepository('settings');
        $settings->get('offre-de-bienvenue-sur-home', 'type');
        return (bool) $settings->value;
    }

    /**
     * @param \clients $client
     *
     * @return array
     * @throws \Exception
     */
    public function createWelcomeOffer(\clients $client)
    {
        /** @var Wallet $wallet */
        $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client->id_client, WalletType::LENDER);
        if (false === $wallet->getIdClient()->isLender()) {
            throw new \Exception('Client ' . $wallet->getIdClient()->getIdClient() . ' is not a Lender');
        }

        /** @var \offres_bienvenues $welcomeOffer */
        $welcomeOffer = $this->entityManagerSimulator->getRepository('offres_bienvenues');

        $offerIsValid                    = false;
        $enoughMoneyLeft                 = false;
        $return                          = [];

        if ($welcomeOffer->get(OffresBienvenues::STATUS_ONLINE, 'status')) {
            /** @var \offres_bienvenues_details $welcomeOfferDetail */
            $welcomeOfferDetail = $this->entityManagerSimulator->getRepository('offres_bienvenues_details');
            /** @var \settings $setting */
            $setting = $this->entityManagerSimulator->getRepository('settings');

            $unilendPromotionalWalletType = $this->entityManager->getRepository('UnilendCoreBusinessBundle:WalletType')->findOneBy(['label' => WalletType::UNILEND_PROMOTIONAL_OPERATION]);
            $unilendPromotionalWallet     = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->findOneBy(['idType' => $unilendPromotionalWalletType]);

            $iSumOfAllWelcomeOffersDistributed = $welcomeOfferDetail->sum('type = 0 AND id_offre_bienvenue = ' . $welcomeOffer->id_offre_bienvenue . ' AND status <> 2', 'montant');
            $sumAvailableOffers                = $unilendPromotionalWallet->getAvailableBalance();

            $startWelcomeOfferDate = \DateTime::createFromFormat('Y-m-d H:i:s', $welcomeOffer->debut . ' 00:00:00');
            $endWelcomeOfferDate   = \DateTime::createFromFormat('Y-m-d H:i:s', $welcomeOffer->fin . ' 23:59:59');
            $today                 = new \DateTime();

            if ($startWelcomeOfferDate <= $today && $endWelcomeOfferDate >= $today) {
                $offerIsValid = true;
            } else {
                $return = ['code' => 1, 'message' => "Il n'y a plus d'offre de bienvenue en cours."];
            }

            if ($iSumOfAllWelcomeOffersDistributed + $welcomeOffer->montant <= $welcomeOffer->montant_limit && $sumAvailableOffers >= $welcomeOffer->montant) {
                $enoughMoneyLeft = true;
            } else {
                $return = ['code' => 2, 'message' => "Il n'y a plus assez d'argent disponible pour créer l'offre de bienvenue."];
            }

            if ($offerIsValid && $enoughMoneyLeft) {
                $setting->get("Offre de bienvenue motif", 'type');

                $welcomeOfferDetail->id_offre_bienvenue = $welcomeOffer->id_offre_bienvenue;
                $welcomeOfferDetail->motif              = $setting->value;
                $welcomeOfferDetail->id_client          = $client->id_client;
                $welcomeOfferDetail->montant            = $welcomeOffer->montant;
                $welcomeOfferDetail->status             = \offres_bienvenues_details::STATUS_NEW;
                $welcomeOfferDetail->create();

                $welcomeOfferDetailEntity = $this->entityManager->getRepository('UnilendCoreBusinessBundle:OffresBienvenuesDetails')->find($welcomeOfferDetail->id_offre_bienvenue_detail);
                $this->operationManager->newWelcomeOffer($wallet, $welcomeOfferDetailEntity);

                $this->mailerManager->sendWelcomeOfferEmail($client, $welcomeOffer);
                $return = ['code' => 0, 'message' => 'Offre de bienvenue créée.'];
            }
        } else {
            $return = ['code' => 3, 'message' => "L'offre de bienvenue n'existe pas"];
        }
        return $return;
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

        $hasSource            = in_array($client->getSource2(), [SourceManager::HP_LENDER_SOURCE_NAME, SourceManager::HP_SOURCE_NAME]);
        $noPreviousValidation = (null === $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientsStatusHistory')->getFirstClientValidation($client));

        return ($hasSource && $noPreviousValidation);
    }

}
