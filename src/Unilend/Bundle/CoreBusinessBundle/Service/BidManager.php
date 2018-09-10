<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\{EntityManagerInterface, NonUniqueResultException, NoResultException, OptimisticLockException};
use Psr\Cache\CacheException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{AcceptedBids, Autobid, Bids, ClientsGestionTypeNotif, Notifications, OffresBienvenuesDetails, Projects, ProjectsStatus, Sponsorship, Wallet,
    WalletBalanceHistory, WalletType};
use Unilend\Bundle\CoreBusinessBundle\Exception\BidException;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductManager;
use Unilend\librairies\CacheKeys;

/**
 * Class BidManager
 * @package Unilend\Bundle\CoreBusinessBundle\Service
 */
class BidManager
{
    /** @var LoggerInterface */
    private $logger;
    /** @var NotificationManager */
    private $notificationManager;
    /** @var AutoBidSettingsManager */
    private $autoBidSettingsManager;
    /** @var LenderManager */
    private $lenderManager;
    /** @var ProductManager */
    private $productManager;
    /** @var CIPManager */
    private $cipManager;
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var WalletManager */
    private $walletManager;
    /** @var SponsorshipManager */
    private $sponsorshipManager;
    /** @var CacheItemPoolInterface */
    private $cachePool;

    /**
     * @param NotificationManager    $notificationManager
     * @param AutoBidSettingsManager $autoBidSettingsManager
     * @param LenderManager          $lenderManager
     * @param ProductManager         $productManager
     * @param CIPManager             $cipManager
     * @param EntityManagerInterface $entityManager
     * @param WalletManager          $walletManager
     * @param SponsorshipManager     $sponsorshipManager
     * @param CacheItemPoolInterface $cachePool
     */
    public function __construct(
        NotificationManager $notificationManager,
        AutoBidSettingsManager $autoBidSettingsManager,
        LenderManager $lenderManager,
        ProductManager $productManager,
        CIPManager $cipManager,
        EntityManagerInterface $entityManager,
        WalletManager $walletManager,
        SponsorshipManager $sponsorshipManager,
        CacheItemPoolInterface $cachePool
    )
    {
        $this->notificationManager    = $notificationManager;
        $this->autoBidSettingsManager = $autoBidSettingsManager;
        $this->lenderManager          = $lenderManager;
        $this->productManager         = $productManager;
        $this->cipManager             = $cipManager;
        $this->entityManager          = $entityManager;
        $this->walletManager          = $walletManager;
        $this->sponsorshipManager     = $sponsorshipManager;
        $this->cachePool              = $cachePool;
    }

    /**
     * @required
     *
     * @param LoggerInterface|null $logger
     */
    public function setLogger(?LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param Wallet       $wallet
     * @param Projects     $project
     * @param float|int    $amount
     * @param float        $rate
     * @param Autobid|null $autoBidSetting
     * @param bool         $sendNotification
     *
     * @return Bids
     * @throws BidException
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws OptimisticLockException
     */
    public function bid(Wallet $wallet, Projects $project, $amount, float $rate, ?Autobid $autoBidSetting = null, bool $sendNotification = true): Bids
    {
        $bid = new Bids();
        $bid
            ->setIdLenderAccount($wallet)
            ->setProject($project)
            ->setAmount(bcmul($amount, 100))
            ->setRate($rate)
            ->setStatus(Bids::STATUS_PENDING)
            ->setAutobid($autoBidSetting);

        if (false === $autoBidSetting instanceof Autobid) {
            $bidNb = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Bids')->countBy(['idProject' => $project]);
            $bidNb++;
            $bid->setOrdre($bidNb);

            $this->checkMinimumAmount($bid);
            $this->checkRate($bid);
            $this->checkProjectStatus($bid);
            $this->checkProjectDates($bid);
            $this->checkLenderCanBid($bid);
        }

        $this->checkLenderBalance($bid);
        $this->checkBidEligibility($bid);
        $this->checkCip($bid);

        $this->entityManager->persist($bid);
        $walletBalanceHistory = $this->walletManager->engageBalance($wallet, $amount, $bid);
        $this->entityManager->flush($bid);

        $clientId = $wallet->getIdClient()->getIdClient();
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
                        $welcomeOffer
                            ->setIdOffreBienvenue(0)
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
                $bid->getAutobid() !== null ? ClientsGestionTypeNotif::TYPE_AUTOBID_ACCEPTED_REJECTED_BID : ClientsGestionTypeNotif::TYPE_BID_PLACED,
                $clientId,
                'sendBidConfirmation',
                $project->getIdProject(),
                $amount,
                $bid->getIdBid(),
                $walletBalanceHistory
            );
        }

