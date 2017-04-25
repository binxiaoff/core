<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Bids;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ContractAttributeManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductManager;
use Unilend\core\Loader;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
use PhpXmlRpc\Client as soapClient;
use PhpXmlRpc\Request as soapRequest;
use PhpXmlRpc\Value as documentId;

class ProjectManager
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
    /** @var SlackManager */
    private $slackManager;
    /** @var string */
    private $universignUrl;
    /** @var \dates */
    private $datesManager;
    /** @var \jours_ouvres */
    private $workingDay;
    /** @var LoggerInterface */
    private $logger;

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
        SlackManager $slackManager,
        $universignUrl
    ) {
        $this->entityManagerSimulator     = $entityManagerSimulator;
        $this->entityManager              = $entityManager;
        $this->bidManager                 = $bidManager;
        $this->loanManager                = $loanManager;
        $this->notificationManager        = $notificationManager;
        $this->mailerManager              = $mailerManager;
        $this->projectRateSettingsManager = $projectRateSettingsManager;
        $this->productManager             = $productManager;
        $this->contractAttributeManager   = $contractAttributeManager;
        $this->slackManager               = $slackManager;
        $this->universignUrl              = $universignUrl;

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

    public function prePublish(\projects $oProject)
    {
        $this->autoBid($oProject);

        if ($this->isFunded($oProject)) {
            $this->markAsFunded($oProject);
        }

        $this->reBidAutoBidDeeply($oProject, BidManager::MODE_REBID_AUTO_BID_CREATE, false);
        $this->addProjectStatus(Users::USER_ID_CRON, \projects_status::AUTO_BID_PLACED, $oProject);
    }

    /**
     * @param \projects $project
     */
    public function publish(\projects $project)
    {
        /** @var \bids $bidData */
        $bidData = $this->entityManagerSimulator->getRepository('bids');
        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount = $this->entityManagerSimulator->getRepository('lenders_accounts');

        $offset = 0;
        $limit  = 100;

        while ($bids = $bidData->getLastProjectBidsByLender($project->id_project, $limit, $offset)) {
            foreach ($bids as $bid) {
                if ($lenderAccount->get($bid['id_lender_account'])) {
                    $this->notificationManager->create(
                        $bid['status'] == \bids::STATUS_BID_PENDING ? \notifications::TYPE_BID_PLACED : \notifications::TYPE_BID_REJECTED,
                        $bid['id_autobid'] > 0 ? \clients_gestion_type_notif::TYPE_AUTOBID_ACCEPTED_REJECTED_BID : ($bid['status'] == \bids::STATUS_BID_PENDING ? \clients_gestion_type_notif::TYPE_BID_PLACED : \clients_gestion_type_notif::TYPE_BID_REJECTED),
                        $lenderAccount->id_client_owner,
                        $bid['status'] == \bids::STATUS_BID_PENDING ? 'sendBidConfirmation' : 'sendBidRejected',
                        $project->id_project,
                        $bid['amount'] / 100,
                        $bid['id_bid']
                    );
                }
            }

            $offset += $limit;
        }

        $this->addProjectStatus(Users::USER_ID_CRON, \projects_status::EN_FUNDING, $project);
    }

    public function checkBids(\projects $oProject, $bSendNotification)
    {
        /** @var \bids $legacyBid */
        $legacyBid = $this->entityManagerSimulator->getRepository('bids');
        /** @var \bids_logs $oBidLog */
        $oBidLog = $this->entityManagerSimulator->getRepository('bids_logs');
        $bidRepo = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Bids');

        $aLogContext      = array();
        $bBidsLogs        = false;
        $iRejectedBids    = 0;
        $iBidsAccumulated = 0;
        $iBorrowAmount    = $oProject->amount;
        $iBidTotal        = $legacyBid->getSoldeBid($oProject->id_project);

        $oBidLog->debut = date('Y-m-d H:i:s');

        if ($iBidTotal >= $iBorrowAmount) {
            $bids = $bidRepo->findBy(['idProject' => $oProject->id_project, 'status' => Bids::STATUS_BID_PENDING], ['rate' => 'ASC', 'ordre' => 'ASC']);
            foreach ($bids as $bid) {
                if ($iBidsAccumulated < $iBorrowAmount) {
                    $iBidsAccumulated = bcadd($iBidsAccumulated, round(bcdiv($bid->getAmount(), 100, 4), 2), 2);
                } else {
                    $bBidsLogs = true;
                    if (null === $bid->getAutobid()) { // non-auto-bid
                        $this->bidManager->reject($bid, $bSendNotification);
                    } else {
                        // For a autobid, we don't send reject notification, we don't create payback transaction, either. So we just flag it here as reject temporarily
                        $bid->setStatus(Bids::STATUS_AUTOBID_REJECTED_TEMPORARILY);
                        $this->entityManager->flush($bid);
                    }

                    $iRejectedBids++;
                }
            }

            $aLogContext['Project ID']    = $oProject->id_project;
            $aLogContext['Balance']       = $iBidTotal;
            $aLogContext['Rejected bids'] = $iRejectedBids;
        }

        if ($bBidsLogs == true) {
            $oBidLog->id_project      = $oProject->id_project;
            $oBidLog->nb_bids_encours = $bidRepo->countBy(['idProject' => $oProject->id_project, 'status' => Bids::STATUS_BID_PENDING]);
            $oBidLog->nb_bids_ko      = $iRejectedBids;
            $oBidLog->total_bids      = $bidRepo->countBy(['idProject' => $oProject->id_project]);
            $oBidLog->total_bids_ko   = $bidRepo->countBy(['idProject' => $oProject->id_project, 'status' => Bids::STATUS_BID_REJECTED]);
            $oBidLog->rate_max        = $legacyBid->getProjectMaxRate($oProject);
            $oBidLog->fin             = date('Y-m-d H:i:s');
            $oBidLog->create();
        }

        if ($this->logger instanceof LoggerInterface) {
            $this->logger->info('Check bid info: ' . var_export($aLogContext) . ' (project ' . $oProject->id_project . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project));
        }
    }

    /**
     * @param \projects $oProject
     */
    public function autoBid(\projects $oProject)
    {
        if ($oProject->status == \projects_status::A_FUNDER) {
            $this->bidAllAutoBid($oProject);
        } elseif ($oProject->status == \projects_status::EN_FUNDING) {
            $this->reBidAutoBid($oProject, BidManager::MODE_REBID_AUTO_BID_CREATE, true);
        }
    }

    private function bidAllAutoBid(\projects $oProject)
    {
        /** @var \autobid $oAutoBid */
        $oAutoBid = $this->entityManagerSimulator->getRepository('autobid');
        /** @var \project_period $oProjectPeriods */
        $oProjectPeriods = $this->entityManagerSimulator->getRepository('project_period');
        $autobidRepo     = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Autobid');
        $project         = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($oProject->id_project);

        if ($oProjectPeriods->getPeriod($oProject->period)) {
            $rateRange = $this->bidManager->getProjectRateRange($oProject);

            $iOffset = 0;
            $iLimit  = 100;
            while ($aAutoBidList = $oAutoBid->getSettings(null, $oProject->risk, $oProjectPeriods->id_period, array(\autobid::STATUS_ACTIVE), ['id_autobid' => 'ASC'], $iLimit, $iOffset)) {
                $iOffset += $iLimit;
                $this->entityManager->getConnection()->beginTransaction();
                try {
                    foreach ($aAutoBidList as $aAutoBidSetting) {
                        $autobid = $autobidRepo->find($aAutoBidSetting['id_autobid']);
                        if ($autobid) {
                            try {
                                $this->bidManager->bidByAutoBidSettings($autobid, $project, $rateRange['rate_max'], false);
                            } catch (\Exception $exception) {
                                continue;
                            }
                        }
                    }
                    $this->entityManager->flush();
                    $this->entityManager->getConnection()->commit();
                } catch (\Exception $e) {
                    $this->entityManager->getConnection()->rollBack();
                    throw $e;
                }
            }

            /** @var \bids $oBid */
            $oBid = $this->entityManagerSimulator->getRepository('bids');
            $oBid->shuffleAutoBidOrder($oProject->id_project);
        }
    }

    private function reBidAutoBid(\projects $oProject, $iMode, $bSendNotification)
    {
        /** @var \settings $oSettings */
        $oSettings = $this->entityManagerSimulator->getRepository('settings');
        /** @var \bids $legacyBid */
        $legacyBid = $this->entityManagerSimulator->getRepository('bids');
        $bidRepo   = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Bids');

        $oSettings->get('Auto-bid step', 'type');
        $fStep       = (float)$oSettings->value;
        $currentRate = bcsub($legacyBid->getProjectMaxRate($oProject), $fStep, 1);

        while ($aAutoBidList = $legacyBid->getAutoBids($oProject->id_project, \bids::STATUS_AUTOBID_REJECTED_TEMPORARILY)) {
            $this->entityManager->getConnection()->beginTransaction();
            try {
                foreach ($aAutoBidList as $aAutobid) {
                    $bid = $bidRepo->find($aAutobid['id_bid']);
                    if ($bid) {
                        $this->bidManager->reBidAutoBidOrReject($bid, $currentRate, $iMode, $bSendNotification);
                    }
                }
                $this->entityManager->getConnection()->commit();
            } catch (\Exception $e) {
                $this->entityManager->getConnection()->rollBack();
                throw $e;
            }
        }
    }

    private function reBidAutoBidDeeply(\projects $oProject, $iMode, $bSendNotification)
    {
        /** @var \bids $oBid */
        $oBid = $this->entityManagerSimulator->getRepository('bids');
        $this->checkBids($oProject, $bSendNotification);
        $aRefusedAutoBid = $oBid->getAutoBids($oProject->id_project, \bids::STATUS_AUTOBID_REJECTED_TEMPORARILY, 1);
        if (false === empty($aRefusedAutoBid)) {
            $this->reBidAutoBid($oProject, $iMode, $bSendNotification);
            $this->reBidAutoBidDeeply($oProject, $iMode, $bSendNotification);
        }
    }

    public function buildLoans(\projects $oProject)
    {
        $bidRepo = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Bids');

        $this->addProjectStatus(Users::USER_ID_CRON, \projects_status::BID_TERMINATED, $oProject);
        $this->reBidAutoBidDeeply($oProject, BidManager::MODE_REBID_AUTO_BID_CREATE, true);
        $this->addProjectStatus(Users::USER_ID_CRON, \projects_status::FUNDE, $oProject);

        if ($this->logger instanceof LoggerInterface) {
            $this->logger->info('Project ' . $oProject->id_project . ' is now changed to status funded', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project));
        }

        $criteria     = ['idProject' => $oProject->id_project, 'status' => Bids::STATUS_BID_PENDING];
        $bids         = $bidRepo->findBy($criteria, ['rate' => 'ASC', 'ordre' => 'ASC']);
        $iBidNbTotal  = $bidRepo->countBy($criteria);
        $iBidBalance  = 0;
        $treatedBidNb = 0;

        if ($this->logger instanceof LoggerInterface) {
            $this->logger->info($iBidNbTotal . ' bids created (project ' . $oProject->id_project . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project));
        }

        foreach ($bids as $bid) {
            if ($bid) {
                if ($iBidBalance < $oProject->amount) {
                    $iBidBalance = bcadd($iBidBalance, round(bcdiv($bid->getAmount(), 100, 4), 2), 2);
                    if ($iBidBalance > $oProject->amount) {
                        $fAmountToCredit = $iBidBalance - $oProject->amount;
                        $this->bidManager->rejectPartially($bid, $fAmountToCredit);
                    } else {
                        $bid->setStatus(Bids::STATUS_BID_ACCEPTED);
                        $this->entityManager->flush($bid);
                    }

                    if ($this->logger instanceof LoggerInterface) {
                        $this->logger->info('The bid status has been updated to 1' . $bid->getIdBid() . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project));
                    }
                } else {
                    $this->bidManager->reject($bid, true);
                }

                $treatedBidNb ++;

                if ($this->logger instanceof LoggerInterface) {
                    $this->logger->info($treatedBidNb . '/' . $iBidNbTotal . ' bids treated (project ' . $oProject->id_project . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project));
                }
            }
        }

        /** @var \product $product */
        $product = $this->entityManagerSimulator->getRepository('product');
        $product->get($oProject->id_product);

        $contractTypes = array_column($this->productManager->getAvailableContracts($product), 'label');

        if (in_array(\underlying_contract::CONTRACT_IFP, $contractTypes) && in_array(\underlying_contract::CONTRACT_BDC, $contractTypes)) {
            $this->buildLoanIFPAndBDC($oProject);
        } elseif (in_array(\underlying_contract::CONTRACT_IFP, $contractTypes) && in_array(\underlying_contract::CONTRACT_MINIBON, $contractTypes)) {
            $this->buildLoanIFPAndMinibon($oProject);
        } elseif (in_array(\underlying_contract::CONTRACT_IFP, $contractTypes)) {
            $this->buildLoanIFP($oProject);
        }
    }

    private function buildLoanIFPAndMinibon($project)
    {
        $this->buildIFPBasedMixLoan($project, \underlying_contract::CONTRACT_MINIBON);
    }

    private function buildLoanIFPAndBDC($project) {
        $this->buildIFPBasedMixLoan($project, \underlying_contract::CONTRACT_BDC);
    }

    private function buildIFPBasedMixLoan($project, $additionalContract)
    {
        /** @var \bids $legacyBid */
        $legacyBid = $this->entityManagerSimulator->getRepository('bids');
        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount = $this->entityManagerSimulator->getRepository('lenders_accounts');
        /** @var \loans $loan */
        $loan = $this->entityManagerSimulator->getRepository('loans');
        /** @var \underlying_contract $contract */
        $contract = $this->entityManagerSimulator->getRepository('underlying_contract');
        $bidRepo  = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Bids');

        $aLenderList = $legacyBid->getLenders($project->id_project, array(\bids::STATUS_BID_ACCEPTED));

        if (false === $contract->get(\underlying_contract::CONTRACT_IFP, 'label')) {
            throw new \InvalidArgumentException('The contract ' . \underlying_contract::CONTRACT_IFP . 'does not exist.');
        }
        $IFPContractId = $contract->id_contract;

        $contractAttrVars = $this->contractAttributeManager->getContractAttributesByType($contract, \underlying_contract_attribute_type::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO);
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
            $iLenderId  = $aLender['id_lender_account'];
            $lenderBids = $bidRepo->findBy(['idLenderAccount' => $iLenderId, 'idProject' => $project->id_project, 'status' => Bids::STATUS_BID_ACCEPTED], ['rate' => 'DESC']);

            if ($lenderAccount->isNaturalPerson($iLenderId)) {
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
                        $loan->id_lender        = $iLenderId;
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
                $loan->id_lender        = $iLenderId;
                $loan->id_project       = $project->id_project;
                $loan->amount           = $fLoansLenderSum * 100;
                $loan->rate             = round($fInterests / $fLoansLenderSum, 2);
                $loan->id_type_contract = $IFPContractId;
                $this->loanManager->create($loan);
            } else {
                foreach ($lenderBids as $bid) {
                    $loan->unsetData();
                    $loan->addAcceptedBid($bid->getIdBid(), round(bcdiv($bid->getAmount(), 100, 4), 2));
                    $loan->id_lender        = $iLenderId;
                    $loan->id_project       = $project->id_project;
                    $loan->amount           = $bid->getAmount();
                    $loan->rate             = $bid->getRate();
                    $loan->id_type_contract = $additionalContractId;
                    $this->loanManager->create($loan);
                }
            }
        }
    }

    private function buildLoanIFP($project)
    {
        /** @var \bids $legacyBid */
        $legacyBid = $this->entityManagerSimulator->getRepository('bids');
        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount = $this->entityManagerSimulator->getRepository('lenders_accounts');
        /** @var \loans $loan */
        $loan = $this->entityManagerSimulator->getRepository('loans');
        /** @var \underlying_contract $contract */
        $contract = $this->entityManagerSimulator->getRepository('underlying_contract');
        $bidRepo  = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Bids');

        $aLenderList = $legacyBid->getLenders($project->id_project, array(\bids::STATUS_BID_ACCEPTED));

        if (false === $contract->get(\underlying_contract::CONTRACT_IFP, 'label')) {
            throw new \InvalidArgumentException('The contract ' . \underlying_contract::CONTRACT_IFP . 'does not exist.');
        }
        $IFPContractId = $contract->id_contract;

        $contractAttrVars = $this->contractAttributeManager->getContractAttributesByType($contract, \underlying_contract_attribute_type::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO);
        if (empty($contractAttrVars) || false === isset($contractAttrVars[0]) || false === is_numeric($contractAttrVars[0])) {
            throw new \UnexpectedValueException('The IFP contract max amount is not set');
        } else {
            $IFPLoanAmountMax = $contractAttrVars[0];
        }

        foreach ($aLenderList as $aLender) {
            $iLenderId   = $aLender['id_lender_account'];
            $lenderBids = $bidRepo->findBy(['idLenderAccount' => $iLenderId, 'idProject' => $project->id_project, 'status' => Bids::STATUS_BID_ACCEPTED], ['rate' => 'DESC']);

            if ($lenderAccount->isNaturalPerson($iLenderId)) {
                $fLoansLenderSum = 0;
                $fInterests      = 0;
                $aBidIFP         = array();

                foreach ($lenderBids as $bid) {
                    $fBidAmount = round(bcdiv($bid->getAmount(), 100, 4), 2);

                    if (bccomp(bcadd($fLoansLenderSum, $fBidAmount, 2), $IFPLoanAmountMax, 2) <= 0) {
                        $fInterests = bcadd($fInterests, bcmul($bid->getRate(), $fBidAmount, 2), 2);
                        $fLoansLenderSum = bcadd($fLoansLenderSum, $fBidAmount, 2);
                        $aBidIFP[] = array(
                            'bid_id' => $bid->getIdBid(),
                            'amount' => $fBidAmount
                        );
                    } else {
                        $this->bidManager->reject($bid);
                    }
                }

                // Create IFP loan from the grouped bids
                $loan->unsetData();
                foreach ($aBidIFP as $aAcceptedBid) {
                    $loan->addAcceptedBid($aAcceptedBid['bid_id'], $aAcceptedBid['amount']);
                }
                $loan->id_lender        = $iLenderId;
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

    public function treatFundFailed(\projects $oProject)
    {
        $bidRepo = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Bids');

        $this->addProjectStatus(Users::USER_ID_CRON, \projects_status::FUNDING_KO, $oProject);

        $criteria      = ['idProject' => $oProject->id_project];
        $bids          = $bidRepo->findBy($criteria, ['rate' => 'ASC', 'ordre' => 'ASC']);
        $iBidNbTotal   = $bidRepo->countBy($criteria);
        $treatedBidNb = 0;

        if ($this->logger instanceof LoggerInterface) {
            $this->logger->info($iBidNbTotal . 'bids in total (project ' . $oProject->id_project . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project));
        }

        foreach ($bids as $bid) {
            if ($bid) {
                $this->bidManager->reject($bid, true);
                $treatedBidNb ++;
                if ($this->logger instanceof LoggerInterface) {
                    $this->logger->info($treatedBidNb . '/' . $iBidNbTotal . 'bids treated (project ' . $oProject->id_project . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project));
                }
            }
        }
    }

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
            case \repayment_type::REPAYMENT_TYPE_AMORTIZATION :
                $this->createAmortizationRepaymentSchedule($project);
                return;
            default :
                throw new \Exception('Unknown repayment schedule type ' . $repaymentType->label);
        }
    }

    private function createAmortizationRepaymentSchedule(\projects $oProject)
    {
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '512M');

        /** @var \loans $oLoan */
        $oLoan = $this->entityManagerSimulator->getRepository('loans');
        /** @var \lenders_accounts $oLenderAccount */
        $oLenderAccount = $this->entityManagerSimulator->getRepository('lenders_accounts');
        /** @var \echeanciers $oRepaymentSchedule */
        $oRepaymentSchedule = $this->entityManagerSimulator->getRepository('echeanciers');
        /** @var \clients_adresses $oClientAdresse */
        $oClientAdresse = $this->entityManagerSimulator->getRepository('clients_adresses');
        /** @var \clients $oClient */
        $oClient = $this->entityManagerSimulator->getRepository('clients');

        if ($oProject->status == \projects_status::FUNDE) {
            $lLoans = $oLoan->select('id_project = ' . $oProject->id_project);

            $iLoanNbTotal   = count($lLoans);
            $iTreatedLoanNb = 0;

            if ($this->logger instanceof LoggerInterface) {
                $this->logger->info($iLoanNbTotal . ' in total (project ' . $oProject->id_project . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project));
            }

            foreach ($lLoans as $l) {
                $oLenderAccount->get($l['id_lender'], 'id_lender_account');
                $oClient->get($oLenderAccount->id_client_owner, 'id_client');
                $oClientAdresse->get($oLenderAccount->id_client_owner, 'id_client');
                $oLoan->get($l['id_loan']);

                $aRepaymentSchedule = array();
                foreach ($oLoan->getRepaymentSchedule() as $k => $e) {
                    $dateEcheance = $this->datesManager->dateAddMoisJoursV3($oProject->date_fin, $k);
                    $dateEcheance = date('Y-m-d H:i', $dateEcheance) . ':00';

                    $dateEcheance_emprunteur = $this->datesManager->dateAddMoisJoursV3($oProject->date_fin, $k);
                    $dateEcheance_emprunteur = $this->workingDay->display_jours_ouvres($dateEcheance_emprunteur, 6);
                    $dateEcheance_emprunteur = date('Y-m-d H:i', $dateEcheance_emprunteur) . ':00';

                    $aRepaymentSchedule[] = array(
                        'id_lender'                => $l['id_lender'],
                        'id_project'               => $oProject->id_project,
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
                        $iTreatedLoanNb . '/' . $iLoanNbTotal . ' loans treated. ' . $k . ' repayment schedules created (project ' . $oProject->id_project . ' : ',
                        array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project)
                    );
                }
            }
        }
    }

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
            default:
                throw new \Exception('Unknown repayment schedule type ' . $repaymentType->label);
        }
    }

    /**
     * @param \projects $oProject
     */
    public function createAmortizationPaymentSchedule(\projects $oProject)
    {
        ini_set('memory_limit', '512M');

        /** @var \echeanciers_emprunteur $oPaymentSchedule */
        $oPaymentSchedule = $this->entityManagerSimulator->getRepository('echeanciers_emprunteur');
        /** @var \echeanciers $oRepaymentSchedule */
        $oRepaymentSchedule = $this->entityManagerSimulator->getRepository('echeanciers');
        /** @var \tax_type $taxType */
        $taxType = $this->entityManagerSimulator->getRepository('tax_type');

        $taxRate = $taxType->getTaxRateByCountry('fr');
        $fVAT    = $taxRate[\tax_type::TYPE_VAT] / 100;

        $fAmount           = $oProject->amount;
        $iMonthNb          = $oProject->period;
        $aCommission       = \repayment::getRepaymentCommission($fAmount, $iMonthNb, round(bcdiv($oProject->commission_rate_repayment, 100, 4), 2), $fVAT);
        $aPaymentList      = $oRepaymentSchedule->getMonthlyScheduleByProject($oProject->id_project);
        $iPaymentsNbTotal  = count($aPaymentList);
        $iTreatedPaymentNb = 0;

        if ($this->logger instanceof LoggerInterface) {
            $this->logger->info($iPaymentsNbTotal . ' borrower repayments in total (project ' . $oProject->id_project . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project));
        }

        foreach ($aPaymentList as $iIndex => $aPayment) {
            $sPaymentDate = $this->datesManager->dateAddMoisJoursV3($oProject->date_fin, $iIndex);
            $sPaymentDate = $this->workingDay->display_jours_ouvres($sPaymentDate, 6);
            $sPaymentDate = date('Y-m-d H:i', $sPaymentDate) . ':00';

            $oPaymentSchedule->id_project               = $oProject->id_project;
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
                    'Borrower repayment ' . $oPaymentSchedule->id_echeancier_emprunteur . ' created. ' . $iTreatedPaymentNb . '/' . $iPaymentsNbTotal . 'treated (project ' . $oProject->id_project . ')',
                    array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project)
                );
            }
        }
    }

    /**
     * @param \projects $project
     * @return \DateTime
     */
    public function getProjectEndDate(\projects $project)
    {
        return $project->date_fin != '0000-00-00 00:00:00' ? new \DateTime($project->date_fin) : new \DateTime($project->date_retrait);
    }

    /**
     * @param int       $userId
     * @param int       $projectStatus
     * @param \projects $project
     * @param int       $reminderNumber
     * @param string    $content
     */
    public function addProjectStatus($userId, $projectStatus, \projects &$project, $reminderNumber = 0, $content = '')
    {
        $originStatus = $project->status;
        /** @var \projects_status_history $projectsStatusHistory */
        $projectsStatusHistory = $this->entityManagerSimulator->getRepository('projects_status_history');
        /** @var \projects_status $projectStatusEntity */
        $projectStatusEntity = $this->entityManagerSimulator->getRepository('projects_status');
        $projectStatusEntity->get($projectStatus, 'status');

        $projectsStatusHistory->id_project        = $project->id_project;
        $projectsStatusHistory->id_project_status = $projectStatusEntity->id_project_status;
        $projectsStatusHistory->id_user           = $userId;
        $projectsStatusHistory->numero_relance    = $reminderNumber;
        $projectsStatusHistory->content           = $content;
        $projectsStatusHistory->create();

        $project->status = $projectStatus;
        $project->update();

        if ($originStatus != $projectStatus) {
            $this->projectStatusUpdateTrigger($projectStatusEntity, $project, $userId);
        }
    }

    /**
     * @param \projects_status $projectStatus
     * @param \projects        $project
     * @param int              $userId
     */
    private function projectStatusUpdateTrigger(\projects_status $projectStatus, \projects $project, $userId)
    {
        if ($project->status >= \projects_status::COMPLETE_REQUEST) {
            $userRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Users');
            $message        = $this->slackManager->getProjectName($project) . ' passé en statut *' . $projectStatus->label . '*';

            if ($userId > 0 && ($user = $userRepository->find($userId))) {
                $message .= ' par ' . $user->getFirstname() . ' ' . $user->getName();
            }

            if (
                $project->id_commercial > 0
                && $userId != $project->id_commercial
                && ($user = $userRepository->find($project->id_commercial))
                && false === empty($user->getSlack())
            ) {
                $this->slackManager->sendMessage($message, '@' . $user->getSlack());
            }

            if (
                $project->id_analyste > 0
                && $userId != $project->id_analyste
                && ($user = $userRepository->find($project->id_analyste))
                && false === empty($user->getSlack())
            ) {
                $this->slackManager->sendMessage($message, '@' . $user->getSlack());
            }

            $this->slackManager->sendMessage($message, '#statuts-projets');
        }

        switch ($project->status) {
            case \projects_status::COMMERCIAL_REJECTION:
            case \projects_status::ANALYSIS_REJECTION:
            case \projects_status::COMITY_REJECTION:
                $this->abandonOlderProjects($project, $userId);
                break;
            case \projects_status::A_FUNDER:
                $this->mailerManager->sendProjectOnlineToBorrower($project);
                break;
            case \projects_status::PRET_REFUSE:
                $this->cancelProxyAndMandate($project);
                break;
        }
    }

    /**
     * @param \projects $project
     * @param int       $userId
     */
    public function abandonOlderProjects(\projects $project, $userId)
    {
        /** @var \companies $company */
        $company = $this->entityManagerSimulator->getRepository('companies');
        $company->get($project->id_company);

        $previousProjects = $project->getPreviousProjectsWithSameSiren($company->siren, $project->added);

        foreach ($previousProjects as $previousProject) {
            if (in_array($previousProject['status'], [\projects_status::IMPOSSIBLE_AUTO_EVALUATION, \projects_status::INCOMPLETE_REQUEST, \projects_status::COMPLETE_REQUEST])) {
                $project->get($previousProject['id_project'], 'id_project');
                $this->addProjectStatus($userId, \projects_status::ABANDONED, $project, 0, 'same_company_project_rejected');
            }
        }
    }

    public function isFunded(\projects $oProject)
    {
        /** @var \bids $oBid */
        $oBid      = $this->entityManagerSimulator->getRepository('bids');
        $iBidTotal = $oBid->getSoldeBid($oProject->id_project);

        if ($iBidTotal >= $oProject->amount) {
            return true;
        }

        return false;
    }

    public function markAsFunded(\projects $oProject)
    {
        if ($oProject->date_funded == '0000-00-00 00:00:00') {
            $oFunded    = new \DateTime();
            $oPublished = new \DateTime($oProject->date_publication);

            if ($oFunded < $oPublished) {
                $oFunded = $oPublished;
            }

            $oProject->date_funded  = $oFunded->format('Y-m-d H:i:s');
            $oProject->update();

            $this->mailerManager->sendFundedToStaff($oProject);
        }
    }

    /**
     * @param \projects $project
     * @return array
     */
    public function getBidsSummary(\projects $project)
    {
        /** @var \bids $bid */
        $bid = $this->entityManagerSimulator->getRepository('bids');
        return $bid->getBidsSummary($project->id_project);
    }

    public function getPossibleProjectPeriods()
    {
        /** @var \settings $settings */
        $settings = $this->entityManagerSimulator->getRepository('settings');
        $settings->get('Durée des prêts autorisées', 'type');
        return explode(',', $settings->value);
    }

    public function getMaxProjectAmount()
    {
        /** @var \settings $settings */
        $settings = $this->entityManagerSimulator->getRepository('settings');
        $settings->get('Somme à emprunter max', 'type');
        return (int) $settings->value;
    }

    public function getMinProjectAmount()
    {
        /** @var \settings $settings */
        $settings = $this->entityManagerSimulator->getRepository('settings');
        $settings->get('Somme à emprunter min', 'type');
        return (int) $settings->value;
    }

    /**
     * @param int $amount
     * @return int
     */
    public function getAverageFundingDuration($amount)
    {
        /** @var \settings $settings */
        $settings = $this->entityManagerSimulator->getRepository('settings');
        $settings->get('Durée moyenne financement', 'type');

        $projectAverageFundingDuration = 15;
        foreach (json_decode($settings->value) as $averageFundingDuration) {
            if ($amount >= $averageFundingDuration->min && $amount <= $averageFundingDuration->max) {
                $projectAverageFundingDuration = round($averageFundingDuration->heures / 24);
            }
        }

        return $projectAverageFundingDuration;
    }

    public function getProjectRateRangeId(\projects $project)
    {
        if (empty($project->period)) {
            throw new \Exception('project period not set.');
        }

        if (empty($project->risk)) {
            throw new \Exception('project risk not set.');
        }

        /** @var \project_period $projectPeriod */
        $projectPeriod = $this->entityManagerSimulator->getRepository('project_period');

        if ($projectPeriod->getPeriod($project->period)) {
            /** @var \project_rate_settings $projectRateSettings */
            $projectRateSettings = $this->entityManagerSimulator->getRepository('project_rate_settings');
            $rateSettings        = $projectRateSettings->getSettings($project->risk, $projectPeriod->id_period);

            if (empty($rateSettings)) {
                throw new \Exception('No rate settings found for the project.');
            }
            if (count($rateSettings) === 1) {
                return $rateSettings[0]['id_rate'];
            } else {
                throw new \Exception('More than one rate settings found for the project.');
            }
        } else {
            throw new \Exception('Period not found for the project.');
        }
    }

    /**
     * @param \projects $project
     *
     * @return bool
     */
    public function isRateMinReached(\projects $project)
    {
        $rateRange = $this->bidManager->getProjectRateRange($project);
        /** @var \bids $bid */
        $bid = $this->entityManagerSimulator->getRepository('bids');
        $totalBidRateMin = $bid->getSoldeBid($project->id_project, $rateRange['rate_min'], array(\bids::STATUS_BID_PENDING, \bids::STATUS_BID_ACCEPTED));

        return $totalBidRateMin >= $project->amount;
    }

    /**
     * @param \projects $project
     */
    public function cancelProxyAndMandate(\projects $project)
    {
        /** @var \projects_pouvoir $mandate */
        $mandate = $this->entityManagerSimulator->getRepository('clients_mandats');
        /** @var \projects_pouvoir $proxy */
        $proxy = $this->entityManagerSimulator->getRepository('projects_pouvoir');

        $client = new soapClient($this->universignUrl);

        if ($mandate->get($project->id_project, 'id_project')) {
            $mandate->status = \clients_mandats::STATUS_CANCELED;
            $mandate->update();

            $request          = new soapRequest('requester.cancelTransaction', array(new documentId($mandate->id_universign, "string")));
            $universignReturn = $client->send($request);

            if ($universignReturn->faultCode()) {
                $this->logger->error('Mandate cancellation failed. Reason : ' . $universignReturn->faultString() . ' (project ' . $mandate->id_project . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $mandate->id_project));
            } else {
                $this->logger->info('Mandate canceled (project ' . $mandate->id_project . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $mandate->id_project));
            }
        } else {
            $this->logger->info('Cannot get Mandate', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project));
        }

        if ($proxy->get($project->id_project, 'id_project')) {
            $proxy->status = \projects_pouvoir::STATUS_CANCELLED;
            $proxy->update();

            $request          = new soapRequest('requester.cancelTransaction', array(new documentId($proxy->id_universign, "string")));
            $universignReturn = $client->send($request);

            if ($universignReturn->faultCode()) {
                $this->logger->error('Proxy cancellation failed. Reason : ' . $universignReturn->faultString() . ' (project ' . $proxy->id_project . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $proxy->id_project));
            } else {
                $this->logger->info('Proxy canceled (project ' . $proxy->id_project . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $proxy->id_project));
            }
        } else {
            $this->logger->info('Cannot get Proxy', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project));
        }
    }
}
