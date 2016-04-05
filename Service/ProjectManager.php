<?php
/**
 * Created by PhpStorm.
 * User: binxiao
 * Date: 09/02/2016
 * Time: 09:38
 */

namespace Unilend\Service;

use Unilend\core\Loader;
use Unilend\librairies\ULogger;

class ProjectManager
{
    /** @var NotificationManager */
    private $oNotificationManager;

    /** @var \email */
    private $oEmail;

    /** @var \ficelle */
    private $oFicelle;

    /** @var array */
    private $aConfig;

    /** @var \dates */
    private $oDate;

    /** @var \tnmp */
    private $oTNMP;

    /** @var ULogger */
    private $oLogger;

    /** @var BidManager */
    private $oBidManager;

    /** @var LoanManager */
    private $oLoanManager;

    /** @var AutoBidSettingsManager */
    private $oAutoBidSettingsManager;

    /** @var \jours_ouvres */
    private $oWorkingDay;

    public function __construct()
    {
        $this->aConfig = Loader::loadConfig();

        $this->oBidManager             = Loader::loadService('BidManager');
        $this->oLoanManager            = Loader::loadService('LoanManager');
        $this->oNotificationManager    = Loader::loadService('NotificationManager');
        $this->oAutoBidSettingsManager = Loader::loadService('AutoBidSettingsManager');

        $this->oNMP       = Loader::loadData('nmp');
        $this->oNMPDesabo = Loader::loadData('nmp_desabo');

        $this->oTNMP       = Loader::loadLib('tnmp', array($this->oNMP, $this->oNMPDesabo, $this->aConfig['env']));
        $this->oEmail      = Loader::loadLib('email');
        $this->oFicelle    = Loader::loadLib('ficelle');
        $this->oDate       = Loader::loadLib('dates');
        $this->oWorkingDay = Loader::loadLib('jours_ouvres');

        $this->sLanguage = 'fr';
    }

    /**
     * @param ULogger $oLogger
     */
    public function setLogger(ULogger $oLogger)
    {
        $this->oLogger = $oLogger;
    }

    public function prePublish(\projects $oProject)
    {
        /** @var \projects_status_history $oProjectsStatusHistory */
        $oProjectsStatusHistory = Loader::loadData('projects_status_history');
        $this->checkAutoBidBalance($oProject);
        $this->autoBid($oProject);
        $oProjectsStatusHistory->addStatus(\users::USER_ID_CRON, \projects_status::AUTO_BID_PLACED, $oProject->id_project);
    }

    public function publish(\projects $oProjects)
    {
        /** @var \projects_status_history $oProjectsStatusHistory */
        $oProjectsStatusHistory = Loader::loadData('projects_status_history');
        $oProjectsStatusHistory->addStatus(\users::USER_ID_CRON, \projects_status::EN_FUNDING, $oProjects->id_project);
    }

