<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Bids;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ContractAttributeManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductManager;
use Unilend\core\Loader;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
use PhpXmlRpc\Client as soapClient;
use PhpXmlRpc\Request as soapRequest;
use PhpXmlRpc\Value as documentId;

class ProjectManager
{
    /** @var NotificationManager */
    private $oNotificationManager;

    /** @var \ficelle */
    private $oFicelle;

    /** @var \dates */
    private $oDate;

    /** @var LoggerInterface */
    private $oLogger;

    /** @var BidManager */
    private $oBidManager;

    /** @var LoanManager */
    private $oLoanManager;

    /** @var AutoBidSettingsManager */
    private $oAutoBidSettingsManager;

    /** @var MailerManager */
    private $oMailerManager;

    /** @var LenderManager */
    private $oLenderManager;

    /** @var \jours_ouvres */
    private $oWorkingDay;

    /** @var EntityManagerSimulator  */
    private $oEntityManager;

    /** @var EntityManager */
    private $entityManager;

    /** @var  ProjectRateSettingsManager */
    private $projectRateSettingsManager;

    /** @var string */
    private $universignUrl;

    /** @var ProductManager */
    private $productManager;

    /** @var ContractAttributeManager */
    private $contractAttributeManager;

    public function __construct(
        EntityManagerSimulator $oEntityManager,
        EntityManager $entityManager,
        BidManager $oBidManager,
        LoanManager $oLoanManager,
        NotificationManager $oNotificationManager,
        AutoBidSettingsManager $oAutoBidSettingsManager,
        MailerManager $oMailerManager,
        LenderManager $oLenderManager,
        ProjectRateSettingsManager $projectRateSettingsManager,
        ProductManager $productManager,
        ContractAttributeManager $contractAttributeManager,
        $universignUrl
    ) {
        $this->oEntityManager             = $oEntityManager;
        $this->entityManager              = $entityManager;
        $this->oBidManager                = $oBidManager;
        $this->oLoanManager               = $oLoanManager;
        $this->oNotificationManager       = $oNotificationManager;
        $this->oAutoBidSettingsManager    = $oAutoBidSettingsManager;
        $this->oMailerManager             = $oMailerManager;
        $this->oLenderManager             = $oLenderManager;
        $this->projectRateSettingsManager = $projectRateSettingsManager;
        $this->productManager             = $productManager;
        $this->contractAttributeManager   = $contractAttributeManager;
        $this->universignUrl              = $universignUrl;

        $this->oFicelle    = Loader::loadLib('ficelle');
        $this->oDate       = Loader::loadLib('dates');
        $this->oWorkingDay = Loader::loadLib('jours_ouvres');
    }

    /**
     * @param LoggerInterface $oLogger
     */
    public function setLogger(LoggerInterface $oLogger)
    {
        $this->oLogger = $oLogger;
    }

    public function prePublish(\projects $oProject)
    {
       // $this->autoBid($oProject);

        if ($this->isFunded($oProject)) {
            $this->markAsFunded($oProject);
        }

        $this->reBidAutoBidDeeply($oProject, BidManager::MODE_REBID_AUTO_BID_CREATE, false);
        $this->addProjectStatus(\users::USER_ID_CRON, \projects_status::AUTO_BID_PLACED, $oProject);
    }

