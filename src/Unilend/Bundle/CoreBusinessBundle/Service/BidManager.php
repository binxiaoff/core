<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Autobid;
use Unilend\Bundle\CoreBusinessBundle\Entity\Bids;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\Notifications;
use Unilend\Bundle\CoreBusinessBundle\Entity\OffresBienvenuesDetails;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletBalanceHistory;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Exception\BidException;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

/**
 * Class BidManager
 * @package Unilend\Bundle\CoreBusinessBundle\Service
 */
class BidManager
{
    const MODE_REBID_AUTO_BID_CREATE = 1;
    const MODE_REBID_AUTO_BID_UPDATE = 2;

    /** @var LoggerInterface */
    private $logger;

    /** @var NotificationManager */
    private $notificationManager;

    /** @var AutoBidSettingsManager */
    private $autoBidSettingsManager;

    /** @var LenderManager */
    private $lenderManager;

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

    /**
     * @param EntityManagerSimulator $entityManagerSimulator
     * @param NotificationManager    $notificationManager
     * @param AutoBidSettingsManager $autoBidSettingsManager
     * @param LenderManager          $lenderManager
     * @param ProductManager         $productManager
     * @param CIPManager             $cipManager
     * @param EntityManager          $entityManager
     * @param WalletManager          $walletManager
     */
    public function __construct(
        EntityManagerSimulator $entityManagerSimulator,
        NotificationManager $notificationManager,
        AutoBidSettingsManager $autoBidSettingsManager,
        LenderManager $lenderManager,
        ProductManager $productManager,
        CIPManager $cipManager,
        EntityManager $entityManager,
        WalletManager $walletManager
    )
    {
        $this->entityManagerSimulator  = $entityManagerSimulator;
        $this->notificationManager    = $notificationManager;
        $this->autoBidSettingsManager = $autoBidSettingsManager;
        $this->lenderManager          = $lenderManager;
        $this->productManager          = $productManager;
        $this->cipManager              = $cipManager;
        $this->entityManager           = $entityManager;
        $this->walletManager           = $walletManager;
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param Wallet       $wallet
     * @param Projects     $project
     * @param int|float    $amount
     * @param float        $rate
     * @param Autobid|null $autobidSetting
     * @param bool         $sendNotification
     *
     * @return Bids
     * @throws \Exception
     */
    public function bid(Wallet $wallet, Projects $project, $amount, float $rate, Autobid $autobidSetting = null, $sendNotification = true) : Bids
    {
        /** @var \projects $legacyProject */
        $legacyProject = $this->entityManagerSimulator->getRepository('projects');
        $projectId     = $project->getIdProject();
        $legacyProject->get($projectId);

        $minAmountSetting = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ClientSettings')->findOneBy(['type' => 'Pret min']);
        $amountMin        = (int) $minAmountSetting->getValue();

        $bid = new Bids();
        $bid->setIdLenderAccount($wallet)
            ->setProject($project)
            ->setAmount(bcmul($amount, 100))
            ->setInitialAmount(bcmul($amount, 100))
            ->setRate($rate)
            ->setStatus(Bids::STATUS_PENDING)
            ->setAutobid($autobidSetting);

        if ($amountMin > $amount) {
            if ($this->logger instanceof LoggerInterface) {
                $this->logger->warning('Amount is less than the min amount for a bid', ['project_id' => $projectId, 'lender_id' => $wallet->getId(), 'amount' => $amount, 'rate' => $rate]);
            }
            throw new BidException('bids-invalid-amount');
        }

        $projectRates = $this->getProjectRateRange($legacyProject);

        if (bccomp($rate, $projectRates['rate_max'], 1) > 0 || bccomp($rate, $projectRates['rate_min'], 1) < 0) {
            if ($this->logger instanceof LoggerInterface) {
                $this->logger->warning(
                    'The rate is less than the min rate for a bid',
                    ['project_id' => $projectId, 'lender_id' => $wallet->getId(), 'amount' => $amount, 'rate' => $rate]
                );
            }
            throw new BidException('bids-invalid-rate');
        }

        if (false === in_array($project->getStatus(), array(\projects_status::A_FUNDER, \projects_status::EN_FUNDING))) {
            if ($this->logger instanceof LoggerInterface) {
                $this->logger->warning(
                    'Project status is not valid for bidding',
                    ['project_id' => $projectId, 'lender_id' => $wallet->getId(), 'amount' => $amount, 'rate' => $rate, 'project_status' => $project->getStatus()]
                );
            }
            throw new BidException('bids-invalid-project-status');
        }

        $currentDate = new \DateTime();
        $endDate     = $project->getDateRetrait();
        if ($legacyProject->date_fin != '0000-00-00 00:00:00') {
            $endDate = $project->getDateFin();
        }

        if ($currentDate > $endDate) {
            if ($this->logger instanceof LoggerInterface) {
                $this->logger->warning(
                    'Project end date is passed for bidding',
                    ['project_id' => $projectId, 'lender_id' => $wallet->getId(), 'amount' => $amount, 'rate' => $rate, 'project_ended' => $endDate->format('c'), 'now' => $currentDate->format('c')]);
            }
            throw new BidException('bids-invalid-project-status');
        }

        if (WalletType::LENDER !== $wallet->getIdType()->getLabel()) {
            if ($this->logger instanceof LoggerInterface) {
                $this->logger->warning('Wallet is no Lender', ['project_id' => $projectId, 'lender_id' => $wallet->getId(), 'amount' => $amount, 'rate' => $rate]);
            }
            throw new BidException('bids-invalid-lender');
        }

        if (false === $this->lenderManager->canBid($wallet->getIdClient())) {
            if ($this->logger instanceof LoggerInterface) {
                $this->logger->warning('lender cannot bid', ['project_id' => $projectId, 'lender_id' => $wallet->getId(), 'amount' => $amount, 'rate' => $rate]);
            }
            throw new BidException('bids-lender-cannot-bid');
        }

        if (false === $this->productManager->isBidEligible($bid)) {
            if ($this->logger instanceof LoggerInterface) {
                $this->logger->warning('The Bid is not eligible for the project', ['project_id' => $projectId, 'lender_id' => $wallet->getId(), 'amount' => $amount, 'rate' => $rate]);
            }
            throw new BidException('bids-not-eligible');
        }

        $clientId = $wallet->getIdClient()->getIdClient();
        $balance  = $wallet->getAvailableBalance();

        if ($balance < $amount) {
            if ($this->logger instanceof LoggerInterface) {
                $this->logger->warning('lender\'s balance not enough for a bid', ['project_id' => $projectId, 'lender_id' => $wallet->getId(), 'amount' => $amount, 'rate' => $rate, 'balance' => $balance]);
            }
            throw new BidException('bids-low-balance');
        }

        if ($this->cipManager->isCIPValidationNeeded($bid) && false === $this->cipManager->hasValidEvaluation($wallet->getIdClient())) {
            throw new BidException('bids-cip-validation-needed');
        }

        $bidNb = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Bids')->countBy(['idProject' => $projectId]);
        $bidNb ++;
        $bid->setOrdre($bidNb);
        $this->entityManager->persist($bid);
        $walletBalanceHistory = $this->walletManager->engageBalance($wallet, $amount, $bid);
        $this->entityManager->flush($bid);

        $unusedWelcomeOffers = $this->entityManager->getRepository('UnilendCoreBusinessBundle:OffresBienvenuesDetails')->findBy(['idClient' => $clientId, 'status' => OffresBienvenuesDetails::TYPE_OFFER]);
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
                        $amountRepayment = $offerTotal - $amount;

                        $welcomeOffer = new OffresBienvenuesDetails();
                        $welcomeOffer->setIdOffreBienvenue(0)
                            ->setIdClient($clientId)
                            ->setIdBidRemb($bid->getIdBid())
                            ->setStatus(OffresBienvenuesDetails::STATUS_NEW)
                            ->setType(OffresBienvenuesDetails::TYPE_CUT)
                            ->setMontant($amountRepayment * 100);

                        $this->entityManager->persist($welcomeOffer);
                        $this->entityManager->flush($welcomeOffer);
                    }
                } else {
                    break;
                }
            }
        }

        if ($sendNotification) {
            $this->notificationManager->create(
                Notifications::TYPE_BID_PLACED,
                $bid->getAutobid() !== null ? \clients_gestion_type_notif::TYPE_AUTOBID_ACCEPTED_REJECTED_BID : \clients_gestion_type_notif::TYPE_BID_PLACED,
                $clientId,
                'sendBidConfirmation',
                $projectId,
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
     *
     * @return bool|Bids
     * @throws \Exception
     */
    public function bidByAutoBidSettings(Autobid $autoBid, Projects $project, float $rate, $sendNotification = true)
    {
        $biddenAutobid = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Bids')->findOneBy(['idProject' => $project, 'idAutobid' => $autoBid]);
        if (
            null === $biddenAutobid
            && bccomp($autoBid->getRateMin(), $rate, 1) <= 0
            && WalletType::LENDER === $autoBid->getIdLender()->getIdType()->getLabel()
            && bccomp($autoBid->getIdLender()->getAvailableBalance(), $autoBid->getAmount()) >= 0
            && $this->autoBidSettingsManager->isOn($autoBid->getIdLender()->getIdClient())
            && $this->autoBidSettingsManager->isQualified($autoBid->getIdLender()->getIdClient())
        ) {
            return $this->bid($autoBid->getIdLender(), $project, $autoBid->getAmount(), $rate, $autoBid, $sendNotification);
        }

        return false;
    }

    /**
     * @param Bids $bid
     * @param bool $sendNotification
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function reject(Bids $bid, $sendNotification = true)
    {
        if ($bid->getStatus() == Bids::STATUS_PENDING || $bid->getStatus() == Bids::STATUS_TEMPORARILY_REJECTED_AUTOBID) {
            $walletBalanceHistory = $this->creditRejectedBid($bid, $bid->getAmount() / 100);

            if ($sendNotification) {
                $this->notificationRejection($bid, $walletBalanceHistory);
            }

            $bid->setStatus(Bids::STATUS_REJECTED);
            $this->entityManager->flush($bid);
        }
    }

    /**
     * @param Bids  $bid
     * @param float $repaymentAmount
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function rejectPartially(Bids $bid, $repaymentAmount)
    {
        if (in_array($bid->getStatus(), [Bids::STATUS_PENDING, Bids::STATUS_TEMPORARILY_REJECTED_AUTOBID])) {
            $walletBalanceHistory = $this->creditRejectedBid($bid, $repaymentAmount);
            $this->notificationRejection($bid, $walletBalanceHistory);
            // Save new amount of the bid after repayment
            $amount = bcsub($bid->getAmount(), bcmul($repaymentAmount, 100));
            $bid->setAmount($amount)
                ->setStatus(Bids::STATUS_ACCEPTED);
            $this->entityManager->flush($bid);
        }
    }

    /**
     * @param Bids   $bid
     * @param string $currentRate
     * @param int    $mode
     * @param bool   $sendNotification
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function reBidAutoBidOrReject(Bids $bid, string $currentRate, int $mode, bool $sendNotification = true)
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
                if (self::MODE_REBID_AUTO_BID_CREATE === $mode) {
                    $iBidOrder = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Bids')->countBy(['idProject' => $bid->getProject()->getIdProject()]);
                    $iBidOrder ++;
                    $newBid = clone $bid;
                    $newBid->setOrdre($iBidOrder)
                           ->setRate($currentRate)
                           ->setStatus(Bids::STATUS_PENDING);
                    $this->entityManager->persist($newBid);
                    $bid->setStatus(Bids::STATUS_REJECTED);
                    $this->entityManager->flush($newBid);
                } else {
                    $bid->setRate($currentRate)
                        ->setStatus(Bids::STATUS_PENDING);
                }
                $this->entityManager->flush($bid);
            } else {
                $this->reject($bid, $sendNotification);
            }
        }
    }

    /**
     * @param Bids  $bid
     * @param float $amount
     *
     * @return WalletBalanceHistory
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    private function creditRejectedBid(Bids $bid, float $amount) :  WalletBalanceHistory
    {
        $walletBalanceHistory = $this->walletManager->releaseBalance($bid->getIdLenderAccount(), $amount, $bid);
        $amountX100           = $amount * 100;
        $welcomeOffer         = new OffresBienvenuesDetails();

        $welcomeOfferTotal = $this->entityManager->getRepository('UnilendCoreBusinessBundle:OffresBienvenuesDetails')->getSumOfferByBid($bid->getIdLenderAccount()->getIdClient()->getIdClient(), $bid->getIdBid());
        if ($welcomeOfferTotal > 0) {
            if ($bid->getAmount() === $amountX100) { //Totally credit
                $welcomeOffer->setMontant(min($welcomeOfferTotal, $amountX100));
            } elseif (($bid->getAmount() - $amountX100) <= $welcomeOfferTotal) { //Partially credit
                $welcomeOffer->setMontant($welcomeOfferTotal - ($bid->getAmount() - $amountX100));
            }

            if (false === empty($welcomeOffer->getMontant())) {
                $welcomeOffer
                    ->setIdOffreBienvenue(0)
                    ->setIdClient($bid->getIdLenderAccount()->getIdClient()->getIdClient())
                    ->setIdBid(0)
                    ->setIdBidRemb($bid->getIdBid())
                    ->setStatus(OffresBienvenuesDetails::STATUS_NEW)
                    ->setType(OffresBienvenuesDetails::TYPE_PAYBACK);

                $this->entityManager->persist($welcomeOffer);
                $this->entityManager->flush($welcomeOffer);
            }
        }

        return $walletBalanceHistory;

    }

    /**
     * @param Bids                 $bid
     * @param WalletBalanceHistory $walletBalanceHistory
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function notificationRejection(Bids $bid, WalletBalanceHistory $walletBalanceHistory)
    {
        if (WalletType::LENDER === $bid->getIdLenderAccount()->getIdType()->getLabel()) {
            $this->notificationManager->create(
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
