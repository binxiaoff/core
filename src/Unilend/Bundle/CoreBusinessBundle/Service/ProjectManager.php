<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Psr\Log\LoggerInterface;
use Unilend\core\Loader;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

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

    /** @var EntityManager  */
    private $oEntityManager;

    public function __construct(EntityManager $oEntityManager, BidManager $oBidManager, LoanManager $oLoanManager, NotificationManager $oNotificationManager, AutoBidSettingsManager $oAutoBidSettingsManager, MailerManager $oMailerManager, LenderManager $oLenderManager)
    {
        $this->oEntityManager          = $oEntityManager;
        $this->oBidManager             = $oBidManager;
        $this->oLoanManager            = $oLoanManager;
        $this->oNotificationManager    = $oNotificationManager;
        $this->oAutoBidSettingsManager = $oAutoBidSettingsManager;
        $this->oMailerManager          = $oMailerManager;
        $this->oLenderManager          = $oLenderManager;

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
        $this->autoBid($oProject);

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
        /** @var \bids $oBid */
        $oBid = $this->oEntityManager->getRepository('bids');
        /** @var \bids_logs $oBidLog */
        $oBidLog = $this->oEntityManager->getRepository('bids_logs');

        $aLogContext      = array();
        $bBidsLogs        = false;
        $iRejectedBids    = 0;
        $iBidsAccumulated = 0;
        $iBorrowAmount    = $oProject->amount;
        $iBidTotal        = $oBid->getSoldeBid($oProject->id_project);

        $oBidLog->debut = date('Y-m-d H:i:s');

        if ($iBidTotal >= $iBorrowAmount) {
            foreach ($oBid->select('id_project = ' . $oProject->id_project . ' AND status = 0', 'rate ASC, ordre ASC') as $aBid) {
                if ($iBidsAccumulated < $iBorrowAmount) {
                    $iBidsAccumulated += ($aBid['amount'] / 100);
                } else {
                    $bBidsLogs = true;
                    $oBid->get($aBid['id_bid']);

                    if (0 == $oBid->id_autobid) { // non-auto-bid
                        $this->oBidManager->reject($oBid, $bSendNotification);
                    } else {
                        // For a autobid, we don't send reject notification, we don't create payback transaction, either. So we just flag it here as reject temporarily
                        $oBid->status = \bids::STATUS_AUTOBID_REJECTED_TEMPORARILY;
                    }

                    $iRejectedBids++;
                    $oBid->update();
                }

                if (1 != $oBid->checked) {
                    $oBid->checked = 1;
                    $oBid->update();
                }
            }

            $aLogContext['Project ID']    = $oProject->id_project;
            $aLogContext['Balance']       = $iBidTotal;
            $aLogContext['Rejected bids'] = $iRejectedBids;
        }

        if ($bBidsLogs == true) {
            $oBidLog->id_project      = $oProject->id_project;
            $oBidLog->nb_bids_encours = $oBid->counter('id_project = ' . $oProject->id_project . ' AND status = 0');
            $oBidLog->nb_bids_ko      = $iRejectedBids;
            $oBidLog->total_bids      = $oBid->counter('id_project = ' . $oProject->id_project);
            $oBidLog->total_bids_ko   = $oBid->counter('id_project = ' . $oProject->id_project . ' AND status = 2');
            $oBidLog->rate_max        = $oBid->getProjectMaxRate($oProject);
            $oBidLog->fin             = date('Y-m-d H:i:s');
            $oBidLog->create();
        }
        if ($this->oLogger instanceof LoggerInterface) {
            $this->oLogger->info('Check bid info: ' . var_export($aLogContext) . ' (project ' . $oProject->id_project . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project));
        }
    }

    /**
     * @param \projects $oProject
     *
     * @return bool
     */
    public function autoBid(\projects $oProject)
    {
        /** @var \projects_status $oProjectStatus */
        $oProjectStatus = $this->oEntityManager->getRepository('projects_status');
        if ($oProjectStatus->getLastStatut($oProject->id_project)) {
            if ($oProjectStatus->status == \projects_status::A_FUNDER) {
                $this->bidAllAutoBid($oProject);
            } elseif ($oProjectStatus->status == \projects_status::EN_FUNDING) {
                $this->reBidAutoBid($oProject, BidManager::MODE_REBID_AUTO_BID_CREATE, true);
            }
        }
    }

    private function bidAllAutoBid(\projects $oProject)
    {
        /** @var \autobid $oAutoBid */
        $oAutoBid = $this->oEntityManager->getRepository('autobid');

        $aPeriod = $this->oAutoBidSettingsManager->getPeriod($oProject->period);
        if (false === empty($aPeriod)) {
            $iOffset = 0;
            $iLimit  = 100;
            while ($aAutoBidList = $this->oAutoBidSettingsManager->getSettings(null, $oProject->risk, $aPeriod['id_period'], array(\autobid::STATUS_ACTIVE), 'id_autobid', $iLimit, $iOffset)) {
                $iOffset += $iLimit;

                foreach ($aAutoBidList as $aAutoBidSetting) {
                    if ($oAutoBid->get($aAutoBidSetting['id_autobid'])) {
                        $this->oBidManager->bidByAutoBidSettings($oAutoBid, $oProject, \bids::BID_RATE_MAX, false);
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
        /** @var \bids $oBid */
        $oBid = $this->oEntityManager->getRepository('bids');

        $oSettings->get('Auto-bid step', 'type');
        $fStep       = (float)$oSettings->value;
        $currentRate = bcsub($oBid->getProjectMaxRate($oProject), $fStep, 1);

        while ($aAutoBidList = $oBid->getAutoBids($oProject->id_project, \bids::STATUS_AUTOBID_REJECTED_TEMPORARILY)) {
            foreach ($aAutoBidList as $aAutobid) {
                if ($oBid->get($aAutobid['id_bid'])) {
                    $this->oBidManager->reBidAutoBidOrReject($oBid, $currentRate, $iMode, $bSendNotification);
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
        /** @var \bids $oBid */
        $oBid = $this->oEntityManager->getRepository('bids');
        /** @var \loans $oLoan */
        $oLoan = $this->oEntityManager->getRepository('loans');
        /** @var \lenders_accounts $oLenderAccount */
        $oLenderAccount = $this->oEntityManager->getRepository('lenders_accounts');

        $this->addProjectStatus(\users::USER_ID_CRON, \projects_status::BID_TERMINATED, $oProject);
        $this->reBidAutoBidDeeply($oProject, BidManager::MODE_REBID_AUTO_BID_CREATE, true);
        $this->addProjectStatus(\users::USER_ID_CRON, \projects_status::FUNDE, $oProject);

        if ($this->oLogger instanceof LoggerInterface) {
            $this->oLogger->info('Project ' . $oProject->id_project . ' is now changed to status funded', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project));
        }

        $aBidList    = $oBid->select('id_project = ' . $oProject->id_project . ' AND status = ' . \bids::STATUS_BID_PENDING, 'rate ASC, ordre ASC');
        $iBidBalance = 0;

        $iBidNbTotal   = count($aBidList);
        $iTreatedBitNb = 0;

        if ($this->oLogger instanceof LoggerInterface) {
            $this->oLogger->info($iBidNbTotal . ' bids created (project ' . $oProject->id_project . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project));
        }

        foreach ($aBidList as $aBid) {
            $oBid->get($aBid['id_bid']);
            if ($iBidBalance < $oProject->amount) {
                $iBidBalance += ($aBid['amount'] / 100);

                if ($iBidBalance > $oProject->amount) {
                    $fAmountToCredit = $iBidBalance - $oProject->amount;
                    $this->oBidManager->rejectPartially($oBid, $fAmountToCredit);
                } else {
                    $oBid->status = \bids::STATUS_BID_ACCEPTED;
                    $oBid->update();
                }

                if ($this->oLogger instanceof LoggerInterface) {
                    $this->oLogger->info('The bid status has been updated to 1' . $aBid['id_bid'] . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project));
                }
            } else {
                $this->oBidManager->reject($oBid, true);
            }

            $iTreatedBitNb++;

            if ($this->oLogger instanceof LoggerInterface) {
                $this->oLogger->info($iTreatedBitNb . '/' . $iBidNbTotal . ' bids treated (project ' . $oProject->id_project . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project));
            }
        }

        $aLenderList = $oBid->getLenders($oProject->id_project, array(\bids::STATUS_BID_ACCEPTED));
        foreach ($aLenderList as $aLender) {
            $iLenderId   = $aLender['id_lender_account'];
            $aLenderBids = $oBid->select(
                'id_lender_account = ' . $iLenderId . ' AND id_project = ' . $oProject->id_project . ' AND status = ' . \bids::STATUS_BID_ACCEPTED,
                'rate DESC'
            );

            if ($oLenderAccount->isNaturalPerson($iLenderId)) {
                $fLoansLenderSum = 0;
                $fInterests      = 0;
                $bIFPContract    = true;
                $aBidIFP         = array();

                foreach ($aLenderBids as $iIndex => $aBid) {
                    $fBidAmount = $aBid['amount'] / 100;

                    if (true === $bIFPContract && ($fLoansLenderSum + $fBidAmount) <= \loans::IFP_AMOUNT_MAX) {
                        $fInterests += $aBid['rate'] * $fBidAmount;
                        $fLoansLenderSum += $fBidAmount;
                        $aBidIFP[] = array(
                            'bid_id' => $aBid['id_bid'],
                            'amount' => $fBidAmount
                        );
                    } else {
                        // Greater than \loans::IFP_AMOUNT_MAX ? create BDC loan, split it if needed.
                        $bIFPContract = false;
                        $fDiff        = $fLoansLenderSum + $fBidAmount - \loans::IFP_AMOUNT_MAX;

                        $oLoan->unsetData();
                        $oLoan->addAcceptedBid($aBid['id_bid'], $fDiff);
                        $oLoan->id_lender        = $iLenderId;
                        $oLoan->id_project       = $oProject->id_project;
                        $oLoan->amount           = $fDiff * 100;
                        $oLoan->rate             = $aBid['rate'];
                        $oLoan->id_type_contract = \loans::TYPE_CONTRACT_BDC;
                        $this->oLoanManager->create($oLoan);

                        $fRest = $fBidAmount - $fDiff;
                        if (0 < $fRest) {
                            $fInterests += $aBid['rate'] * $fRest;
                            $aBidIFP[] = array(
                                'bid_id' => $aBid['id_bid'],
                                'amount' => $fRest
                            );
                        }
                        $fLoansLenderSum = \loans::IFP_AMOUNT_MAX;
                    }
                }

                // Create IFP loan from the grouped bids
                $oLoan->unsetData();
                foreach ($aBidIFP as $aAcceptedBid) {
                    $oLoan->addAcceptedBid($aAcceptedBid['bid_id'], $aAcceptedBid['amount']);
                }
                $oLoan->id_lender        = $iLenderId;
                $oLoan->id_project       = $oProject->id_project;
                $oLoan->amount           = $fLoansLenderSum * 100;
                $oLoan->rate             = round($fInterests / $fLoansLenderSum, 2);
                $oLoan->id_type_contract = \loans::TYPE_CONTRACT_IFP;
                $this->oLoanManager->create($oLoan);
            } else {
                foreach ($aLenderBids as $aBid) {
                    $oLoan->unsetData();
                    $oLoan->addAcceptedBid($aBid['id_bid'], $aBid['amount'] / 100);
                    $oLoan->id_lender        = $iLenderId;
                    $oLoan->id_project       = $oProject->id_project;
                    $oLoan->amount           = $aBid['amount'];
                    $oLoan->rate             = $aBid['rate'];
                    $oLoan->id_type_contract = \loans::TYPE_CONTRACT_BDC;
                    $this->oLoanManager->create($oLoan);
                }
            }
        }
    }

    public function treatFundFailed(\projects $oProject)
    {
        /** @var \bids $oBid */
        $oBid = $this->oEntityManager->getRepository('bids');

        // On passe le projet en funding ko
        $this->addProjectStatus(\users::USER_ID_CRON, \projects_status::FUNDING_KO, $oProject);

        $aBidList      = $oBid->select('id_project = ' . $oProject->id_project, 'rate ASC, ordre ASC');
        $iBidNbTotal   = count($aBidList);
        $iTreatedBitNb = 0;

        if ($this->oLogger instanceof LoggerInterface) {
            $this->oLogger->info($iBidNbTotal . 'bids in total (project ' . $oProject->id_project . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project));
        }

        foreach ($aBidList as $aBid) {
            $oBid->get($aBid['id_bid'], 'id_bid');
            $this->oBidManager->reject($oBid, true);
            $iTreatedBitNb++;

            if ($this->oLogger instanceof LoggerInterface) {
                $this->oLogger->info($iTreatedBitNb . '/' . $iBidNbTotal . 'bids treated (project ' . $oProject->id_project . ')', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project));
            }
        }
    }

    public function createRepaymentSchedule(\projects $oProject)
    {
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '512M');

        /** @var \loans $oLoan */
        $oLoan = $this->oEntityManager->getRepository('loans');
        /** @var \projects_status $oProjectStatus */
        $oProjectStatus = $this->oEntityManager->getRepository('projects_status');
        /** @var \lenders_accounts $oLenderAccount */
        $oLenderAccount = $this->oEntityManager->getRepository('lenders_accounts');
        /** @var \echeanciers $oRepaymentSchedule */
        $oRepaymentSchedule = $this->oEntityManager->getRepository('echeanciers');
        /** @var \clients_adresses $oClientAdresse */
        $oClientAdresse = $this->oEntityManager->getRepository('clients_adresses');
        /** @var \clients $oClient */
        $oClient = $this->oEntityManager->getRepository('clients');

        $oProjectStatus->getLastStatut($oProject->id_project);

        if ($oProjectStatus->status == \projects_status::FUNDE) {
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

    public function createPaymentSchedule(\projects $oProject)
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
        $taxType = $this->loadData('tax_type');

        $taxRate = $taxType->getTaxRateByCountry('fr');
        $fVAT    = bcdiv($taxRate[\tax_type::TYPE_VAT], 100, 2);

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

    public function getProjectEndDate(\projects $oProject)
    {
        /** @var \settings $oSettings */
        $oSettings = $this->oEntityManager->getRepository('settings');
        $oEndDate  = new \DateTime($oProject->date_retrait_full);
        if ($oProject->date_fin != '0000-00-00 00:00:00') {
            $oEndDate = new \DateTime($oProject->date_fin);
        }
        if ($oEndDate->format('H') === '00') {
            $oSettings->get('Heure fin periode funding', 'type');
            $iEndHour = (int)$oSettings->value;
            $oEndDate->add(new \DateInterval('PT' . $iEndHour . 'H'));
        }
        return $oEndDate;
    }

    public function addProjectStatus($iUserId, $iProjectStatus, \projects $oProject, $iReminderNumber = 0, $sContent = '')
    {
        /** @var \projects_status_history $oProjectsStatusHistory */
        $oProjectsStatusHistory = $this->oEntityManager->getRepository('projects_status_history');
        /** @var \projects_status $oProjectStatus */
        $oProjectStatus = $this->oEntityManager->getRepository('projects_status');
        $oProjectStatus->get($iProjectStatus, 'status');

        if ($this->oLogger instanceof LoggerInterface && empty($oProjectStatus->id_project_status)) {
            $this->oLogger->error('Trying to insert empty status for project ' . $oProject->id_project . ' - Trace: ' . serialize(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)), array('id_project' => $oProject->id_project));
        }

        $oProjectsStatusHistory->id_project        = $oProject->id_project;
        $oProjectsStatusHistory->id_project_status = $oProjectStatus->id_project_status;
        $oProjectsStatusHistory->id_user           = $iUserId;
        $oProjectsStatusHistory->numero_relance    = $iReminderNumber;
        $oProjectsStatusHistory->content           = $sContent;
        $oProjectsStatusHistory->create();

        $this->projectStatusUpdateTrigger($oProjectStatus, $oProject);
    }

    private function projectStatusUpdateTrigger(\projects_status $oProjectStatus, \projects $oProject)
    {
        /** @var \settings $oSettings */
        $oSettings = $this->oEntityManager->getRepository('settings');

        switch ($oProjectStatus->status) {
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
        if ($oProject->status_solde == 0) {
            $oFunded    = new \DateTime();
            $oPublished = new \DateTime($oProject->date_publication_full);
            if ($oFunded < $oPublished) {
                $oFunded = $oPublished;
            }

            $oProject->date_funded  = $oFunded->format('Y-m-d H:i:s');
            $oProject->status_solde = 1;
            $oProject->update();

            $this->oMailerManager->sendFundedToBorrower($oProject);
            $this->oMailerManager->sendFundedToStaff($oProject);
        }
    }

    /**
     * @param \projects $project
     * @return string
     */
    public function getBorrowerBankTransferLabel(\projects $project)
    {
        /** @var \companies $company */
        $company = $this->oEntityManager->getRepository('companies');
        $company->get($project->id_company);

        return 'UNILEND' . str_pad($project->id_project, 6, 0, STR_PAD_LEFT) . 'E' . trim($company->siren);
    }

    /**
     * @param \projects $oProject
     *
     * @return array
     */
    public function getBidsStatistics(\projects $oProject)
    {
        /** @var \bids $oBid */
        $oBid = $this->oEntityManager->getRepository('bids');
        return $oBid->getBidsStatistics($oProject->id_project);
    }
}