    public function checkBids(\projects $oProject)
    {
        /** @var \bids $oBid */
        $oBid = Loader::loadData('bids');
        /** @var \bids_logs $oBidLog */
        $oBidLog = Loader::loadData('bids_logs');

        $aLogContext      = array();
        $bBidsLogs        = false;
        $nb_bids_ko       = 0;
        $iBidsAccumulated = 0;
        $iBorrowAmount    = $oProject->amount;


        $iBidsNbPending = $oBid->counter('id_project = ' . $oProject->id_project . ' AND status = 0');
        $iBidsNbTotal   = $oBid->counter('id_project = ' . $oProject->id_project);
        $iBidTotal      = $oBid->getSoldeBid($oProject->id_project);

        $oBidLog->debut = date('Y-m-d H:i:s');

        if ($iBidTotal >= $iBorrowAmount) {
            foreach ($oBid->select('id_project = ' . $oProject->id_project . ' AND status = 0', 'rate ASC, ordre ASC') as $aBid) {
                if ($iBidsAccumulated < $iBorrowAmount) {
                    $iBidsAccumulated += ($aBid['amount'] / 100);
                } else {
                    $bBidsLogs = true;
                    $oBid->get($aBid['id_bid']);

                    if (0 == $oBid->id_autobid) { // non-auto-bid
                        $this->oBidManager->reject($oBid);
                    } else {
                        // For a autobid, we don't send reject notification, we don't create payback transaction, either. So we just flag it here as reject temporarily
                        $oBid->status = \bids::STATUS_AUTOBID_REJECTED_TEMPORARILY;
                    }

                    $nb_bids_ko++;
                    $oBid->update();
                }

                if (1 != $oBid->checked) {
                    $oBid->checked = 1;
                    $oBid->update();
                }
            }

            $aLogContext['Project ID']    = $oProject->id_project;
            $aLogContext['Balance']       = $iBidTotal;
            $aLogContext['Rejected bids'] = $nb_bids_ko;
        }

        if ($bBidsLogs == true) {
            $oBidLog->id_project      = $oProject->id_project;
            $oBidLog->nb_bids_encours = $iBidsNbPending;
            $oBidLog->nb_bids_ko      = $nb_bids_ko;
            $oBidLog->total_bids      = $iBidsNbTotal;
            $oBidLog->total_bids_ko   = $oBid->counter('id_project = ' . $oProject->id_project . ' AND status = 2');
            $oBidLog->rate_max        = $oBid->getProjectMaxRate($oProject->id_project);
            $oBidLog->fin             = date('Y-m-d H:i:s');
            $oBidLog->create();
        }
        if ($this->oLogger instanceof ULogger) {
            $this->oLogger->addRecord(ULogger::INFO, 'Project ID: ' . $oProject->id_project, $aLogContext);
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
        $oProjectStatus = Loader::loadData('projects_status');
        if ($oProjectStatus->getLastStatut($oProject->id_project)) {
            if ($oProjectStatus->status == \projects_status::A_FUNDER) {
                $this->bidAllAutoBid($oProject);
            } elseif ($oProjectStatus->status == \projects_status::EN_FUNDING) {
                $this->reBidAutoBid($oProject, BidManager::MODE_REBID_AUTO_BID_CREATE);
            }
        }
    }

    private function bidAllAutoBid(\projects $oProject)
    {
        /** @var \autobid $oAutoBid */
        $oAutoBid = Loader::loadData('autobid');

        $aPeriod = $this->oAutoBidSettingsManager->getPeriod($oProject->period);
        if (false === empty($aPeriod)) {
            $iOffset = 0;
            $iLimit  = 100;
            while ($aAutoBidList = $this->oAutoBidSettingsManager->getSettings(null, $oProject->risk, $aPeriod['id_period'], array(\autobid::STATUS_ACTIVE), null, $iLimit, $iOffset)) {
                $iOffset += $iLimit;
                foreach ($aAutoBidList as $aAutoBidSetting) {
                    if ($oAutoBid->get($aAutoBidSetting['id_autobid'])) {
                        $this->oBidManager->bidByAutoBidSettings($oAutoBid, $oProject, \bids::BID_RATE_MAX);
                    }
                }
            }

            /** @var \bids $oBid */
            $oBid = Loader::loadData('bids');
            $oBid->shuffleAutoBidOrder($oProject->id_project);
        }
    }

    public function checkAutoBidBalance(\projects $oProject)
    {
        /** @var \transactions $oTransaction */
        $oTransaction = Loader::loadData('transactions');
        /** @var \clients $oClient */
        $oClient = Loader::loadData('clients');
        /** @var \lenders_accounts $oLenderAccount */
        $oLenderAccount = Loader::loadData('lenders_accounts');

        $aPeriod = $this->oAutoBidSettingsManager->getPeriod($oProject->period);
        if (false === empty($aPeriod)) {
            $iLimit  = 100;
            $iOffset = 0;
            while ($aAutoBidList = $this->oAutoBidSettingsManager->getSettings(null, $oProject->risk, $aPeriod['id_period'], array(\autobid::STATUS_ACTIVE), null, $iLimit, $iOffset)) {
                $iOffset += $iLimit;
                foreach ($aAutoBidList as $aAutoBidSetting) {
                    if (false === $oClient->get($aAutoBidSetting['id_client'])
                        && false === $oLenderAccount->get($aAutoBidSetting['id_lender'])
                        && false === $this->oAutoBidSettingsManager->isOn($oLenderAccount)
                    ) {
                        continue;
                    }
                    $iBalance = $oTransaction->getSolde($oClient->id_client);

                    if ($iBalance < $aAutoBidSetting['amount']) {
                        $this->oNotificationManager->create(
                            \notifications::TYPE_AUTOBID_BALANCE_INSUFFICIENT,
                            \clients_gestion_type_notif::TYPE_AUTOBID_BALANCE_INSUFFICIENT,
                            $oClient->id_client,
                            'sendAutoBidBalanceInsufficient',
                            $oProject->id_project
                        );
                    } elseif ($iBalance < (\autobid::THRESHOLD_AUTO_BID_BALANCE_LOW * $aAutoBidSetting['amount'])) {
                        $this->oNotificationManager->create(
                            \notifications::TYPE_AUTOBID_BALANCE_LOW,
                            \clients_gestion_type_notif::TYPE_AUTOBID_BALANCE_LOW,
                            $oClient->id_client,
                            'sendAutoBidBalanceLow',
                            $oProject->id_project
                        );
                    }
                }
            }
        }
    }

    private function reBidAutoBid(\projects $oProject, $iMode)
    {
        /** @var \settings $oSettings */
        $oSettings = Loader::loadData('settings');
        /** @var \bids $oBid */
        $oBid = Loader::loadData('bids');

        $oSettings->get('Auto-bid step', 'type');
        $fStep        = (float)$oSettings->value;
        $fCurrentRate = (float)$oBid->getProjectMaxRate($oProject->id_project) - $fStep;

        while ($aAutoBidList = $oBid->getAutoBids($oProject->id_project, \bids::STATUS_AUTOBID_REJECTED_TEMPORARILY)) {
            foreach ($aAutoBidList as $aAutobid) {
                if ($oBid->get($aAutobid['id_bid'])) {
                    $this->oBidManager->reBidAutoBidOrReject($oBid, $fCurrentRate, $iMode);
                }
            }
        }
    }

    private function reBidAutoBidDeeply(\projects $oProject, $iMode)
    {
        /** @var \bids $oBid */
        $oBid = Loader::loadData('bids');
        $this->checkBids($oProject);
        $aRefusedAutoBid = $oBid->getAutoBids($oProject->id_project, \bids::STATUS_AUTOBID_REJECTED_TEMPORARILY, 1);
        if (false === empty($aRefusedAutoBid)) {
            $this->reBidAutoBid($oProject, $iMode);
            $this->reBidAutoBidDeeply($oProject, $iMode);
        }
    }

    public function buildLoans(\projects $oProject)
    {
        /** @var \bids $oBid */
        $oBid = Loader::loadData('bids');
        /** @var \loans $oLoan */
        $oLoan = Loader::loadData('loans');
        /** @var \projects_status_history $oProjectStatusHistory */
        $oProjectStatusHistory = Loader::loadData('projects_status_history');
        /** @var \lenders_accounts $oLenderAccount */
        $oLenderAccount = Loader::loadData('lenders_accounts');

        $this->reBidAutoBidDeeply($oProject, BidManager::MODE_REBID_AUTO_BID_CREATE);

        $oProjectStatusHistory->addStatus(\users::USER_ID_CRON, \projects_status::FUNDE, $oProject->id_project);

        if ($this->oLogger instanceof ULogger) {
            $this->oLogger->addRecord(ULogger::INFO, 'project : ' . $oProject->id_project . ' is now changed to status funded.');
        }

        $aBidList    = $oBid->select('id_project = ' . $oProject->id_project . ' AND status = ' . \bids::STATUS_BID_PENDING, 'rate ASC, ordre ASC');
        $iBidBalance = 0;

        $iBidNbTotal   = count($aBidList);
        $iTreatedBitNb = 0;
        if ($this->oLogger instanceof ULogger) {
            $this->oLogger->addRecord(ULogger::INFO, 'project : ' . $oProject->id_project . ' : ' . $iBidNbTotal . ' bids in total.');
        }
        foreach ($aBidList as $aBid) {
            $oBid->get($aBid['id_bid']);
            if ($iBidBalance < $oProject->amount) {
                $iBidBalance += ($aBid['amount'] / 100);
                // Pour la partie qui depasse le montant de l'emprunt (ça c'est que pour le mec à qui on découpé son bid)
                if ($iBidBalance > $oProject->amount) {
                    $fAmountToCredit = $iBidBalance - $oProject->amount;
                    $this->oBidManager->rejectPartially($oBid, $fAmountToCredit);
                } else {
                    $oBid->status = \bids::STATUS_BID_ACCEPTED;
                    $oBid->update();
                }
                if ($this->oLogger instanceof ULogger) {
                    $this->oLogger->addRecord(ULogger::INFO, 'project : ' . $oProject->id_project . ' : The bid (' . $aBid['id_bid'] . ') status has been updated to 1');
                }
            } else { // Pour les encheres qui depassent on rend l'argent
                // On regarde si on a pas deja un remb pour ce bid
                $this->oBidManager->reject($oBid);
            }
            $iTreatedBitNb++;
            if ($this->oLogger instanceof ULogger) {
                $this->oLogger->addRecord(ULogger::INFO, 'project : ' . $oProject->id_project . ' : ' . $iTreatedBitNb . '/' . $iBidNbTotal . ' bids treated.');
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
        /** @var \projects_status_history $oProjectStatusHistory */
        $oProjectStatusHistory = Loader::loadData('projects_status_history');
        /** @var \bids $oBid */
        $oBid = Loader::loadData('bids');

        // On passe le projet en funding ko
        $oProjectStatusHistory->addStatus(\users::USER_ID_CRON, \projects_status::FUNDING_KO, $oProject->id_project);

        $aBidList      = $oBid->select('id_project = ' . $oProject->id_project, 'rate ASC, ordre ASC');
        $iBidNbTotal   = count($aBidList);
        $iTreatedBitNb = 0;

        if ($this->oLogger instanceof ULogger) {
            $this->oLogger->addRecord(ULogger::INFO, 'project : ' . $oProject->id_project . ' : ' . $iBidNbTotal . 'bids in total.');
        }

        foreach ($aBidList as $aBid) {
            $oBid->get($aBid['id_bid'], 'id_bid');
            $this->oBidManager->reject($oBid);
            $iTreatedBitNb++;
            if ($this->oLogger instanceof ULogger) {
                $this->oLogger->addRecord(ULogger::INFO, 'project : ' . $oProject->id_project . ' : ' . $iTreatedBitNb . '/' . $iBidNbTotal . 'bids treated.');
            }
        }
    }

    public function createRepaymentSchedule(\projects $oProject)
    {
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '512M');

        /** @var \settings $oSettings */
        $oSettings = Loader::loadData('settings');
        /** @var \loans $oLoan */
        $oLoan = Loader::loadData('loans');
        /** @var \projects_status $oProjectStatus */
        $oProjectStatus = Loader::loadData('projects_status');
        /** @var \lenders_accounts $oLenderAccount */
        $oLenderAccount = Loader::loadData('lenders_accounts');
        /** @var \echeanciers $oRepaymentSchedule */
        $oRepaymentSchedule = Loader::loadData('echeanciers');
        /** @var \clients_adresses $oClientAdresse */
        $oClientAdresse = Loader::loadData('clients_adresses');
        /** @var \clients $oClient */
        $oClient = Loader::loadData('clients');

        $oSettings->get('Commission remboursement', 'type');
        $commission = $oSettings->value;

        $oSettings->get('TVA', 'type');
        $tva = $oSettings->value;

        $oProjectStatus->getLastStatut($oProject->id_project);

        if ($oProjectStatus->status == \projects_status::FUNDE) {
            $lLoans = $oLoan->select('id_project = ' . $oProject->id_project);

            $iLoanNbTotal   = count($lLoans);
            $iTreatedLoanNb = 0;
            if ($this->oLogger instanceof ULogger) {
                $this->oLogger->addRecord(ULogger::INFO, 'project : ' . $oProject->id_project . ' : ' . $iLoanNbTotal . ' in total.');
            }

            foreach ($lLoans as $l) {
                $oLenderAccount->get($l['id_lender'], 'id_lender_account');
                $oClient->get($oLenderAccount->id_client_owner, 'id_client');

                $oClientAdresse->get($oLenderAccount->id_client_owner, 'id_client');

                $oLoan->get($l['id_loan']);
                $tabl = $oLoan->getRepaymentSchedule($commission, $tva);

                $aRepaymentSchedule = array();
                foreach ($tabl['repayment_schedule'] as $k => $e) {
                    $dateEcheance = $this->oDate->dateAddMoisJoursV3($oProject->date_fin, $k);
                    $dateEcheance = date('Y-m-d H:i', $dateEcheance) . ':00';

                    $dateEcheance_emprunteur = $this->oDate->dateAddMoisJoursV3($oProject->date_fin, $k);
                    $dateEcheance_emprunteur = $this->oWorkingDay->display_jours_ouvres($dateEcheance_emprunteur, 6);
                    $dateEcheance_emprunteur = date('Y-m-d H:i', $dateEcheance_emprunteur) . ':00';

                    $aRepaymentSchedule[] = array(
                        'id_lender'                    => $l['id_lender'],
                        'id_project'                   => $oProject->id_project,
                        'id_loan'                      => $l['id_loan'],
                        'ordre'                        => $k,
                        'montant'                      => bcmul($e['repayment'], 100),
                        'capital'                      => bcmul($e['capital'], 100),
                        'interets'                     => bcmul($e['interest'], 100),
                        'commission'                   => bcmul($e['commission'], 100),
                        'tva'                          => bcmul($e['vat_amount'], 100),
                        'date_echeance'                => $dateEcheance,
                        'date_echeance_emprunteur'     => $dateEcheance_emprunteur,
                    );
                }
                $oRepaymentSchedule->multiInsert($aRepaymentSchedule);

                $iTreatedLoanNb++;
                if ($this->oLogger instanceof ULogger) {
                    $this->oLogger->addRecord(
                        ULogger::INFO,
                        'project : ' . $oProject->id_project . ' : ' . $iTreatedLoanNb . '/' . $iLoanNbTotal . ' lender loan treated. ' . $k . ' repayment schedules created.'
                    );
                }
            }
        }
    }

    public function createPaymentSchedule(\projects $oProject)
    {
        ini_set('memory_limit', '512M');

        /** @var \echeanciers_emprunteur $oPaymentSchedule */
        $oPaymentSchedule = Loader::loadData('echeanciers_emprunteur');
        /** @var \echeanciers $oRepaymentSchedule */
        $oRepaymentSchedule = Loader::loadData('echeanciers');
        /** @var \settings $oSettings */
        $oSettings = Loader::loadData('settings');

        $oSettings->get('Commission remboursement', 'type');
        $fCommissionRate = $oSettings->value;

        $oSettings->get('TVA', 'type');
        $fVAT = $oSettings->value;

        $fAmount           = $oProject->amount;
        $iMonthNb          = $oProject->period;
        $aCommission       = \repayment::getRepaymentCommission($fAmount, $iMonthNb, $fCommissionRate, $fVAT);
        $aPaymentList      = $oRepaymentSchedule->getSumRembEmpruntByMonths($oProject->id_project);
        $iPaymentsNbTotal  = count($aPaymentList);
        $iTreatedPaymentNb = 0;

        if ($this->oLogger instanceof ULogger) {
            $this->oLogger->addRecord(ULogger::INFO, 'project : ' . $oProject->id_project . ' : ' . $iPaymentsNbTotal . ' in total.');
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
            if ($this->oLogger instanceof ULogger) {
                $this->oLogger->addRecord(
                    ULogger::INFO,
                    'project : ' . $oProject->id_project . ' : borrower echeance (' . $oPaymentSchedule->id_echeancier_emprunteur . ') has been created. ' . $iTreatedPaymentNb . '/' . $iPaymentsNbTotal . 'traited'
                );
            }
        }

    }

    public static function getWeightedAvgRate(\projects $oProject)
    {
        /** @var \projects_status $oProjectStatus */
        $oProjectStatus = Loader::loadData('projects_status');
        $oProjectStatus->getLastStatut($oProject->id_project);
        if ($oProjectStatus->status == \projects_status::EN_FUNDING) {
            return self::getWeightedAvgRateFromBid($oProject);
        } elseif ($oProjectStatus->status == \projects_status::FUNDE) {
            return self::getWeightedAvgRateFromLoan($oProject);
        } else {
            return false;
        }
    }

    private static function getWeightedAvgRateFromLoan(\projects $oProject)
    {
        /** @var \loans $oLoan */
        $oLoan          = Loader::loadData('loans');
        $iInterestTotal = 0;
        $iCapitalTotal  = 0;
        foreach ($oLoan->select('id_project = ' . $oProject->id_project) as $aLoan) {
            $iInterestTotal += $aLoan['rate'] * $aLoan['amount'];
            $iCapitalTotal += $aLoan['amount'];
        }
        return ($iInterestTotal / $iCapitalTotal);
    }

    private static function getWeightedAvgRateFromBid(\projects $oProject)
    {
        /** @var \bids $oBid */
        $oBid           = Loader::loadData('bids');
        $iInterestTotal = 0;
        $iCapitalTotal  = 0;
        foreach ($oBid->select('id_project = ' . $oProject->id_project . ' AND status = 0') as $aLoan) {
            $iInterestTotal += $aLoan['rate'] * $aLoan['amount'];
            $iCapitalTotal += $aLoan['amount'];
        }
        return ($iInterestTotal / $iCapitalTotal);
    }

    public static function getProjectEndDate(\projects $oProject)
    {
        /** @var \settings $oSettings */
        $oSettings = Loader::loadData('settings');
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
}
