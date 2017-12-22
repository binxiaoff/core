<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{
    Bids, Notifications, ProjectsStatus, Sponsorship, TaxType, UnderlyingContractAttributeType, Users, WalletType
};
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\ContractAttributeManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
use Unilend\core\Loader;

class ProjectLifecycleManager
{
    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;
    /** @var EntityManager */
    private $entityManager;
    /** @var BidManager */
    private $bidManager;
    /** @var LoanManager */
    private $loanManager;
    /** @var NotificationManager */
    private $notificationManager;
    /** @var MailerManager */
    private $mailerManager;
    /** @var ProjectRateSettingsManager */
    private $projectRateSettingsManager;
    /** @var ProductManager */
    private $productManager;
    /** @var ContractAttributeManager */
    private $contractAttributeManager;
    /** @var \dates */
    private $datesManager;
    /** @var \jours_ouvres */
    private $workingDay;
    /** @var LoggerInterface */
    private $logger;
    /** @var SponsorshipManager */
    private $sponsorshipManager;
    /** @var ProjectStatusManager */
    private $projectStatusManager;
    /** @var ProjectManager */
    private $projectManager;

    public function __construct(
        EntityManagerSimulator $entityManagerSimulator,
        EntityManager $entityManager,
        BidManager $bidManager,
        LoanManager $loanManager,
        NotificationManager $notificationManager,
        MailerManager $mailerManager,
        ProjectRateSettingsManager $projectRateSettingsManager,
        ProductManager $productManager,
        ContractAttributeManager $contractAttributeManager,
        SponsorshipManager $sponsorshipManager,
        ProjectStatusManager $projectStatusManager,
        ProjectManager $projectManager
    )
    {
        $this->entityManagerSimulator     = $entityManagerSimulator;
        $this->entityManager              = $entityManager;
        $this->bidManager                 = $bidManager;
        $this->loanManager                = $loanManager;
        $this->notificationManager        = $notificationManager;
        $this->mailerManager              = $mailerManager;
        $this->projectRateSettingsManager = $projectRateSettingsManager;
        $this->productManager             = $productManager;
        $this->contractAttributeManager   = $contractAttributeManager;
        $this->sponsorshipManager         = $sponsorshipManager;
        $this->projectStatusManager       = $projectStatusManager;
        $this->projectManager             = $projectManager;

        $this->datesManager = Loader::loadLib('dates');
        $this->workingDay   = Loader::loadLib('jours_ouvres');
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param \projects $project
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function prePublish(\projects $project)
    {
        $this->autoBid($project);

        if ($this->projectManager->isFunded($project)) {
            $this->markAsFunded($project);
        }

        $this->reBidAutoBidDeeply($project, BidManager::MODE_REBID_AUTO_BID_CREATE, false);
        $this->projectStatusManager->addProjectStatus(Users::USER_ID_CRON, \projects_status::AUTO_BID_PLACED, $project);
    }

    /**
     * @param \projects $project
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function publish(\projects $project)
    {
        /** @var \bids $bidData */
        $bidData = $this->entityManagerSimulator->getRepository('bids');

        $offset = 0;
        $limit  = 100;

        while ($bids = $bidData->getFirstProjectBidsByLender($project->id_project, $limit, $offset)) {
            foreach ($bids as $bid) {
                $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->find($bid['id_lender_account']);

                if (null !== $wallet && WalletType::LENDER === $wallet->getIdType()->getLabel()) {
                    if ($bid['min_status'] == \bids::STATUS_BID_PENDING) {
                        $notificationType = Notifications::TYPE_BID_PLACED;
                        $mailType         = \clients_gestion_type_notif::TYPE_BID_PLACED;
                        $mailFunction     = 'sendBidConfirmation';
                    } else {
                        $notificationType = Notifications::TYPE_BID_REJECTED;
                        $mailType         = \clients_gestion_type_notif::TYPE_BID_REJECTED;
                        $mailFunction     = 'sendBidRejected';
                    }

                    if ($bid['id_autobid'] > 0) {
                        $mailType = \clients_gestion_type_notif::TYPE_AUTOBID_ACCEPTED_REJECTED_BID;
                    }

                    $this->notificationManager->create(
                        $notificationType,
                        $mailType,
                        $wallet->getIdClient()->getIdClient(),
                        $mailFunction,
                        $project->id_project,
                        $bid['amount'] / 100,
                        $bid['id_bid']
                    );
                }
            }

            $offset += $limit;
        }

        $this->projectStatusManager->addProjectStatus(Users::USER_ID_CRON, ProjectsStatus::EN_FUNDING, $project);
    }

    /**
     * @param \projects $project
     * @param bool      $sendNotification
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function checkBids(\projects $project, bool $sendNotification)
    {
        /** @var \bids $legacyBid */
        $legacyBid = $this->entityManagerSimulator->getRepository('bids');
        /** @var \bids_logs $oBidLog */
        $oBidLog       = $this->entityManagerSimulator->getRepository('bids_logs');
        $bidRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Bids');

