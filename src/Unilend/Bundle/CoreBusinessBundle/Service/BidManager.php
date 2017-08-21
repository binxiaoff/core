<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Unilend\Bundle\CoreBusinessBundle\Entity\Autobid;
use Unilend\Bundle\CoreBusinessBundle\Entity\Bids;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\Notifications;
use Unilend\Bundle\CoreBusinessBundle\Entity\OffresBienvenues;
use Unilend\Bundle\CoreBusinessBundle\Entity\OffresBienvenuesDetails;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletBalanceHistory;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Exception\BidException;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductManager;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
use Doctrine\ORM\EntityManager;

/**
 * Class BidManager
 * @package Unilend\Bundle\CoreBusinessBundle\Service
 */
class BidManager
{
    const MODE_REBID_AUTO_BID_CREATE = 1;
    const MODE_REBID_AUTO_BID_UPDATE = 2;

    /** @var LoggerInterface */
    private $oLogger;

    /** @var NotificationManager */
    private $oNotificationManager;

    /** @var AutoBidSettingsManager */
    private $oAutoBidSettingsManager;

    /** @var LenderManager */
    private $oLenderManager;

    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;

    /** @var ProductManager */
    private $productManager;

    /** @var CIPManager */
    private $cipManager;

    /** @var EntityManager */
    private $entityManager;

    /** @var WalletManager */
    private $walletManager;

    public function __construct(
        EntityManagerSimulator $entityManagerSimulator,
        NotificationManager $oNotificationManager,
        AutoBidSettingsManager $oAutoBidSettingsManager,
        LenderManager $oLenderManager,
        ProductManager $productManager,
        CIPManager $cipManager,
        EntityManager $entityManager,
        WalletManager $walletManager
    )
    {
        $this->entityManagerSimulator  = $entityManagerSimulator;
        $this->oNotificationManager    = $oNotificationManager;
        $this->oAutoBidSettingsManager = $oAutoBidSettingsManager;
        $this->oLenderManager          = $oLenderManager;
        $this->productManager          = $productManager;
        $this->cipManager              = $cipManager;
        $this->entityManager           = $entityManager;
        $this->walletManager           = $walletManager;
    }

    /**
     * @param LoggerInterface $oLogger
     */
    public function setLogger(LoggerInterface $oLogger)
    {
        $this->oLogger = $oLogger;
    }