        return $bid;
    }

    /**
     * @param Bids $bid
     *
     * @throws BidException
     */
    private function checkMinimumAmount(Bids $bid): void
    {
        $bidAmount     = bcdiv($bid->getAmount(), 100);
        $minimumAmount = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Settings')
            ->findOneBy(['type' => 'Pret min'])
            ->getValue();

        if (bccomp($bidAmount, $minimumAmount, 2) < 0) {
            if ($this->logger instanceof LoggerInterface) {
                $this->logger->warning('Amount is less than the min amount for a bid', [
                    'project_id' => $bid->getProject()->getIdProject(),
                    'lender_id'  => $bid->getIdLenderAccount()->getId(),
                    'amount'     => $bidAmount,
                    'rate'       => $bid->getRate()
                ]);
            }

            throw new BidException('bids-invalid-amount');
        }
    }

    /**
     * @param Bids $bid
     *
     * @throws BidException
     */
    private function checkRate(Bids $bid): void
    {
        $projectRates = $this->getProjectRateRange($bid->getProject());

        if (
            bccomp($bid->getRate(), $projectRates['rate_max'], 1) > 0
            || bccomp($bid->getRate(), $projectRates['rate_min'], 1) < 0
        ) {
            if ($this->logger instanceof LoggerInterface) {
                $this->logger->warning('The rate is less than the min rate for a bid', [
                    'project_id' => $bid->getProject()->getIdProject(),
                    'lender_id'  => $bid->getIdLenderAccount()->getId(),
                    'amount'     => $bid->getAmount() / 100,
                    'rate'       => $bid->getRate()
                ]);
            }

            throw new BidException('bids-invalid-rate');
        }
    }

    /**
     * @param Bids $bid
     *
     * @throws BidException
     */
    private function checkProjectStatus(Bids $bid): void
    {
        if (false === in_array($bid->getProject()->getStatus(), [ProjectsStatus::A_FUNDER, ProjectsStatus::EN_FUNDING])) {
            if ($this->logger instanceof LoggerInterface) {
                $this->logger->warning('Project status is not valid for bidding', [
                    'project_id'     => $bid->getProject()->getIdProject(),
                    'lender_id'      => $bid->getIdLenderAccount()->getId(),
                    'amount'         => $bid->getAmount() / 100,
                    'rate'           => $bid->getRate(),
                    'project_status' => $bid->getProject()->getStatus()
                ]);
            }

            throw new BidException('bids-invalid-project-status');
        }
    }

    /**
     * @param Bids $bid
     *
     * @throws BidException
     */
    private function checkProjectDates(Bids $bid): void
    {
        $currentDate = new \DateTime();
        $endDate     = $bid->getProject()->getDateFin() ?? $bid->getProject()->getDateRetrait();

        if ($currentDate > $endDate) {
            if ($this->logger instanceof LoggerInterface) {
                $this->logger->warning('Project end date is passed for bidding', [
                    'project_id'    => $bid->getProject()->getIdProject(),
                    'lender_id'     => $bid->getIdLenderAccount()->getId(),
                    'amount'        => $bid->getAmount() / 100,
                    'rate'          => $bid->getRate(),
                    'project_ended' => $endDate->format('c'),
                    'now'           => $currentDate->format('c')
                ]);
            }

            throw new BidException('bids-invalid-project-status');
        }
    }

    /**
     * @param Bids $bid
     *
     * @throws BidException
     */
    private function checkLenderCanBid(Bids $bid): void
    {
        if (false === $this->lenderManager->canBid($bid->getIdLenderAccount()->getIdClient())) {
            if ($this->logger instanceof LoggerInterface) {
                $this->logger->warning('lender cannot bid', [
                    'project_id' => $bid->getProject()->getIdProject(),
                    'lender_id'  => $bid->getIdLenderAccount()->getId(),
                    'amount'     => $bid->getAmount() / 100,
                    'rate'       => $bid->getRate()
                ]);
            }

            throw new BidException('bids-lender-cannot-bid');
        }
    }

    /**
     * @param Bids $bid
     *
     * @throws BidException
     */
    private function checkBidEligibility(Bids $bid): void
    {
        if (false === $this->productManager->isBidEligible($bid)) {
            if ($this->logger instanceof LoggerInterface) {
                $this->logger->warning('The Bid is not eligible for the project', [
                    'project_id' => $bid->getProject()->getIdProject(),
                    'lender_id'  => $bid->getIdLenderAccount()->getId(),
                    'amount'     => $bid->getAmount() / 100,
                    'rate'       => $bid->getRate()
                ]);
            }

            throw new BidException('bids-not-eligible');
        }
    }

    /**
     * @param Bids $bid
     *
     * @throws BidException
     */
    private function checkLenderBalance(Bids $bid): void
    {
        $balance   = $bid->getIdLenderAccount()->getAvailableBalance();
        $bidAmount = bcdiv($bid->getAmount(), 100);

        if (bccomp($balance, $bidAmount, 2) < 0) {
            if ($this->logger instanceof LoggerInterface) {
                $this->logger->warning('Lender\'s balance not enough for a bid', [
                    'project_id' => $bid->getProject()->getIdProject(),
                    'lender_id'  => $bid->getIdLenderAccount()->getId(),
                    'amount'     => $bidAmount,
                    'rate'       => $bid->getRate(),
                    'balance'    => $balance
                ]);
            }

            throw new BidException('bids-low-balance');
        }
    }

    /**
     * @param Bids $bid
     *
     * @throws \Exception
     * @throws BidException
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    private function checkCip(Bids $bid): void
    {
        if (
            $this->cipManager->isCIPValidationNeeded($bid)
            && false === $this->cipManager->hasValidEvaluation($bid->getIdLenderAccount()->getIdClient())
        ) {
            $this->logger->warning('CIP validation is needed for a bid', [
                'project_id' => $bid->getProject()->getIdProject(),
                'lender_id'  => $bid->getIdLenderAccount()->getId(),
                'amount'     => $bid->getAmount() / 100,
                'rate'       => $bid->getRate()
            ]);

            throw new BidException('bids-cip-validation-needed');
        }
    }

    /**
     * @param Bids $bid
     * @param bool $sendNotification
     *
     * @throws OptimisticLockException
     * @throws \Exception
     */
    public function reject(Bids $bid, bool $sendNotification = true): void
    {
        if (in_array($bid->getStatus(), [Bids::STATUS_PENDING, Bids::STATUS_TEMPORARILY_REJECTED_AUTOBID])) {
            $walletBalanceHistory = $this->creditRejectedBid($bid, $bid->getAmount() / 100);

            if ($sendNotification) {
                $this->notificationRejection($bid, $walletBalanceHistory);
            }

            $bid->setStatus(Bids::STATUS_REJECTED);
            $this->entityManager->flush($bid);
        }
    }

    /**
     * @param Bids     $bid
     * @param string   $currentRate
     * @param int|null $bidOrder
     * @param bool     $sendNotification
     *
     * @return Bids
     * @throws OptimisticLockException
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function reBidAutoBidOrReject(Bids $bid, string $currentRate, ?int $bidOrder, bool $sendNotification = true): Bids
    {
        $minimumProjectRate = (string) $this->getProjectRateRange($bid->getProject())['rate_min'];

        if (
            bccomp($currentRate, $minimumProjectRate, 1) >= 0
            && bccomp($currentRate, $bid->getAutobid()->getRateMin(), 1) >= 0
        ) {
            if (null === $bidOrder) {
                $bidOrder = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Bids')->countBy(['idProject' => $bid->getProject()->getIdProject()]);
                $bidOrder++;
            }

            $bid->setStatus(Bids::STATUS_REJECTED);

            $newBid = clone $bid;
            $newBid
                ->setRate($currentRate)
                ->setOrdre($bidOrder)
                ->setStatus(Bids::STATUS_PENDING)
                ->setAdded(new \DateTime('NOW'));

            $this->entityManager->persist($newBid);
            $this->entityManager->flush([$bid, $newBid]);

            return $newBid;
        } else {
            $this->reject($bid, $sendNotification);
        }

        return $bid;
    }

    /**
     * @param Bids  $bid
     * @param float $amount
     *
     * @return WalletBalanceHistory
     * @throws OptimisticLockException
     * @throws \Exception
     */
    private function creditRejectedBid(Bids $bid, float $amount): WalletBalanceHistory
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
     * @throws OptimisticLockException
     */
    private function notificationRejection(Bids $bid, WalletBalanceHistory $walletBalanceHistory): void
    {
        if (WalletType::LENDER === $bid->getIdLenderAccount()->getIdType()->getLabel()) {
            $this->notificationManager->create(
                Notifications::TYPE_BID_REJECTED,
                $bid->getAutobid() !== null ? ClientsGestionTypeNotif::TYPE_AUTOBID_ACCEPTED_REJECTED_BID : ClientsGestionTypeNotif::TYPE_BID_REJECTED,
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
     * @param Projects|\projects $project
     *
     * @return array
     */
    public function getProjectRateRange($project): array
    {
        if ($project instanceof \projects) {
            $project = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($project->id_project);
        }

        try {
            $cachedItem = $this->cachePool->getItem(CacheKeys::PROJECT_RATE_RANGE . '_' . $project->getIdProject());
            $cacheHit   = $cachedItem->isHit();
        } catch (CacheException $exception) {
            $cachedItem = null;
            $cacheHit   = false;
        }

        if ($cacheHit) {
            return $cachedItem->get();
        }

        $projectRateSettings           = null;
        $projectRateSettingsRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectRateSettings');

        if (false === empty($project->getIdRate())) {
            $projectRateSettings = $projectRateSettingsRepository->find($project->getIdRate());
        }

        if (null === $projectRateSettings) {
            $projectRateRange = $projectRateSettingsRepository->getGlobalMinMaxRate();
        } else {
            $projectRateRange = [
                'rate_min' => $projectRateSettings->getRateMin(),
                'rate_max' => $projectRateSettings->getRateMax()
            ];
        }

        if (false === empty($cachedItem)) {
            $cachedItem->set($projectRateRange)->expiresAfter(CacheKeys::SHORT_TIME);
            $this->cachePool->save($cachedItem);
        }

        return $projectRateRange;
    }

    /**
     * @param Bids       $bid
     * @param float|null $acceptedAmount
     *
     * @throws OptimisticLockException
     * @throws \Exception
     */
    public function accept(Bids $bid, ?float $acceptedAmount): void
    {
        $bid->setStatus(Bids::STATUS_ACCEPTED);
        $acceptedAmount = null == $acceptedAmount ? $bid->getAmount() : bcmul($acceptedAmount, 100);

        $acceptedBid = new AcceptedBids();
        $acceptedBid
            ->setIdBid($bid)
            ->setAmount($acceptedAmount);

        $this->entityManager->persist($acceptedBid);
        $this->entityManager->flush([$bid, $acceptedBid]);

        if ($acceptedAmount < $bid->getAmount()) {
            $rejectedAmount       = bcsub($bid->getAmount(), $acceptedAmount);
            $walletBalanceHistory = $this->creditRejectedBid($bid, $rejectedAmount / 100);
            $this->notificationRejection($bid, $walletBalanceHistory);
        }

        if (null !== $this->entityManager->getRepository('UnilendCoreBusinessBundle:Sponsorship')->findOneBy(['idClientSponsee' => $bid->getIdLenderAccount()->getIdClient(), 'status' => Sponsorship::STATUS_SPONSEE_PAID])) {
            try {
                $this->sponsorshipManager->attributeSponsorReward($bid->getIdLenderAccount()->getIdClient());
            } catch (\Exception $exception) {
                $this->logger->info('Sponsor reward could not be attributed for bid ' . $bid->getIdBid() . '. Reason: ' . $exception->getMessage(), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $bid->getProject()->getIdProject()]);
            }
        }
    }

}
