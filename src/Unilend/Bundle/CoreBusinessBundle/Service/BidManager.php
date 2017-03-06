<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Unilend\Bundle\CoreBusinessBundle\Entity\Autobid;
use Unilend\Bundle\CoreBusinessBundle\Entity\Bids;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductManager;
use Unilend\core\Loader;
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

    /**
     * @var \dates
     */
    private $oDate;

    /**
     * @var \ficelle
     */
    private $oFicelle;

    /**
     * @var LoggerInterface
     */
    private $oLogger;

    /**
     * @var NotificationManager
     */
    private $oNotificationManager;

    /**
     * @var AutoBidSettingsManager
     */
    private $oAutoBidSettingsManager;

    /**
     * @var LenderManager
     */
    private $oLenderManager;

    /**
     * @var EntityManagerSimulator
     */
    private $oEntityManager;

    /**
     * @var ProductManager
     */
    private $productManager;

    /** @var CIPManager */

    private $cipManager;
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var WalletManager
     */
    private $walletManager;

    public function __construct(
        EntityManagerSimulator $oEntityManager,
        NotificationManager $oNotificationManager,
        AutoBidSettingsManager $oAutoBidSettingsManager,
        LenderManager $oLenderManager,
        ProductManager $productManager,
        CIPManager $cipManager,
        EntityManager $em,
        WalletManager $walletManager
    ) {
        $this->oEntityManager          = $oEntityManager;
        $this->oNotificationManager    = $oNotificationManager;
        $this->oAutoBidSettingsManager = $oAutoBidSettingsManager;
        $this->oLenderManager          = $oLenderManager;
        $this->productManager          = $productManager;
        $this->cipManager              = $cipManager;
        $this->em                      = $em;
        $this->walletManager           = $walletManager;

        $this->oDate    = Loader::loadLib('dates');
        $this->oFicelle = Loader::loadLib('ficelle');
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
     * @param integer      $iLenderId for compatibility
     * @param Projects     $project
     * @param              $amount
     * @param              $rate
     * @param Autobid|null $autobidSetting
     * @param bool         $bSendNotification
     *
     * @return Bids
     * @throws \Exception
     */
    public function bid(Wallet $wallet, $iLenderId, Projects $project, $amount, $rate, Autobid $autobidSetting = null, $bSendNotification = true)
    {
        /** @var \settings $oSettings */
        $oSettings = $this->oEntityManager->getRepository('settings');
        /** @var \lenders_accounts $oLenderAccount */
        $oLenderAccount = $this->oEntityManager->getRepository('lenders_accounts');
        /** @var \transactions $oTransaction */
        $oTransaction = $this->oEntityManager->getRepository('transactions');
        /** @var \offres_bienvenues_details $oWelcomeOfferDetails */
        $oWelcomeOfferDetails = $this->oEntityManager->getRepository('offres_bienvenues_details');
        /** @var \projects $legacyProject */
        $legacyProject = $this->oEntityManager->getRepository('projects');
        /** @var \bids $legacyBid */
        $legacyBid     = $this->oEntityManager->getRepository('bids');

        $oSettings->get('Pret min', 'type');
        $iAmountMin = (int)$oSettings->value;

        $iProjectId      = $project->getIdProject();
        $legacyProject->get($iProjectId);

        $bid = new Bids();
        $bid->setIdLenderAccount($iLenderId)
            ->setProject($project)
            ->setAmount(bcmul($amount, 100))
            ->setRate($rate)
            ->setStatus(Bids::STATUS_BID_PENDING)
            ->setAutobid($autobidSetting);

        $legacyBid->id_lender_account = $iLenderId;
        $legacyBid->id_project        = $iProjectId;
        $legacyBid->amount            = bcmul($amount, 100);
        $legacyBid->rate              = $rate;
        $legacyBid->status            = \bids::STATUS_BID_PENDING;
        if ($autobidSetting instanceof Autobid) {
            $legacyBid->id_autobid = $autobidSetting->getIdAutobid();
        }

        if ($iAmountMin > $amount) {
            if ($this->oLogger instanceof LoggerInterface) {
                $this->oLogger->warning('Amount is less than the min amount for a bid', ['project_id' => $iProjectId, 'lender_id' => $iLenderId, 'amount' => $amount, 'rate' => $rate]);
            }
            throw new \Exception('bids-invalid-amount');
        }

        $projectRates = $this->getProjectRateRange($legacyProject);

        if (bccomp($rate, $projectRates['rate_max'], 1) > 0 || bccomp($rate, $projectRates['rate_min'], 1) < 0) {
            if ($this->oLogger instanceof LoggerInterface) {
                $this->oLogger->warning(
                    'The rate is less than the min rate for a bid',
                    ['project_id' => $iProjectId, 'lender_id' => $iLenderId, 'amount' => $amount, 'rate' => $rate]
                );
            }
            throw new \Exception('bids-invalid-rate');
        }

        if (false === in_array($project->getStatus(), array(\projects_status::A_FUNDER, \projects_status::EN_FUNDING))) {
            if ($this->oLogger instanceof LoggerInterface) {
                $this->oLogger->warning(
                    'Project status is not valid for bidding',
                    ['project_id' => $iProjectId, 'lender_id' => $iLenderId, 'amount' => $amount, 'rate' => $rate, 'project_status' => $project->getStatus()]
                );
            }
            throw new \Exception('bids-invalid-project-status');
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
                    ['project_id' => $iProjectId, 'lender_id' => $iLenderId, 'amount' => $amount, 'rate' => $rate, 'project_ended' => $oEndDate->format('c'), 'now' => $oCurrentDate->format('c')]);
            }
            throw new \Exception('bids-invalid-project-status');
        }

        if (false === $oLenderAccount->get($iLenderId)) {
            if ($this->oLogger instanceof LoggerInterface) {
                $this->oLogger->warning('Cannot get lender', ['project_id' => $iProjectId, 'lender_id' => $iLenderId, 'amount' => $amount, 'rate' => $rate]);
            }
            throw new \Exception('bids-invalid-lender');
        }

        if (false === $this->oLenderManager->canBid($oLenderAccount)) {
            if ($this->oLogger instanceof LoggerInterface) {
                $this->oLogger->warning('lender cannot bid', ['project_id' => $iProjectId, 'lender_id' => $iLenderId, 'amount' => $amount, 'rate' => $rate]);
            }
            throw new \Exception('bids-lender-cannot-bid');
        }

        if (false === $this->productManager->isBidEligible($legacyBid)) {
            if ($this->oLogger instanceof LoggerInterface) {
                $this->oLogger->warning('The Bid is not eligible for the project', ['project_id' => $iProjectId, 'lender_id' => $iLenderId, 'amount' => $amount, 'rate' => $rate]);
            }
            throw new \Exception('bids-not-eligible');
        }

        $iClientId = $oLenderAccount->id_client_owner;
        $iBalance  = $oTransaction->getSolde($iClientId);

        if ($iBalance < $amount) {
            if ($this->oLogger instanceof LoggerInterface) {
                $this->oLogger->warning('lender\'s balance not enough for a bid', ['project_id' => $iProjectId, 'lender_id' => $iLenderId, 'amount' => $amount, 'rate' => $rate, 'balance' => $iBalance]);
            }
            throw new \Exception('bids-low-balance');
        }

        if ($this->cipManager->isCIPValidationNeeded($legacyBid) && false === $this->cipManager->hasValidEvaluation($oLenderAccount)) {
            throw new \Exception('bids-cip-validation-needed');
        }

        $iBidNb = $this->em->getRepository('UnilendCoreBusinessBundle:Bids')->countBy(['idProject' => $iProjectId]);
        $iBidNb ++;
        $bid->setOrdre($iBidNb);
        $this->em->persist($bid);
        $this->walletManager->engageBalance($wallet, $amount, $bid);
        $this->em->flush($bid);
        // Liste des offres non utilisées
        $aAllOffers = $oWelcomeOfferDetails->select('id_client = ' . $iClientId . ' AND status = 0');
        if ($aAllOffers != false) {
            $iOfferTotal = 0;
            foreach ($aAllOffers as $aOffer) {
                if ($iOfferTotal <= $amount) {
                    $iOfferTotal += ($aOffer['montant'] / 100); // total des offres

                    $oWelcomeOfferDetails->get($aOffer['id_offre_bienvenue_detail'], 'id_offre_bienvenue_detail');
                    $oWelcomeOfferDetails->status = \offres_bienvenues_details::STATUS_USED;
                    $oWelcomeOfferDetails->id_bid = $bid->getIdBid();
                    $oWelcomeOfferDetails->update();

                    // Apres addition de la derniere offre on se rend compte que le total depasse
                    if ($iOfferTotal > $amount) {
                        // On fait la diff et on créer un remb du trop plein d'offres
                        $iAmountRepayment = $iOfferTotal - $amount;
                        $oWelcomeOfferDetails->unsetData();
                        $oWelcomeOfferDetails->id_offre_bienvenue = 0;
                        $oWelcomeOfferDetails->id_client          = $iClientId;
                        $oWelcomeOfferDetails->id_bid             = 0;
                        $oWelcomeOfferDetails->id_bid_remb        = $bid->getIdBid();
                        $oWelcomeOfferDetails->status             = \offres_bienvenues_details::STATUS_NEW;
                        $oWelcomeOfferDetails->type               = \offres_bienvenues_details::TYPE_CUT;
                        $oWelcomeOfferDetails->montant            = $iAmountRepayment * 100;
                        $oWelcomeOfferDetails->create();
                    }
                } else {
                    break;
                }
            }
        }

        if ($bSendNotification) {
            $this->oNotificationManager->create(
                \notifications::TYPE_BID_PLACED,
                $bid->getAutobid() !== null ? \clients_gestion_type_notif::TYPE_AUTOBID_ACCEPTED_REJECTED_BID : \clients_gestion_type_notif::TYPE_BID_PLACED,
                $iClientId,
                'sendBidConfirmation',
                $iProjectId,
                $amount,
                $bid->getIdBid(),
                $oTransaction->id_transaction
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
        if (bccomp($autoBid->getRateMin(), $rate, 1) <= 0) {
            /** @var \lenders_accounts $LenderAccount */
            $oLenderAccount = $this->oEntityManager->getRepository('lenders_accounts');
            if (
                $oLenderAccount->get($autoBid->getIdLender())
                && $this->oAutoBidSettingsManager->isOn($oLenderAccount)
                && $this->oAutoBidSettingsManager->isQualified($oLenderAccount)
            ) {
                $walletMatching = $this->em->getRepository('UnilendCoreBusinessBundle:AccountMatching')->findOneBy(['idLenderAccount' => $autoBid->getIdLender()]);
                $wallet         = $walletMatching->getIdWallet();
                return $this->bid($wallet, $autoBid->getIdLender(), $project, $autoBid->getAmount(), $rate, $autoBid, $sendNotification);
            }
        }

        return false;
    }

    /**
     * @param Bids $bid
     * @param bool  $sendNotification
     */
    public function reject(Bids $bid, $sendNotification = true)
    {
        if ($bid->getStatus() == Bids::STATUS_BID_PENDING || $bid->getStatus() == Bids::STATUS_AUTOBID_REJECTED_TEMPORARILY) {
            $oTransaction = $this->creditRejectedBid($bid, $bid->getAmount() / 100);

            if ($sendNotification) {
                $this->notificationRejection($bid, $oTransaction);
            }

            $bid->setStatus(Bids::STATUS_BID_REJECTED);
            $this->em->flush($bid);
        }
    }

    /**
     * @param Bids    $bid
     * @param float   $fRepaymentAmount
     */
    public function rejectPartially(Bids $bid, $fRepaymentAmount)
    {
        if ($bid->getStatus() == \bids::STATUS_BID_PENDING || $bid->getStatus() == \bids::STATUS_AUTOBID_REJECTED_TEMPORARILY) {
            $oTransaction = $this->creditRejectedBid($bid, $fRepaymentAmount);
            $this->notificationRejection($bid, $oTransaction);
            // Save new amount of the bid after repayment
            $amount = bcsub($bid->getAmount(), bcmul($fRepaymentAmount, 100));
            $bid->setAmount($amount)
                ->setStatus(Bids::STATUS_BID_ACCEPTED);
            $this->em->flush($bid);
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
        /** @var \lenders_accounts $oLenderAccount */
        $oLenderAccount = $this->oEntityManager->getRepository('lenders_accounts');
        /** @var \clients $oClient */
        $oClient = $this->oEntityManager->getRepository('clients');
        /** @var \projects $project */
        $project = $this->oEntityManager->getRepository('projects');

        $autobid = $bid->getAutobid();
        if ($autobid instanceof Autobid && false === empty($bid->getIdBid()) && $project->get($bid->getProject()->getIdProject())) {
            if (
                bccomp($currentRate, $this->getProjectRateRange($project)['rate_min'], 1) >= 0
                && bccomp($currentRate, $autobid->getRateMin(), 1) >= 0
                && $oLenderAccount->get($bid->getIdLenderAccount())
                && $oClient->get($oLenderAccount->id_client_owner)
                && $oClient->status == \clients::STATUS_ONLINE
            ) { //check online/offline instead of LenderManager::canBid() because of the performance issue.
                if (self::MODE_REBID_AUTO_BID_CREATE === $iMode) {
                    $iBidOrder = $this->em->getRepository('UnilendCoreBusinessBundle:Bids')->countBy(['idProject' => $bid->getProject()->getIdProject()]);
                    $iBidOrder ++;
                    $newBid = clone $bid;
                    $newBid->setOrdre($iBidOrder)
                           ->setRate($currentRate)
                           ->setStatus(Bids::STATUS_BID_PENDING);
                    $this->em->persist($newBid);
                    $bid->setStatus(Bids::STATUS_BID_REJECTED);
                    $this->em->flush($newBid);
                } else {
                    $bid->setRate($currentRate)
                        ->setStatus(Bids::STATUS_BID_PENDING);
                }
                $this->em->flush($bid);
            } else {
                $this->reject($bid, $bSendNotification);
            }
        }
    }

    /**
     * @param Bids  $bid
     * @param float $fAmount
     *
     * @return \transactions
     */
    private function creditRejectedBid(Bids $bid, $fAmount)
    {
        /** @var \lenders_accounts $oLenderAccount */
        $oLenderAccount = $this->oEntityManager->getRepository('lenders_accounts');
        /** @var \offres_bienvenues_details $oWelcomeOfferDetails */
        $oWelcomeOfferDetails = $this->oEntityManager->getRepository('offres_bienvenues_details');
        // Loaded for class constants
        $this->oEntityManager->getRepository('transactions_types');

        $walletMatching = $this->em->getRepository('UnilendCoreBusinessBundle:AccountMatching')->findOneBy(['idLenderAccount' => $bid->getIdLenderAccount()]);
        $wallet = $walletMatching->getIdWallet();
        $oTransaction = $this->walletManager->releaseBalance($wallet, $fAmount, $bid);

        $oLenderAccount->get($bid->getIdLenderAccount(), 'id_lender_account');
        $fAmountX100 = $fAmount * 100;

        $iWelcomeOfferTotal = $oWelcomeOfferDetails->sum('id_client = ' . $oLenderAccount->id_client_owner . ' AND id_bid = ' . $bid->getIdBid(), 'montant');
        if ($iWelcomeOfferTotal > 0) {
            if ($bid->getAmount() === $fAmountX100) { //Totally credit
                $oWelcomeOfferDetails->montant = min($iWelcomeOfferTotal, $fAmountX100);
            } elseif (($bid->getAmount() - $fAmountX100) <= $iWelcomeOfferTotal) { //Partially credit
                $oWelcomeOfferDetails->montant = $iWelcomeOfferTotal - ($bid->getAmount() - $fAmountX100);
            }

            if (false === empty($oWelcomeOfferDetails->montant)) {
                $oWelcomeOfferDetails->id_offre_bienvenue = 0;
                $oWelcomeOfferDetails->id_client          = $oLenderAccount->id_client_owner;
                $oWelcomeOfferDetails->id_bid             = 0;
                $oWelcomeOfferDetails->id_bid_remb        = $bid->getIdBid();
                $oWelcomeOfferDetails->status             = \offres_bienvenues_details::STATUS_NEW;
                $oWelcomeOfferDetails->type               = \offres_bienvenues_details::TYPE_PAYBACK;
                $oWelcomeOfferDetails->create();
            }
        }

        return $oTransaction;

    }

    /**
     * @param Bids          $bid
     * @param \transactions $oTransaction
     */
    private function notificationRejection(Bids $bid, \transactions $oTransaction)
    {
        /** @var \lenders_accounts $oLenderAccount */
        $oLenderAccount = $this->oEntityManager->getRepository('lenders_accounts');
        if ($oLenderAccount->get($bid->getIdLenderAccount())) {
            $this->oNotificationManager->create(
                \notifications::TYPE_BID_REJECTED,
                $bid->getAutobid() !== null ? \clients_gestion_type_notif::TYPE_AUTOBID_ACCEPTED_REJECTED_BID : \clients_gestion_type_notif::TYPE_BID_REJECTED,
                $oLenderAccount->id_client_owner,
                'sendBidRejected',
                $bid->getProject()->getIdProject(),
                $oTransaction->montant / 100,
                $bid->getIdBid(),
                $oTransaction->id_transaction
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
        $projectRateSettings = $this->oEntityManager->getRepository('project_rate_settings');

        if (false === empty($project->id_rate) && $projectRateSettings->get($project->id_rate)) {
            return ['rate_min' => (float) $projectRateSettings->rate_min, 'rate_max' => (float) $projectRateSettings->rate_max];
        }

        return $projectRateSettings->getGlobalMinMaxRate();
    }
}