    /**
     * @param Wallet       $wallet
     * @param Projects     $project
     * @param              $amount
     * @param              $rate
     * @param Autobid|null $autobidSetting
     * @param bool         $bSendNotification
     *
     * @return Bids
     * @throws \Exception
     */
    public function bid(Wallet $wallet, Projects $project, $amount, $rate, Autobid $autobidSetting = null, $bSendNotification = true)
    {
        /** @var \settings $oSettings */
        $oSettings = $this->entityManagerSimulator->getRepository('settings');
        /** @var \projects $legacyProject */
        $legacyProject = $this->entityManagerSimulator->getRepository('projects');
        /** @var \bids $legacyBid */
        $legacyBid = $this->entityManagerSimulator->getRepository('bids');

        $oSettings->get('Pret min', 'type');
        $iAmountMin = (int)$oSettings->value;

        $iProjectId = $project->getIdProject();
        $legacyProject->get($iProjectId);

        $bid = new Bids();
        $bid->setIdLenderAccount($wallet)
            ->setProject($project)
            ->setAmount(bcmul($amount, 100))
            ->setRate($rate)
            ->setStatus(Bids::STATUS_BID_PENDING)
            ->setAutobid($autobidSetting);

        $legacyBid->id_lender_account = $wallet->getId();
        $legacyBid->id_project        = $iProjectId;
        $legacyBid->amount            = bcmul($amount, 100);
        $legacyBid->rate              = $rate;
        $legacyBid->status            = \bids::STATUS_BID_PENDING;
        if ($autobidSetting instanceof Autobid) {
            $legacyBid->id_autobid = $autobidSetting->getIdAutobid();
        }

        if ($iAmountMin > $amount) {
            if ($this->oLogger instanceof LoggerInterface) {
                $this->oLogger->warning('Amount is less than the min amount for a bid', ['project_id' => $iProjectId, 'lender_id' => $wallet->getId(), 'amount' => $amount, 'rate' => $rate]);
            }
            throw new BidException('bids-invalid-amount');
        }

        $projectRates = $this->getProjectRateRange($legacyProject);

        if (bccomp($rate, $projectRates['rate_max'], 1) > 0 || bccomp($rate, $projectRates['rate_min'], 1) < 0) {
            if ($this->oLogger instanceof LoggerInterface) {
                $this->oLogger->warning(
                    'The rate is less than the min rate for a bid',
                    ['project_id' => $iProjectId, 'lender_id' => $wallet->getId(), 'amount' => $amount, 'rate' => $rate]
                );
            }
            throw new BidException('bids-invalid-rate');
        }

        if (false === in_array($project->getStatus(), array(\projects_status::A_FUNDER, \projects_status::EN_FUNDING))) {
            if ($this->oLogger instanceof LoggerInterface) {
                $this->oLogger->warning(
                    'Project status is not valid for bidding',
                    ['project_id' => $iProjectId, 'lender_id' => $wallet->getId(), 'amount' => $amount, 'rate' => $rate, 'project_status' => $project->getStatus()]
                );
            }
            throw new BidException('bids-invalid-project-status');
        }

        $oCurrentDate = new \DateTime();
        $oEndDate     = $project->getDateRetrait();
        if ($legacyProject->date_fin != '0000-00-00 00:00:00') {
            $oEndDate = $project->getDateFin();
        }

        if ($oCurrentDate > $oEndDate) {
            if ($this->oLogger instanceof LoggerInterface) {
                $this->oLogger->warning(
                    'Project end date is passed for bidding',
                    ['project_id' => $iProjectId, 'lender_id' => $wallet->getId(), 'amount' => $amount, 'rate' => $rate, 'project_ended' => $oEndDate->format('c'), 'now' => $oCurrentDate->format('c')]);
            }
            throw new BidException('bids-invalid-project-status');
        }

        if (WalletType::LENDER !== $wallet->getIdType()->getLabel()) {
            if ($this->oLogger instanceof LoggerInterface) {
                $this->oLogger->warning('Wallet is no Lender', ['project_id' => $iProjectId, 'lender_id' => $wallet->getId(), 'amount' => $amount, 'rate' => $rate]);
            }
            throw new BidException('bids-invalid-lender');
        }

        if (false === $this->oLenderManager->canBid($wallet->getIdClient())) {
            if ($this->oLogger instanceof LoggerInterface) {
                $this->oLogger->warning('lender cannot bid', ['project_id' => $iProjectId, 'lender_id' => $wallet->getId(), 'amount' => $amount, 'rate' => $rate]);
            }
            throw new BidException('bids-lender-cannot-bid');
        }

        if (false === $this->productManager->isBidEligible($bid)) {
            if ($this->oLogger instanceof LoggerInterface) {
                $this->oLogger->warning('The Bid is not eligible for the project', ['project_id' => $iProjectId, 'lender_id' => $wallet->getId(), 'amount' => $amount, 'rate' => $rate]);
            }
            throw new BidException('bids-not-eligible');
        }

        $iClientId = $wallet->getIdClient()->getIdClient();
        $iBalance  = $wallet->getAvailableBalance();

        if ($iBalance < $amount) {
            if ($this->oLogger instanceof LoggerInterface) {
                $this->oLogger->warning('lender\'s balance not enough for a bid', ['project_id' => $iProjectId, 'lender_id' => $wallet->getId(), 'amount' => $amount, 'rate' => $rate, 'balance' => $iBalance]);
            }
            throw new BidException('bids-low-balance');
        }

        if ($this->cipManager->isCIPValidationNeeded($legacyBid) && false === $this->cipManager->hasValidEvaluation($wallet->getIdClient())) {
            throw new BidException('bids-cip-validation-needed');
        }

        $iBidNb = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Bids')->countBy(['idProject' => $iProjectId]);
        $iBidNb ++;
        $bid->setOrdre($iBidNb);
        $this->entityManager->persist($bid);
        $walletBalanceHistory = $this->walletManager->engageBalance($wallet, $amount, $bid);
        $this->entityManager->flush($bid);

        $unusedWelcomeOffers = $this->entityManager->getRepository('UnilendCoreBusinessBundle:OffresBienvenuesDetails')->findBy(['idClient' => $iClientId, 'status' => OffresBienvenuesDetails::TYPE_OFFER]);
        if ($unusedWelcomeOffers != null) {
            $offerTotal = 0;
            /** @var OffresBienvenuesDetails $offer */
            foreach ($unusedWelcomeOffers as $offer) {
                if ($offerTotal <= $amount) {
                    $offerTotal += ($offer->getMontant() / 100); // total des offres
                    $offer->setStatus(OffresBienvenuesDetails::STATUS_USED);
                    $offer->setIdBid($bid->getIdBid());
                    $this->entityManager->flush($offer);

                    // Apres addition de la derniere offre on se rend compte que le total depasse
                    if ($offerTotal > $amount) {
                        // On fait la diff et on créer un remb du trop plein d'offres
                        $iAmountRepayment = $offerTotal - $amount;

                        $welcomeOffer = new OffresBienvenuesDetails();
                        $welcomeOffer->setIdOffreBienvenue(0)
                            ->setIdClient($iClientId)
                            ->setIdBidRemb($bid->getIdBid())
                            ->setStatus(OffresBienvenuesDetails::STATUS_NEW)
                            ->setType(OffresBienvenuesDetails::TYPE_CUT)
                            ->setMontant($iAmountRepayment * 100);

                        $this->entityManager->persist($welcomeOffer);
                        $this->entityManager->flush($welcomeOffer);
                    }
                } else {
                    break;
                }
            }
        }

        if ($bSendNotification) {
            $this->oNotificationManager->create(
                Notifications::TYPE_BID_PLACED,
                $bid->getAutobid() !== null ? \clients_gestion_type_notif::TYPE_AUTOBID_ACCEPTED_REJECTED_BID : \clients_gestion_type_notif::TYPE_BID_PLACED,
                $iClientId,
                'sendBidConfirmation',
                $iProjectId,
                $amount,
                $bid->getIdBid(),
                $walletBalanceHistory
            );
        }

        return $bid;
    }