    /**
     * @param \projects $project
     */
    public function publish(\projects $project)
    {
        /** @var \bids $bidData */
        $bidData = $this->oEntityManager->getRepository('bids');
        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount = $this->oEntityManager->getRepository('lenders_accounts');

        $offset = 0;
        $limit  = 100;

        while ($bids = $bidData->getLastProjectBidsByLender($project->id_project, $limit, $offset)) {
            foreach ($bids as $bid) {
                if ($lenderAccount->get($bid['id_lender_account'])) {
                    $this->oNotificationManager->create(
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

        $this->addProjectStatus(\users::USER_ID_CRON, \projects_status::EN_FUNDING, $project);
    }

    public function checkBids(\projects $oProject, $bSendNotification)
    {
        /** @var \bids $legacyBid */
        $legacyBid = $this->oEntityManager->getRepository('bids');
        /** @var \bids_logs $oBidLog */
        $oBidLog = $this->oEntityManager->getRepository('bids_logs');
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
                        $this->oBidManager->reject($bid, $bSendNotification);
                    } else {
                        // For a autobid, we don't send reject notification, we don't create payback transaction, either. So we just flag it here as reject temporarily
                        $bid->setStatus(Bids::STATUS_AUTOBID_REJECTED_TEMPORARILY);
                        $this->entityManager->flush();
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
        if ($this->oLogger instanceof LoggerInterface) {
            $this->oLogger->info('Check bid info: ' . var_export($aLogContext) . ' (project ' . $oProject->id_project . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project));
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
        $oAutoBid = $this->oEntityManager->getRepository('autobid');
        /** @var \project_period $oProjectPeriods */
        $oProjectPeriods = $this->oEntityManager->getRepository('project_period');
        $autobidRepo     = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Autobid');
        $project         = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($oProject->id_project);

        if ($oProjectPeriods->getPeriod($oProject->period)) {
            $rateRange = $this->oBidManager->getProjectRateRange($oProject);

            $iOffset = 0;
            $iLimit  = 100;
            while ($aAutoBidList = $oAutoBid->getSettings(null, $oProject->risk, $oProjectPeriods->id_period, array(\autobid::STATUS_ACTIVE), ['id_autobid' => 'ASC'], $iLimit, $iOffset)) {
                $iOffset += $iLimit;

                foreach ($aAutoBidList as $aAutoBidSetting) {
                    $autobid = $autobidRepo->find($aAutoBidSetting['id_autobid']);
                    if ($autobid) {
                        try {
                            $this->oBidManager->bidByAutoBidSettings($autobid, $project, $rateRange['rate_max'], false);
                        } catch (\Exception $exception) {
                            continue;
                        }
                    }
                }
            }

            /** @var \bids $oBid */
            $oBid = $this->oEntityManager->getRepository('bids');
            $oBid->shuffleAutoBidOrder($oProject->id_project);
        }
    }

    private function reBidAutoBid(\projects $oProject, $iMode, $bSendNotification)
    {
        /** @var \settings $oSettings */
        $oSettings = $this->oEntityManager->getRepository('settings');
        /** @var \bids $legacyBid */
        $legacyBid = $this->oEntityManager->getRepository('bids');
        $bidRepo = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Bids');

        $oSettings->get('Auto-bid step', 'type');
        $fStep       = (float)$oSettings->value;
        $currentRate = bcsub($legacyBid->getProjectMaxRate($oProject), $fStep, 1);

        while ($aAutoBidList = $legacyBid->getAutoBids($oProject->id_project, \bids::STATUS_AUTOBID_REJECTED_TEMPORARILY)) {
            foreach ($aAutoBidList as $aAutobid) {
                $bid = $bidRepo->find($aAutobid['id_bid']);
                if ($bid) {
                    $this->oBidManager->reBidAutoBidOrReject($bid, $currentRate, $iMode, $bSendNotification);
                }
            }
        }
    }

    private function reBidAutoBidDeeply(\projects $oProject, $iMode, $bSendNotification)
    {
        /** @var \bids $oBid */
        $oBid = $this->oEntityManager->getRepository('bids');
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

        $this->addProjectStatus(\users::USER_ID_CRON, \projects_status::BID_TERMINATED, $oProject);
        $this->reBidAutoBidDeeply($oProject, BidManager::MODE_REBID_AUTO_BID_CREATE, true);
        $this->addProjectStatus(\users::USER_ID_CRON, \projects_status::FUNDE, $oProject);

        if ($this->oLogger instanceof LoggerInterface) {
            $this->oLogger->info('Project ' . $oProject->id_project . ' is now changed to status funded', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project));
        }

        $creteria      = ['idProject' => $oProject->id_project, 'status' => Bids::STATUS_BID_PENDING];
        $bids          = $bidRepo->findBy($creteria, ['rate' => 'ASC', 'ordre' => 'ASC']);
        $iBidNbTotal   = $bidRepo->countBy($creteria);
        $iBidBalance   = 0;
        $iTreatedBitNb = 0;

        if ($this->oLogger instanceof LoggerInterface) {
            $this->oLogger->info($iBidNbTotal . ' bids created (project ' . $oProject->id_project . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project));
        }

        foreach ($bids as $bid) {
            if ($bid) {
                if ($iBidBalance < $oProject->amount) {
                    $iBidBalance = bcadd($iBidBalance, round(bcdiv($bid->getAmount(), 100, 4), 2), 2);
                    if ($iBidBalance > $oProject->amount) {
                        $fAmountToCredit = $iBidBalance - $oProject->amount;
                        $this->oBidManager->rejectPartially($bid, $fAmountToCredit);
                    } else {
                        $bid->setStatus(Bids::STATUS_BID_ACCEPTED);
                        $this->entityManager->flush();
                    }

                    if ($this->oLogger instanceof LoggerInterface) {
                        $this->oLogger->info('The bid status has been updated to 1' . $bid->getIdBid() . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project));
                    }
                } else {
                    $this->oBidManager->reject($bid, true);
                }

                $iTreatedBitNb++;

                if ($this->oLogger instanceof LoggerInterface) {
                    $this->oLogger->info($iTreatedBitNb . '/' . $iBidNbTotal . ' bids treated (project ' . $oProject->id_project . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project));
                }
            }
        }
        /** @var \product $product */
        $product = $this->oEntityManager->getRepository('product');
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
        $legacyBid = $this->oEntityManager->getRepository('bids');
        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount = $this->oEntityManager->getRepository('lenders_accounts');
        /** @var \loans $loan */
        $loan = $this->oEntityManager->getRepository('loans');
        /** @var \underlying_contract $contract */
        $contract = $this->oEntityManager->getRepository('underlying_contract');
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
            $iLenderId   = $aLender['id_lender_account'];
            $lenderBids  = $bidRepo->findBy(['idLenderAccount' => $iLenderId, 'idProject' => $project->id_project, 'status' => Bids::STATUS_BID_ACCEPTED], ['rate' => 'DESC']);

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
                        $this->oLoanManager->create($loan);

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
                $this->oLoanManager->create($loan);
            } else {
                foreach ($lenderBids as $bid) {
                    $loan->unsetData();
                    $loan->addAcceptedBid($bid->getIdBid(), round(bcdiv($bid->getAmount(), 100, 4), 2));
                    $loan->id_lender        = $iLenderId;
                    $loan->id_project       = $project->id_project;
                    $loan->amount           = $bid->getAmount();
                    $loan->rate             = $bid->getRate();
                    $loan->id_type_contract = $additionalContractId;
                    $this->oLoanManager->create($loan);
                }
            }
        }
    }

    private function buildLoanIFP($project)
    {
        /** @var \bids $legacyBid */
        $legacyBid = $this->oEntityManager->getRepository('bids');
        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount = $this->oEntityManager->getRepository('lenders_accounts');
        /** @var \loans $loan */
        $loan = $this->oEntityManager->getRepository('loans');
        /** @var \underlying_contract $contract */
        $contract = $this->oEntityManager->getRepository('underlying_contract');
        $bidRepo = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Bids');

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
                        $this->oBidManager->reject($bid);
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
                $this->oLoanManager->create($loan);
            } else {
                foreach ($lenderBids as $bid) {
                    $this->oBidManager->reject($bid);
                }
            }
        }
    }

    public function treatFundFailed(\projects $oProject)
    {
        $bidRepo = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Bids');

        $this->addProjectStatus(\users::USER_ID_CRON, \projects_status::FUNDING_KO, $oProject);

        $criteria      = ['idProject' => $oProject->id_project];
        $bids          = $bidRepo->findBy($criteria, ['rate' => 'ASC', 'ordre' => 'ASC']);
        $iBidNbTotal   = $bidRepo->countBy($criteria);
        $iTreatedBitNb = 0;

        if ($this->oLogger instanceof LoggerInterface) {
            $this->oLogger->info($iBidNbTotal . 'bids in total (project ' . $oProject->id_project . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project));
        }

        foreach ($bids as $bid) {
            if ($bid) {
                $this->oBidManager->reject($bid, true);
                $iTreatedBitNb++;
                if ($this->oLogger instanceof LoggerInterface) {
                    $this->oLogger->info($iTreatedBitNb . '/' . $iBidNbTotal . 'bids treated (project ' . $oProject->id_project . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project));
                }
            }
        }
    }

    public function createRepaymentSchedule(\projects $project)
    {
        /** @var \product $product */
        $product = $this->oEntityManager->getRepository('product');
        if (! $product->get($project->id_product)) {
            throw new \Exception('Invalid product id ' . $project->id_product . ' found for project id ' . $project->id_project);
        }
        /** @var \repayment_type $repaymentType */
        $repaymentType = $this->oEntityManager->getRepository('repayment_type');
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
        $oLoan = $this->oEntityManager->getRepository('loans');
        /** @var \lenders_accounts $oLenderAccount */
        $oLenderAccount = $this->oEntityManager->getRepository('lenders_accounts');
        /** @var \echeanciers $oRepaymentSchedule */
        $oRepaymentSchedule = $this->oEntityManager->getRepository('echeanciers');
        /** @var \clients_adresses $oClientAdresse */
        $oClientAdresse = $this->oEntityManager->getRepository('clients_adresses');
        /** @var \clients $oClient */
        $oClient = $this->oEntityManager->getRepository('clients');

        if ($oProject->status == \projects_status::FUNDE) {
            $lLoans = $oLoan->select('id_project = ' . $oProject->id_project);

            $iLoanNbTotal   = count($lLoans);
            $iTreatedLoanNb = 0;

            if ($this->oLogger instanceof LoggerInterface) {
                $this->oLogger->info($iLoanNbTotal . ' in total (project ' . $oProject->id_project . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project));
            }

            foreach ($lLoans as $l) {
                $oLenderAccount->get($l['id_lender'], 'id_lender_account');
                $oClient->get($oLenderAccount->id_client_owner, 'id_client');
                $oClientAdresse->get($oLenderAccount->id_client_owner, 'id_client');
                $oLoan->get($l['id_loan']);

                $aRepaymentSchedule = array();
                foreach ($oLoan->getRepaymentSchedule() as $k => $e) {
                    $dateEcheance = $this->oDate->dateAddMoisJoursV3($oProject->date_fin, $k);
                    $dateEcheance = date('Y-m-d H:i', $dateEcheance) . ':00';

                    $dateEcheance_emprunteur = $this->oDate->dateAddMoisJoursV3($oProject->date_fin, $k);
                    $dateEcheance_emprunteur = $this->oWorkingDay->display_jours_ouvres($dateEcheance_emprunteur, 6);
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

                if ($this->oLogger instanceof LoggerInterface) {
                    $this->oLogger->info(
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
        $product = $this->oEntityManager->getRepository('product');
        if (! $product->get($project->id_product)) {
            throw new \Exception('Invalid product id ' . $project->id_product . ' found for project id ' . $project->id_project);
        }
        /** @var \repayment_type $repaymentType */
        $repaymentType = $this->oEntityManager->getRepository('repayment_type');
        $repaymentType->get($product->id_repayment_type);

        switch ($repaymentType->label) {
            case \repayment_type::REPAYMENT_TYPE_AMORTIZATION :
                return $this->createAmortizationPaymentSchedule($project);
            default :
                throw new \Exception('Unknown repayment schedule type ' . $repaymentType->label);
        }
    }

    public function createAmortizationPaymentSchedule(\projects $oProject)
    {
        ini_set('memory_limit', '512M');

        /** @var \echeanciers_emprunteur $oPaymentSchedule */
        $oPaymentSchedule = $this->oEntityManager->getRepository('echeanciers_emprunteur');
        /** @var \echeanciers $oRepaymentSchedule */
        $oRepaymentSchedule = $this->oEntityManager->getRepository('echeanciers');
        /** @var \settings $oSettings */
        $oSettings = $this->oEntityManager->getRepository('settings');

        $oSettings->get('Commission remboursement', 'type');
        $fCommissionRate = $oSettings->value;

        /** @var \tax_type $taxType */
        $taxType = $this->oEntityManager->getRepository('tax_type');

        $taxRate = $taxType->getTaxRateByCountry('fr');
        $fVAT    = $taxRate[\tax_type::TYPE_VAT] / 100;

        $fAmount           = $oProject->amount;
        $iMonthNb          = $oProject->period;
        $aCommission       = \repayment::getRepaymentCommission($fAmount, $iMonthNb, $fCommissionRate, $fVAT);
        $aPaymentList      = $oRepaymentSchedule->getMonthlyScheduleByProject($oProject->id_project);
        $iPaymentsNbTotal  = count($aPaymentList);
        $iTreatedPaymentNb = 0;

        if ($this->oLogger instanceof LoggerInterface) {
            $this->oLogger->info($iPaymentsNbTotal . ' borrower repayments in total (project ' . $oProject->id_project . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project));
        }

        foreach ($aPaymentList as $iIndex => $aPayment) {
            $sPaymentDate = $this->oDate->dateAddMoisJoursV3($oProject->date_fin, $iIndex);
            $sPaymentDate = $this->oWorkingDay->display_jours_ouvres($sPaymentDate, 6);
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

            if ($this->oLogger instanceof LoggerInterface) {
                $this->oLogger->info(
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

    public function addProjectStatus($iUserId, $iProjectStatus, \projects &$oProject, $iReminderNumber = 0, $sContent = '')
    {
        /** @var \projects_status_history $oProjectsStatusHistory */
        $oProjectsStatusHistory = $this->oEntityManager->getRepository('projects_status_history');
        /** @var \projects_status $oProjectStatus */
        $oProjectStatus = $this->oEntityManager->getRepository('projects_status');
        $oProjectStatus->get($iProjectStatus, 'status');

        $oProjectsStatusHistory->id_project        = $oProject->id_project;
        $oProjectsStatusHistory->id_project_status = $oProjectStatus->id_project_status;
        $oProjectsStatusHistory->id_user           = $iUserId;
        $oProjectsStatusHistory->numero_relance    = $iReminderNumber;
        $oProjectsStatusHistory->content           = $sContent;
        $oProjectsStatusHistory->create();

        $oProject->status = $iProjectStatus;
        $oProject->update();

        $this->projectStatusUpdateTrigger($iProjectStatus, $oProject);
    }

    private function projectStatusUpdateTrigger($iProjectStatus, \projects $oProject)
    {
        /** @var \settings $oSettings */
        $oSettings = $this->oEntityManager->getRepository('settings');

        switch ($iProjectStatus) {
            case \projects_status::A_TRAITER:
                $oSettings->get('Adresse notification inscription emprunteur', 'type');
                $this->oMailerManager->sendProjectNotificationToStaff('notification-depot-de-dossier', $oProject, trim($oSettings->value));
                break;
            case \projects_status::ATTENTE_ANALYSTE:
                $oSettings->get('Adresse notification analystes', 'type');
                $this->oMailerManager->sendProjectNotificationToStaff('notification-projet-a-traiter', $oProject, trim($oSettings->value));
                break;
            case \projects_status::REJETE:
            case \projects_status::REJET_ANALYSTE:
            case \projects_status::REJET_COMITE:
                $this->stopRemindersForOlderProjects($oProject);
                break;
            case \projects_status::A_FUNDER:
                $this->oMailerManager->sendProjectOnlineToBorrower($oProject);
                break;
            case \projects_status::PRET_REFUSE:
                $this->cancelProxyAndMandate($oProject);
                break;
            case \projects_status::REMBOURSEMENT:
            case \projects_status::PROBLEME:
            case \projects_status::PROBLEME_J_X:
            case \projects_status::RECOUVREMENT:
            case \projects_status::PROCEDURE_SAUVEGARDE:
            case \projects_status::REDRESSEMENT_JUDICIAIRE:
            case \projects_status::LIQUIDATION_JUDICIAIRE:
                $this->oLenderManager->addLendersToLendersAccountsStatQueue($oProject->getLoansAndLendersForProject($oProject->id_project));
                break;
        }
    }

    public function stopRemindersForOlderProjects(\projects $oProject)
    {
        /** @var \companies $oCompany */
        $oCompany = $this->oEntityManager->getRepository('companies');

        $oCompany->get($oProject->id_company);
        $aPreviousProjectsWithSameSiren = $oProject->getPreviousProjectsWithSameSiren($oCompany->siren, $oProject->added);
        foreach ($aPreviousProjectsWithSameSiren as $aProject) {
            $oProject->get($aProject['id_project'], 'id_project');
            $this->stopRemindersOnProject($oProject);
        }
    }

    public function stopRemindersOnProject(\projects $oProject)
    {
        $oProject->stop_relances = '1';
        $oProject->update();
    }

    public function isFunded(\projects $oProject)
    {
        /** @var \bids $oBid */
        $oBid      = $this->oEntityManager->getRepository('bids');
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

            $this->oMailerManager->sendFundedToStaff($oProject);
        }
    }

    /**
     * @param \projects $project
     * @return array
     */
    public function getBidsSummary(\projects $project)
    {
        /** @var \bids $bid */
        $bid = $this->oEntityManager->getRepository('bids');
        return $bid->getBidsSummary($project->id_project);
    }

    public function getPossibleProjectPeriods()
    {
        /** @var \settings $settings */
        $settings = $this->oEntityManager->getRepository('settings');
        $settings->get('Durée des prêts autorisées', 'type');
        return explode(',', $settings->value);
    }

    public function getMaxProjectAmount()
    {
        /** @var \settings $settings */
        $settings = $this->oEntityManager->getRepository('settings');
        $settings->get('Somme à emprunter max', 'type');
        return (int) $settings->value;
    }

    public function getMinProjectAmount()
    {
        /** @var \settings $settings */
        $settings = $this->oEntityManager->getRepository('settings');
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
        $settings = $this->oEntityManager->getRepository('settings');
        $settings->get('Durée moyenne financement', 'type');

        $projectAverageFundingDuration = 15;
        foreach (json_decode($settings->value) as $averageFundingDuration) {
            if ($amount >= $averageFundingDuration->min && $amount <= $averageFundingDuration->max) {
                $projectAverageFundingDuration = round($averageFundingDuration->heures / 24);
            }
        }

        return $projectAverageFundingDuration;
    }

    public function getProjectRateRange(\projects $project)
    {
        if (empty($project->period)) {
            throw new \Exception('project period not set.');
        }

        if (empty($project->risk)) {
            throw new \Exception('project risk not set.');
        }

        /** @var \project_period $projectPeriod */
        $projectPeriod = $this->oEntityManager->getRepository('project_period');

        if ($projectPeriod->getPeriod($project->period)) {
            /** @var \project_rate_settings $projectRateSettings */
            $projectRateSettings = $this->oEntityManager->getRepository('project_rate_settings');
            $rateSettings = $projectRateSettings->getSettings($project->risk, $projectPeriod->id_period);

            if (empty($rateSettings)) {
                throw new \Exception('No settings found for the project.');
            }
            if (count($rateSettings) === 1) {
                return $rateSettings[0]['id_rate'];
            } else {
                throw new \Exception('More than one settings found for the project.');
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
        $rateRange = $this->oBidManager->getProjectRateRange($project);
        /** @var \bids $bid */
        $bid = $this->oEntityManager->getRepository('bids');
        $totalBidRateMin = $bid->getSoldeBid($project->id_project, $rateRange['rate_min'], array(\bids::STATUS_BID_PENDING, \bids::STATUS_BID_ACCEPTED));

        return $totalBidRateMin >= $project->amount;
    }

    /**
     * @param \projects $project
     */
    public function cancelProxyAndMandate(\projects $project)
    {
        /** @var \projects_pouvoir $mandate */
        $mandate = $this->oEntityManager->getRepository('clients_mandats');
        /** @var \projects_pouvoir $proxy */
        $proxy = $this->oEntityManager->getRepository('projects_pouvoir');

        $client = new soapClient($this->universignUrl);

        if ($mandate->get($project->id_project, 'id_project')) {
            $mandate->status = \clients_mandats::STATUS_CANCELED;
            $mandate->update();

            $request          = new soapRequest('requester.cancelTransaction', array(new documentId($mandate->id_universign, "string")));
            $universignReturn = $client->send($request);

            if ($universignReturn->faultCode()) {
                $this->oLogger->error('Mandate cancellation failed. Reason : ' . $universignReturn->faultString() . ' (project ' . $mandate->id_project . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $mandate->id_project));
            } else {
                $this->oLogger->info('Mandate canceled (project ' . $mandate->id_project . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $mandate->id_project));
            }
        } else {
            $this->oLogger->info('Cannot get Mandate', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project));
        }

        if ($proxy->get($project->id_project, 'id_project')) {
            $proxy->status = \projects_pouvoir::STATUS_CANCELLED;
            $proxy->update();

            $request          = new soapRequest('requester.cancelTransaction', array(new documentId($proxy->id_universign, "string")));
            $universignReturn = $client->send($request);

            if ($universignReturn->faultCode()) {
                $this->oLogger->error('Proxy cancellation failed. Reason : ' . $universignReturn->faultString() . ' (project ' . $proxy->id_project . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $proxy->id_project));
            } else {
                $this->oLogger->info('Proxy canceled (project ' . $proxy->id_project . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $proxy->id_project));
            }
        } else {
            $this->oLogger->info('Cannot get Proxy', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $project->id_project));
        }
    }
}