        $aLogContext      = [];
        $bBidsLogs        = false;
        $iRejectedBids    = 0;
        $iBidsAccumulated = 0;
        $iBorrowAmount    = $project->amount;
        $iBidTotal        = $legacyBid->getSoldeBid($project->id_project);

        $oBidLog->debut = date('Y-m-d H:i:s');

        if ($iBidTotal >= $iBorrowAmount) {
            $bids = $bidRepository->findBy(['idProject' => $project->id_project, 'status' => Bids::STATUS_BID_PENDING], ['rate' => 'ASC', 'ordre' => 'ASC']);
            foreach ($bids as $bid) {
                if ($iBidsAccumulated < $iBorrowAmount) {
                    $iBidsAccumulated = bcadd($iBidsAccumulated, round(bcdiv($bid->getAmount(), 100, 4), 2), 2);
                } else {
                    $bBidsLogs = true;
                    if (null === $bid->getAutobid()) { // non-auto-bid
                        $this->bidManager->reject($bid, $sendNotification);
                    } else {
                        // For a autobid, we don't send reject notification, we don't create payback transaction, either. So we just flag it here as reject temporarily
                        $bid->setStatus(Bids::STATUS_AUTOBID_REJECTED_TEMPORARILY);
                        $this->entityManager->flush($bid);
                    }

                    $iRejectedBids++;
                }
            }

            $aLogContext['Project ID']    = $project->id_project;
            $aLogContext['Balance']       = $iBidTotal;
            $aLogContext['Rejected bids'] = $iRejectedBids;
        }

        if ($bBidsLogs == true) {
            $oBidLog->id_project      = $project->id_project;
            $oBidLog->nb_bids_encours = $bidRepository->countBy(['idProject' => $project->id_project, 'status' => Bids::STATUS_BID_PENDING]);
            $oBidLog->nb_bids_ko      = $iRejectedBids;
            $oBidLog->total_bids      = $bidRepository->countBy(['idProject' => $project->id_project]);
            $oBidLog->total_bids_ko   = $bidRepository->countBy(['idProject' => $project->id_project, 'status' => Bids::STATUS_BID_REJECTED]);
            $oBidLog->rate_max        = $legacyBid->getProjectMaxRate($project);
            $oBidLog->fin             = date('Y-m-d H:i:s');
            $oBidLog->create();
        }