    /**
     * @param Autobid  $autoBid
     * @param Projects $project
     * @param float    $rate
     * @param bool     $sendNotification
     * @return Bids|false
     */
    public function bidByAutoBidSettings(Autobid $autoBid, Projects $project, $rate, $sendNotification = true)
    {
        if (
            bccomp($autoBid->getRateMin(), $rate, 1) <= 0
            && WalletType::LENDER === $autoBid->getIdLender()->getIdType()->getLabel()
            && bccomp($autoBid->getIdLender()->getAvailableBalance(), $autoBid->getAmount()) >= 0
            && $this->oAutoBidSettingsManager->isOn($autoBid->getIdLender()->getIdClient())
            && $this->oAutoBidSettingsManager->isQualified($autoBid->getIdLender()->getIdClient())
        ) {
            return $this->bid($autoBid->getIdLender(), $project, $autoBid->getAmount(), $rate, $autoBid, $sendNotification);
        }

        return false;
    }

    /**
     * @param Bids $bid
     * @param bool $sendNotification
     */
    public function reject(Bids $bid, $sendNotification = true)
    {
        if ($bid->getStatus() == Bids::STATUS_BID_PENDING || $bid->getStatus() == Bids::STATUS_AUTOBID_REJECTED_TEMPORARILY) {
            $walletBalanceHistory = $this->creditRejectedBid($bid, $bid->getAmount() / 100);

            if ($sendNotification) {
                $this->notificationRejection($bid, $walletBalanceHistory);
            }

            $bid->setStatus(Bids::STATUS_BID_REJECTED);
            $this->entityManager->flush($bid);
        }
    }

    /**
     * @param Bids  $bid
     * @param float $fRepaymentAmount
     */
    public function rejectPartially(Bids $bid, $fRepaymentAmount)
    {
        if ($bid->getStatus() == \bids::STATUS_BID_PENDING || $bid->getStatus() == \bids::STATUS_AUTOBID_REJECTED_TEMPORARILY) {
            $walletBalanceHistory = $this->creditRejectedBid($bid, $fRepaymentAmount);
            $this->notificationRejection($bid, $walletBalanceHistory);
            // Save new amount of the bid after repayment
            $amount = bcsub($bid->getAmount(), bcmul($fRepaymentAmount, 100));
            $bid->setAmount($amount)
                ->setStatus(Bids::STATUS_BID_ACCEPTED);
            $this->entityManager->flush($bid);
        }
    }

    /**
     * @param Bids   $bid
     * @param string $currentRate
     * @param int    $iMode
     * @param bool   $bSendNotification
     */
    public function reBidAutoBidOrReject(Bids $bid, $currentRate, $iMode, $bSendNotification = true)
    {
        /** @var \projects $project */
        $project = $this->entityManagerSimulator->getRepository('projects');

        $autobid = $bid->getAutobid();
        if ($autobid instanceof Autobid && false === empty($bid->getIdBid()) && $project->get($bid->getProject()->getIdProject())) {
            if (
                bccomp($currentRate, $this->getProjectRateRange($project)['rate_min'], 1) >= 0
                && bccomp($currentRate, $autobid->getRateMin(), 1) >= 0
                && WalletType::LENDER === $bid->getIdLenderAccount()->getIdType()->getLabel()
                && Clients::STATUS_ONLINE == $bid->getIdLenderAccount()->getIdClient()->getStatus()
            ) { //check online/offline instead of LenderManager::canBid() because of the performance issue.
                if (self::MODE_REBID_AUTO_BID_CREATE === $iMode) {
                    $iBidOrder = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Bids')->countBy(['idProject' => $bid->getProject()->getIdProject()]);
                    $iBidOrder ++;
                    $newBid = clone $bid;
                    $newBid->setOrdre($iBidOrder)
                           ->setRate($currentRate)
                           ->setStatus(Bids::STATUS_BID_PENDING);
                    $this->entityManager->persist($newBid);
                    $bid->setStatus(Bids::STATUS_BID_REJECTED);
                    $this->entityManager->flush($newBid);
                } else {
                    $bid->setRate($currentRate)
                        ->setStatus(Bids::STATUS_BID_PENDING);
                }
                $this->entityManager->flush($bid);
            } else {
                $this->reject($bid, $bSendNotification);
            }
        }
    }

    /**
     * @param Bids  $bid
     * @param float $fAmount
     *
     * @return WalletBalanceHistory
     */
    private function creditRejectedBid(Bids $bid, $fAmount)
    {
        $walletBalanceHistory = $this->walletManager->releaseBalance($bid->getIdLenderAccount(), $fAmount, $bid);
        $fAmountX100          = $fAmount * 100;
        $welcomeOffer         = new OffresBienvenuesDetails();

        $welcomeOfferTotal = $this->entityManager->getRepository('UnilendCoreBusinessBundle:OffresBienvenuesDetails')->getSumOfferByBid($bid->getIdLenderAccount()->getIdClient()->getIdClient(), $bid->getIdBid());
        if ($welcomeOfferTotal > 0) {
            if ($bid->getAmount() === $fAmountX100) { //Totally credit
                $welcomeOffer->setMontant(min($welcomeOfferTotal, $fAmountX100));
            } elseif (($bid->getAmount() - $fAmountX100) <= $welcomeOfferTotal) { //Partially credit
                $welcomeOffer->setMontant($welcomeOfferTotal - ($bid->getAmount() - $fAmountX100));
            }

            if (false === empty($welcomeOffer->getMontant())) {
                $welcomeOffer->setIdOffreBienvenue(0);
                $welcomeOffer->setIdClient($bid->getIdLenderAccount()->getIdClient()->getIdClient());
                $welcomeOffer->setIdBid(0);
                $welcomeOffer->setIdBidRemb($bid->getIdBid());
                $welcomeOffer->setStatus(OffresBienvenuesDetails::STATUS_NEW);
                $welcomeOffer->setType(OffresBienvenuesDetails::TYPE_PAYBACK);

                $this->entityManager->persist($welcomeOffer);
                $this->entityManager->flush($welcomeOffer);
            }
        }

        return $walletBalanceHistory;

    }

    /**
     * @param Bids                 $bid
     * @param WalletBalanceHistory $walletBalanceHistory
     */
    private function notificationRejection(Bids $bid, WalletBalanceHistory $walletBalanceHistory)
    {
        if (WalletType::LENDER === $bid->getIdLenderAccount()->getIdType()->getLabel()) {
            $this->oNotificationManager->create(
                Notifications::TYPE_BID_REJECTED,
                $bid->getAutobid() !== null ? \clients_gestion_type_notif::TYPE_AUTOBID_ACCEPTED_REJECTED_BID : \clients_gestion_type_notif::TYPE_BID_REJECTED,
                $bid->getIdLenderAccount()->getIdClient()->getIdClient(),
                'sendBidRejected',
                $bid->getProject()->getIdProject(),
                $bid->getAmount() / 100,
                $bid->getIdBid(),
                $walletBalanceHistory
            );
        }
    }

    /**
     * @param \projects $project
     *
     * @return array
     */
    public function getProjectRateRange(\projects $project)
    {
        /** @var \project_rate_settings $projectRateSettings */
        $projectRateSettings = $this->entityManagerSimulator->getRepository('project_rate_settings');

        if (false === empty($project->id_rate) && $projectRateSettings->get($project->id_rate)) {
            return ['rate_min' => (float) $projectRateSettings->rate_min, 'rate_max' => (float) $projectRateSettings->rate_max];
        }

        return $projectRateSettings->getGlobalMinMaxRate();
    }
}