        if ($this->logger instanceof LoggerInterface) {
            $this->logger->info(
                'Check bid info: ' . var_export($aLogContext, true) . ' (project ' . $project->id_project . ')',
                ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project]
            );
        }
    }

    /**
     * @param \projects $project
     */
    public function autoBid(\projects $project)
    {
        if ($project->status == \projects_status::A_FUNDER) {
            $this->bidAllAutoBid($project);
        } elseif ($project->status == \projects_status::EN_FUNDING) {
            $this->reBidAutoBid($project, BidManager::MODE_REBID_AUTO_BID_CREATE, true);
        }
    }

    /**
     * @param \projects $project
     */
    private function bidAllAutoBid(\projects $project)
    {
        /** @var \autobid $oAutoBid */
        $oAutoBid = $this->entityManagerSimulator->getRepository('autobid');
        /** @var \project_period $projectPeriods */
        $projectPeriods = $this->entityManagerSimulator->getRepository('project_period');
        $autobidRepo    = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Autobid');
        $projectEntity  = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($project->id_project);

        if ($projectPeriods->getPeriod($project->period)) {
            $rateRange = $this->bidManager->getProjectRateRange($project);
            $iOffset   = 0;
            $iLimit    = 100;
            while ($aAutoBidList = $oAutoBid->getSettings(null, $project->risk, $projectPeriods->id_period, array(\autobid::STATUS_ACTIVE), ['id_autobid' => 'ASC'], $iLimit, $iOffset)) {
                $iOffset += $iLimit;
                foreach ($aAutoBidList as $aAutoBidSetting) {
                    $autobid = $autobidRepo->find($aAutoBidSetting['id_autobid']);
                    if ($autobid) {
                        try {
                            $this->bidManager->bidByAutoBidSettings($autobid, $projectEntity, $rateRange['rate_max'], false);
                        } catch (\Exception $exception) {
                            continue;
                        }
                    }
                }
            }

            /** @var \bids $oBid */
            $oBid = $this->entityManagerSimulator->getRepository('bids');
            $oBid->shuffleAutoBidOrder($project->id_project);
        }
    }

    /**
     * @param \projects $project
     * @param int       $mode
     * @param bool      $sendNotification
     */
    private function reBidAutoBid(\projects $project, int $mode, bool $sendNotification)
    {
        /** @var \settings $oSettings */
        $oSettings = $this->entityManagerSimulator->getRepository('settings');
        /** @var \bids $legacyBid */
        $legacyBid     = $this->entityManagerSimulator->getRepository('bids');
        $bidRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Bids');

        $oSettings->get('Auto-bid step', 'type');
        $fStep       = (float) $oSettings->value;
        $currentRate = bcsub($legacyBid->getProjectMaxRate($project), $fStep, 1);

        while ($aAutoBidList = $legacyBid->getAutoBids($project->id_project, \bids::STATUS_AUTOBID_REJECTED_TEMPORARILY)) {
            foreach ($aAutoBidList as $aAutobid) {
                $bid = $bidRepository->find($aAutobid['id_bid']);
                if ($bid) {
                    $this->bidManager->reBidAutoBidOrReject($bid, $currentRate, $mode, $sendNotification);
                }
            }
        }
    }

    /**
     * @param \projects $project
     * @param int       $mode
     * @param bool      $sendNotification
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function reBidAutoBidDeeply(\projects $project, int $mode, bool $sendNotification)
    {
        /** @var \bids $oBid */
        $oBid = $this->entityManagerSimulator->getRepository('bids');
        $this->checkBids($project, $sendNotification);
        $aRefusedAutoBid = $oBid->getAutoBids($project->id_project, \bids::STATUS_AUTOBID_REJECTED_TEMPORARILY, 1);
        if (false === empty($aRefusedAutoBid)) {
            $this->reBidAutoBid($project, $mode, $sendNotification);
            $this->reBidAutoBidDeeply($project, $mode, $sendNotification);
        }
    }

    /**
     * @param \projects $project
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function buildLoans(\projects $project)
    {
        $bidRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Bids');

        $this->projectStatusManager->addProjectStatus(Users::USER_ID_CRON, \projects_status::BID_TERMINATED, $project);
        $this->reBidAutoBidDeeply($project, BidManager::MODE_REBID_AUTO_BID_CREATE, true);
        $this->projectStatusManager->addProjectStatus(Users::USER_ID_CRON, \projects_status::FUNDE, $project);

        if ($this->logger instanceof LoggerInterface) {
            $this->logger->info('Project ' . $project->id_project . ' is now changed to status funded', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project]);
        }

        $criteria     = ['idProject' => $project->id_project, 'status' => Bids::STATUS_BID_PENDING];
        $bids         = $bidRepository->findBy($criteria, ['rate' => 'ASC', 'ordre' => 'ASC']);
        $iBidNbTotal  = $bidRepository->countBy($criteria);
        $iBidBalance  = 0;
        $treatedBidNb = 0;

        if ($this->logger instanceof LoggerInterface) {
            $this->logger->info($iBidNbTotal . ' bids created (project ' . $project->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project]);
        }

        foreach ($bids as $bid) {
            if ($bid) {
                if ($iBidBalance < $project->amount) {
                    $iBidBalance = bcadd($iBidBalance, round(bcdiv($bid->getAmount(), 100, 4), 2), 2);
                    if ($iBidBalance > $project->amount) {
                        $fAmountToCredit = $iBidBalance - $project->amount;
                        $this->bidManager->rejectPartially($bid, $fAmountToCredit);
                    } else {
                        $bid->setStatus(Bids::STATUS_BID_ACCEPTED);
                        $this->entityManager->flush($bid);

                        if (null !== $this->entityManager->getRepository('UnilendCoreBusinessBundle:Sponsorship')->findOneBy(['idClientSponsee' => $bid->getIdLenderAccount()->getIdClient(), 'status' => Sponsorship::STATUS_SPONSEE_PAID])) {
                            try {
                                $this->sponsorshipManager->attributeSponsorReward($bid->getIdLenderAccount()->getIdClient());
                            } catch (\Exception $exception) {
                                $this->logger->info('Sponsor reward could not be attributed for bid ' . $bid->getIdBid() . '. Reason: ' . $exception->getMessage(), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project]);
                            }
                        }
                    }
                } else {
                    $this->bidManager->reject($bid, true);
                }

                $treatedBidNb ++;

                if ($this->logger instanceof LoggerInterface) {
                    $this->logger->info($treatedBidNb . '/' . $iBidNbTotal . ' bids treated (project ' . $project->id_project . ')', ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project]);
                }
            }
        }

        /** @var \product $product */
        $product = $this->entityManagerSimulator->getRepository('product');
        $product->get($project->id_product);

        $contractTypes = array_column($this->productManager->getAvailableContracts($product), 'label');

        if (in_array(\underlying_contract::CONTRACT_IFP, $contractTypes) && in_array(\underlying_contract::CONTRACT_BDC, $contractTypes)) {
            $this->buildLoanIFPAndBDC($project);
        } elseif (in_array(\underlying_contract::CONTRACT_IFP, $contractTypes) && in_array(\underlying_contract::CONTRACT_MINIBON, $contractTypes)) {
            $this->buildLoanIFPAndMinibon($project);
        } elseif (in_array(\underlying_contract::CONTRACT_IFP, $contractTypes)) {
            $this->buildLoanIFP($project);
        }
    }

    /**
     * @param \projects $project
     */
    private function buildLoanIFPAndMinibon(\projects $project)
    {
        $this->buildIFPBasedMixLoan($project, \underlying_contract::CONTRACT_MINIBON);
    }

    /**
     * @param \projects $project
     */
    private function buildLoanIFPAndBDC(\projects $project)
    {
        $this->buildIFPBasedMixLoan($project, \underlying_contract::CONTRACT_BDC);
    }

    /**
     * @param \projects $project
     * @param string    $additionalContract
     */
    private function buildIFPBasedMixLoan(\projects $project, string $additionalContract)
    {
        /** @var \bids $legacyBid */
        $legacyBid = $this->entityManagerSimulator->getRepository('bids');
        /** @var \loans $loan */
        $loan = $this->entityManagerSimulator->getRepository('loans');
        /** @var \underlying_contract $contract */
        $contract = $this->entityManagerSimulator->getRepository('underlying_contract');
        $bidRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Bids');

        $aLenderList = $legacyBid->getLenders($project->id_project, [\bids::STATUS_BID_ACCEPTED]);

        if (false === $contract->get(\underlying_contract::CONTRACT_IFP, 'label')) {
            throw new \InvalidArgumentException('The contract ' . \underlying_contract::CONTRACT_IFP . 'does not exist.');
        }
        $IFPContractId = $contract->id_contract;

        $contractAttrVars = $this->contractAttributeManager->getContractAttributesByType($contract, UnderlyingContractAttributeType::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO);
        if (empty($contractAttrVars) || false === isset($contractAttrVars[0]) || false === is_numeric($contractAttrVars[0])) {
            throw new \UnexpectedValueException('The IFP contract max amount is not set');
        } else {
            $IFPLoanAmountMax = $contractAttrVars[0];
        }

        if (false === $contract->get($additionalContract, 'label')) {
            throw new \InvalidArgumentException('The contract ' . $additionalContract . 'does not exist.');
        }
        $additionalContractId = $contract->id_contract;

        foreach ($aLenderList as $aLender) {
            $wallet     = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->find($aLender['id_lender_account']);
            $lenderBids = $bidRepository->findBy(['idLenderAccount' => $wallet, 'idProject' => $project->id_project, 'status' => Bids::STATUS_BID_ACCEPTED], ['rate' => 'DESC']);

            if ($wallet->getIdClient()->isNaturalPerson()) {
                $fLoansLenderSum = 0;
                $fInterests      = 0;
                $bIFPContract    = true;
                $aBidIFP         = array();

                foreach ($lenderBids as $bid) {
                    $fBidAmount = round(bcdiv($bid->getAmount(), 100, 4), 2);

                    if (true === $bIFPContract && bccomp(bcadd($fLoansLenderSum, $fBidAmount, 2), $IFPLoanAmountMax, 2) <= 0) {
                        $fInterests = bcadd($fInterests, bcmul($bid->getRate(), $fBidAmount, 2), 2);
                        $fLoansLenderSum += $fBidAmount;
                        $aBidIFP[] = array(
                            'bid_id' => $bid->getIdBid(),
                            'amount' => $fBidAmount
                        );
                    } else {
                        // Greater than IFP max amount ? create additional contract loan, split it if needed.
                        $bIFPContract = false;
                        $fDiff        = bcsub(bcadd($fLoansLenderSum, $fBidAmount, 2), $IFPLoanAmountMax, 2);

                        $loan->unsetData();
                        $loan->addAcceptedBid($bid->getIdBid(), $fDiff);
                        $loan->id_lender        = $wallet->getId();
                        $loan->id_project       = $project->id_project;
                        $loan->amount           = $fDiff * 100;
                        $loan->rate             = $bid->getRate();
                        $loan->id_type_contract = $additionalContractId;
                        $this->loanManager->create($loan);

                        $fRest = bcsub($fBidAmount, $fDiff, 2);
                        if (0 < $fRest) {
                            $fInterests = bcadd($fInterests, bcmul($bid->getRate(), $fRest, 2), 2);
                            $aBidIFP[] = array(
                                'bid_id' => $bid->getIdBid(),
                                'amount' => $fRest
                            );
                        }
                        $fLoansLenderSum = $IFPLoanAmountMax;
                    }
                }

                // Create IFP loan from the grouped bids
                $loan->unsetData();
                foreach ($aBidIFP as $aAcceptedBid) {
                    $loan->addAcceptedBid($aAcceptedBid['bid_id'], $aAcceptedBid['amount']);
                }
                $loan->id_lender        = $wallet->getId();
                $loan->id_project       = $project->id_project;
                $loan->amount           = $fLoansLenderSum * 100;
                $loan->rate             = round($fInterests / $fLoansLenderSum, 2);
                $loan->id_type_contract = $IFPContractId;
                $this->loanManager->create($loan);
            } else {
                foreach ($lenderBids as $bid) {
                    $loan->unsetData();
                    $loan->addAcceptedBid($bid->getIdBid(), round(bcdiv($bid->getAmount(), 100, 4), 2));
                    $loan->id_lender        = $wallet->getId();
                    $loan->id_project       = $project->id_project;
                    $loan->amount           = $bid->getAmount();
                    $loan->rate             = $bid->getRate();
                    $loan->id_type_contract = $additionalContractId;
                    $this->loanManager->create($loan);
                }
            }
        }
    }

    /**
     * @param \projects $project
     */
    private function buildLoanIFP(\projects $project)
    {
        /** @var \bids $legacyBid */
        $legacyBid = $this->entityManagerSimulator->getRepository('bids');
        /** @var \loans $loan */
        $loan = $this->entityManagerSimulator->getRepository('loans');
        /** @var \underlying_contract $contract */
        $contract      = $this->entityManagerSimulator->getRepository('underlying_contract');
        $bidRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Bids');

        $aLenderList = $legacyBid->getLenders($project->id_project, [\bids::STATUS_BID_ACCEPTED]);

        if (false === $contract->get(\underlying_contract::CONTRACT_IFP, 'label')) {
            throw new \InvalidArgumentException('The contract ' . \underlying_contract::CONTRACT_IFP . 'does not exist.');
        }
        $IFPContractId = $contract->id_contract;

        $contractAttrVars = $this->contractAttributeManager->getContractAttributesByType($contract, UnderlyingContractAttributeType::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO);
        if (empty($contractAttrVars) || false === isset($contractAttrVars[0]) || false === is_numeric($contractAttrVars[0])) {
            throw new \UnexpectedValueException('The IFP contract max amount is not set');
        } else {
            $IFPLoanAmountMax = $contractAttrVars[0];
        }

        foreach ($aLenderList as $aLender) {
            $wallet     = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->find($aLender['id_lender_account']);
            $lenderBids = $bidRepository->findBy(['idLenderAccount' => $wallet->getId(), 'idProject' => $project->id_project, 'status' => Bids::STATUS_BID_ACCEPTED], ['rate' => 'DESC']);

            if ($wallet->getIdClient()->isNaturalPerson()) {
                $fLoansLenderSum = 0;
                $fInterests      = 0;
                $aBidIFP         = [];

                /** @var Bids $bid */
                foreach ($lenderBids as $bid) {
                    $fBidAmount = round(bcdiv($bid->getAmount(), 100, 4), 2);

                    if (bccomp(bcadd($fLoansLenderSum, $fBidAmount, 2), $IFPLoanAmountMax, 2) <= 0) {
                        $fInterests = bcadd($fInterests, bcmul($bid->getRate(), $fBidAmount, 2), 2);
                        $fLoansLenderSum = bcadd($fLoansLenderSum, $fBidAmount, 2);
                        $aBidIFP[] = [
                            'bid_id' => $bid->getIdBid(),
                            'amount' => $fBidAmount
                        ];
                    } else {
                        $this->bidManager->reject($bid);
                    }
                }

                // Create IFP loan from the grouped bids
                $loan->unsetData();
                foreach ($aBidIFP as $aAcceptedBid) {
                    $loan->addAcceptedBid($aAcceptedBid['bid_id'], $aAcceptedBid['amount']);
                }
                $loan->id_lender        = $wallet->getId();
                $loan->id_project       = $project->id_project;
                $loan->amount           = bcmul($fLoansLenderSum, 100);
                $loan->rate             = round(bcdiv($fInterests, $fLoansLenderSum, 4), 2);
                $loan->id_type_contract = $IFPContractId;
                $this->loanManager->create($loan);
            } else {
                foreach ($lenderBids as $bid) {
                    $this->bidManager->reject($bid);
                }
            }
        }
    }

    /**
     * @param \projects $project
     */
    public function treatFundFailed(\projects $project)
    {
        $bidRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Bids');

        $this->projectStatusManager->addProjectStatus(Users::USER_ID_CRON, \projects_status::FUNDING_KO, $project);

        $criteria      = ['idProject' => $project->id_project];
        $bids          = $bidRepository->findBy($criteria, ['rate' => 'ASC', 'ordre' => 'ASC']);
        $iBidNbTotal   = $bidRepository->countBy($criteria);
        $treatedBidNb = 0;

        if ($this->logger instanceof LoggerInterface) {
            $this->logger->info($iBidNbTotal . 'bids in total (project ' . $project->id_project . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project));
        }

        foreach ($bids as $bid) {
            if ($bid) {
                $this->bidManager->reject($bid, false);
                $treatedBidNb ++;
                if ($this->logger instanceof LoggerInterface) {
                    $this->logger->info($treatedBidNb . '/' . $iBidNbTotal . 'bids treated (project ' . $project->id_project . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project));
                }
            }
        }
    }

    /**
     * @param \projects $project
     *
     * @throws \Exception
     */
    public function createRepaymentSchedule(\projects $project)
    {
        /** @var \product $product */
        $product = $this->entityManagerSimulator->getRepository('product');
        if (! $product->get($project->id_product)) {
            throw new \Exception('Invalid product id ' . $project->id_product . ' found for project id ' . $project->id_project);
        }
        /** @var \repayment_type $repaymentType */
        $repaymentType = $this->entityManagerSimulator->getRepository('repayment_type');
        $repaymentType->get($product->id_repayment_type);

        switch ($repaymentType->label) {
            case \repayment_type::REPAYMENT_TYPE_AMORTIZATION:
                $this->createAmortizationRepaymentSchedule($project);
                return;
            case \repayment_type::REPAYMENT_TYPE_DEFERRED:
                $this->createDeferredRepaymentSchedule($project);
                return;
            default :
                throw new \Exception('Unknown repayment schedule type ' . $repaymentType->label);
        }
    }

    /**
     * @param \projects $project
     */
    private function createAmortizationRepaymentSchedule(\projects $project)
    {
        /** @var \loans $oLoan */
        $oLoan = $this->entityManagerSimulator->getRepository('loans');
        /** @var \echeanciers $oRepaymentSchedule */
        $oRepaymentSchedule = $this->entityManagerSimulator->getRepository('echeanciers');
        /** @var \clients_adresses $oClientAdresse */
        $oClientAdresse = $this->entityManagerSimulator->getRepository('clients_adresses');

        if ($project->status == \projects_status::FUNDE) {
            $lLoans = $oLoan->select('id_project = ' . $project->id_project);

            $iLoanNbTotal   = count($lLoans);
            $iTreatedLoanNb = 0;

            if ($this->logger instanceof LoggerInterface) {
                $this->logger->info($iLoanNbTotal . ' in total (project ' . $project->id_project . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project));
            }

            foreach ($lLoans as $l) {
                $wallet = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->find($l['id_lender']);
                $oClientAdresse->get($wallet->getIdClient()->getIdClient(), 'id_client');
                $oLoan->get($l['id_loan']);

                $aRepaymentSchedule = array();
                foreach ($oLoan->getRepaymentSchedule() as $k => $e) {
                    $dateEcheance = $this->datesManager->dateAddMoisJoursV3($project->date_fin, $k);
                    $dateEcheance = date('Y-m-d H:i', $dateEcheance) . ':00';

                    $dateEcheance_emprunteur = $this->datesManager->dateAddMoisJoursV3($project->date_fin, $k);
                    $dateEcheance_emprunteur = $this->workingDay->display_jours_ouvres($dateEcheance_emprunteur, 6);
                    $dateEcheance_emprunteur = date('Y-m-d H:i', $dateEcheance_emprunteur) . ':00';

                    $aRepaymentSchedule[] = array(
                        'id_lender'                => $l['id_lender'],
                        'id_project'               => $project->id_project,
                        'id_loan'                  => $l['id_loan'],
                        'ordre'                    => $k,
                        'montant'                  => bcmul($e['repayment'], 100),
                        'capital'                  => bcmul($e['capital'], 100),
                        'interets'                 => bcmul($e['interest'], 100),
                        'date_echeance'            => $dateEcheance,
                        'date_echeance_emprunteur' => $dateEcheance_emprunteur,
                        'added'                    => date('Y-m-d H:i:s'),
                        'updated'                  => date('Y-m-d H:i:s')
                    );
                }
                $oRepaymentSchedule->multiInsert($aRepaymentSchedule);

                $iTreatedLoanNb++;

                if ($this->logger instanceof LoggerInterface) {
                    $this->logger->info(
                        $iTreatedLoanNb . '/' . $iLoanNbTotal . ' loans treated. ' . $k . ' repayment schedules created (project ' . $project->id_project . ' : ',
                        array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project)
                    );
                }
            }
        }
    }

    /**
     * @param \projects $project
     */
    private function createDeferredRepaymentSchedule(\projects $project)
    {
        /** @var \loans $loanEntity */
        $loanEntity = $this->entityManagerSimulator->getRepository('loans');
        /** @var \echeanciers $repaymentScheduleEntity */
        $repaymentScheduleEntity = $this->entityManagerSimulator->getRepository('echeanciers');

        if ($project->status == \projects_status::FUNDE) {
            $loans               = $loanEntity->select('id_project = ' . $project->id_project);
            $loansCount          = count($loans);
            $processedLoansCount = 0;

            if ($this->logger instanceof LoggerInterface) {
                $this->logger->info(
                    $loansCount . ' in total (project ' . $project->id_project . ')',
                    ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project]
                );
            }

            foreach ($loans as $loan) {
                $loanEntity->get($loan['id_loan']);

                $repayments = [];
                // @todo raw deferred duration = 12
                foreach ($loanEntity->getDeferredRepaymentSchedule(12) as $order => $repayment) {
                    $lenderRepaymentDate = $this->datesManager->dateAddMoisJoursV3($project->date_fin, $order);
                    $lenderRepaymentDate = date('Y-m-d H:i:00', $lenderRepaymentDate);

                    $borrowerPaymentDate = $this->datesManager->dateAddMoisJoursV3($project->date_fin, $order);
                    $borrowerPaymentDate = $this->workingDay->display_jours_ouvres($borrowerPaymentDate, 6);
                    $borrowerPaymentDate = date('Y-m-d H:i:00', $borrowerPaymentDate);

                    $repayments[] = [
                        'id_lender'                => $loan['id_lender'],
                        'id_project'               => $project->id_project,
                        'id_loan'                  => $loan['id_loan'],
                        'ordre'                    => $order,
                        'montant'                  => bcmul($repayment['repayment'], 100),
                        'capital'                  => bcmul($repayment['capital'], 100),
                        'interets'                 => bcmul($repayment['interest'], 100),
                        'date_echeance'            => $lenderRepaymentDate,
                        'date_echeance_emprunteur' => $borrowerPaymentDate,
                        'added'                    => date('Y-m-d H:i:s'),
                        'updated'                  => date('Y-m-d H:i:s')
                    ];
                }
                $repaymentScheduleEntity->multiInsert($repayments);

                $processedLoansCount++;

                if ($this->logger instanceof LoggerInterface) {
                    $this->logger->info(
                        $processedLoansCount . '/' . $loansCount . ' loans treated. ' . $order . ' repayment schedules created (project ' . $project->id_project . ' : ',
                        ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project]
                    );
                }
            }
        }
    }

    /**
     * @param \projects $project
     *
     * @throws \Exception
     */
    public function createPaymentSchedule(\projects $project)
    {
        /** @var \product $product */
        $product = $this->entityManagerSimulator->getRepository('product');

        if (false === $product->get($project->id_product)) {
            throw new \Exception('Invalid product id ' . $project->id_product . ' found for project id ' . $project->id_project);
        }

        /** @var \repayment_type $repaymentType */
        $repaymentType = $this->entityManagerSimulator->getRepository('repayment_type');
        $repaymentType->get($product->id_repayment_type);

        switch ($repaymentType->label) {
            case \repayment_type::REPAYMENT_TYPE_AMORTIZATION:
                $this->createAmortizationPaymentSchedule($project);
                break;
            case \repayment_type::REPAYMENT_TYPE_DEFERRED:
                $this->createDeferredPaymentSchedule($project);
                break;
            default:
                throw new \Exception('Unknown repayment schedule type ' . $repaymentType->label);
        }
    }

    /**
     * @param \projects $project
     */
    private function createAmortizationPaymentSchedule(\projects $project)
    {
        /** @var \echeanciers_emprunteur $oPaymentSchedule */
        $oPaymentSchedule = $this->entityManagerSimulator->getRepository('echeanciers_emprunteur');
        /** @var \echeanciers $oRepaymentSchedule */
        $oRepaymentSchedule = $this->entityManagerSimulator->getRepository('echeanciers');
        /** @var \tax_type $taxType */
        $taxType = $this->entityManagerSimulator->getRepository('tax_type');

        $taxRate = $taxType->getTaxRateByCountry('fr');
        $fVAT    = $taxRate[TaxType::TYPE_VAT] / 100;

        $fAmount           = $project->amount;
        $iMonthNb          = $project->period;
        $aCommission       = \repayment::getRepaymentCommission($fAmount, $iMonthNb, round(bcdiv($project->commission_rate_repayment, 100, 4), 2), $fVAT);
        $aPaymentList      = $oRepaymentSchedule->getMonthlyScheduleByProject($project->id_project);
        $iPaymentsNbTotal  = count($aPaymentList);
        $iTreatedPaymentNb = 0;

        if ($this->logger instanceof LoggerInterface) {
            $this->logger->info($iPaymentsNbTotal . ' borrower repayments in total (project ' . $project->id_project . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project));
        }

        foreach ($aPaymentList as $iIndex => $aPayment) {
            $sPaymentDate = $this->datesManager->dateAddMoisJoursV3($project->date_fin, $iIndex);
            $sPaymentDate = $this->workingDay->display_jours_ouvres($sPaymentDate, 6);
            $sPaymentDate = date('Y-m-d H:i', $sPaymentDate) . ':00';

            $oPaymentSchedule->id_project               = $project->id_project;
            $oPaymentSchedule->ordre                    = $iIndex;
            $oPaymentSchedule->montant                  = bcmul($aPayment['montant'], 100);
            $oPaymentSchedule->capital                  = bcmul($aPayment['capital'], 100);
            $oPaymentSchedule->interets                 = bcmul($aPayment['interets'], 100);
            $oPaymentSchedule->commission               = bcmul($aCommission['commission_monthly'], 100);
            $oPaymentSchedule->tva                      = bcmul($aCommission['vat_amount_monthly'], 100);
            $oPaymentSchedule->date_echeance_emprunteur = $sPaymentDate;
            $oPaymentSchedule->create();

            $iTreatedPaymentNb++;

            if ($this->logger instanceof LoggerInterface) {
                $this->logger->info(
                    'Borrower repayment ' . $oPaymentSchedule->id_echeancier_emprunteur . ' created. ' . $iTreatedPaymentNb . '/' . $iPaymentsNbTotal . 'treated (project ' . $project->id_project . ')',
                    array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project)
                );
            }
        }
    }

    /**
     * @param \projects $project
     */
    private function createDeferredPaymentSchedule(\projects $project)
    {
        /** @var \echeanciers_emprunteur $borrowerPaymentSchedule */
        $borrowerPaymentSchedule = $this->entityManagerSimulator->getRepository('echeanciers_emprunteur');
        /** @var \echeanciers $lenderRepaymentSchedule */
        $lenderRepaymentSchedule = $this->entityManagerSimulator->getRepository('echeanciers');
        /** @var \tax_type $taxType */
        $taxType = $this->entityManagerSimulator->getRepository('tax_type');

        $taxRate = $taxType->getTaxRateByCountry('fr');
        $vatRate = $taxRate[TaxType::TYPE_VAT] / 100;

        // @todo raw deferred duration
        $deferredDuration        = 12;
        $amount                  = $project->amount;
        $loanDuration            = $project->period;
        $commission              = \repayment::getDeferredRepaymentCommission($amount, $loanDuration, $deferredDuration, round(bcdiv($project->commission_rate_repayment, 100, 4), 2), $vatRate);
        $lenderRepaymentsSummary = $lenderRepaymentSchedule->getMonthlyScheduleByProject($project->id_project);
        $paymentsCount           = count($lenderRepaymentsSummary);
        $processedPayments       = 0;

        if ($this->logger instanceof LoggerInterface) {
            $this->logger->info(
                $paymentsCount . ' borrower repayments in total (project ' . $project->id_project . ')',
                ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project]
            );
        }

        foreach ($lenderRepaymentsSummary as $order => $lenderRepaymentSummary) {
            $borrowerPaymentSchedule->id_project               = $project->id_project;
            $borrowerPaymentSchedule->ordre                    = $order;
            $borrowerPaymentSchedule->montant                  = bcmul($lenderRepaymentSummary['montant'], 100);
            $borrowerPaymentSchedule->capital                  = bcmul($lenderRepaymentSummary['capital'], 100);
            $borrowerPaymentSchedule->interets                 = bcmul($lenderRepaymentSummary['interets'], 100);
            $borrowerPaymentSchedule->commission               = bcmul($commission['commission_monthly'], 100);
            $borrowerPaymentSchedule->tva                      = bcmul($commission['vat_amount_monthly'], 100);
            $borrowerPaymentSchedule->date_echeance_emprunteur = $lenderRepaymentSummary['date_echeance_emprunteur'];
            $borrowerPaymentSchedule->create();

            $processedPayments++;

            if ($this->logger instanceof LoggerInterface) {
                $this->logger->info(
                    'Borrower repayment ' . $borrowerPaymentSchedule->id_echeancier_emprunteur . ' created. ' . $processedPayments . '/' . $paymentsCount . 'treated (project ' . $project->id_project . ')',
                    ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project]
                );
            }
        }
    }

    /**
     * @param \projects $project
     */
    public function markAsFunded(\projects $project)
    {
        if ($project->date_funded == '0000-00-00 00:00:00') {
            $oFunded    = new \DateTime();
            $oPublished = new \DateTime($project->date_publication);

            if ($oFunded < $oPublished) {
                $oFunded = $oPublished;
            }

            $project->date_funded  = $oFunded->format('Y-m-d H:i:s');
            $project->update();

            $this->mailerManager->sendFundedToStaff($project);
        }
    }

    /**
     * @param \projects $project
     */
    public function saveInterestRate(\projects $project)
    {
        $project->interest_rate = $project->getAverageInterestRate(false);
        $project->update();
    }
}
