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
    /** @var AutoBidManager */
    private $oAutoBidManager;
    /** @var \email */
    private $oEmail;
    /** @var \ficelle */
    private $oFicelle;
    private $aConfig;
    /** @var \dates */
    private $oDate;
    /** @var  \tnmp */
    private $oTNMP;
    /** @var  ULogger */
    private $oLogger;
    /** @var  BidManager */
    private $oBidManager;
    /** @var  LoanManager */
    private $oLoanManager;
    /** @var \jours_ouvres */
    private $oWorkingDay;

    public function __construct()
    {
        $this->aConfig = Loader::loadConfig();

        $this->oAutoBidManager = Loader::loadService('AutoBidManager');
        $this->oBidManager     = Loader::loadService('BidManager');
        $this->oLoanManager    = Loader::loadService('LoanManager');

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

    public function publish(\projects $oProjects)
    {
        /** @var \projects_status_history $oProjectsStatusHistory */
        $oProjectsStatusHistory = Loader::loadData('projects_status_history');

        $oProjectsStatusHistory->addStatus(\users::USER_ID_CRON, \projects_status::AUTO_BID, $oProjects->id_project);
        $this->autoBid($oProjects);
        $oProjectsStatusHistory->addStatus(\users::USER_ID_CRON, \projects_status::EN_FUNDING, $oProjects->id_project);
    }

    public function checkBids(\projects $oProject)
    {
        /** @var \bids $oBid */
        $oBid = Loader::loadData('bids');
        /** @var \bids_logs $oBidLogi */
        $oBidLog = Loader::loadData('bids_logs');

        $aLogContext      = array();
        $bBidsLogs        = false;
        $nb_bids_ko       = 0;
        $iBidsAccumulated = 0;
        $iBorrowAmount    = $oProject->amount;


        $iBidsNbPending = $oBid->counter('id_project = ' . $oProject->id_project . ' AND status = 0');
        $iBidsNbTotal   = $oBid->counter('id_project = ' . $oProject->id_project);
        $iBidTotal      = $oBid->getSoldeBid($oProject->id_project);

        $oBidLog->debut      = date('Y-m-d H:i:s');
        $oBidLog->id_project = $oProject->id_project;

        if ($iBidTotal >= $iBorrowAmount) {
            foreach ($oBid->select('id_project = ' . $oProject->id_project . ' AND status = 0', 'rate ASC, added ASC') as $aBid) {
                if ($iBidsAccumulated < $iBorrowAmount) {
                    $iBidsAccumulated += ($aBid['amount'] / 100);
                } else { // Les bid qui depassent on leurs redonne leur argent et on met en ko
                    $bBidsLogs = true;
                    $oBid->get($aBid['id_bid']);

                    if (0 == $oBid->id_autobid) { // non-auto-bid
                        $this->oBidManager->reject($oBid);
                    } else {
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
            $oBidLog->nb_bids_encours = $iBidsNbPending;
            $oBidLog->nb_bids_ko      = $nb_bids_ko;
            $oBidLog->total_bids      = $iBidsNbTotal;
            $oBidLog->total_bids_ko   = $oBid->counter('id_project = ' . $oProject->id_project . ' AND status = 2');
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
        $oEndDate = new \DateTime($oProject->date_retrait_full);
        if ($oProject->date_fin != '0000-00-00 00:00:00') {
            $oEndDate = new \DateTime($oProject->date_fin);
        }
        if (new \DateTime() >= $oEndDate) {
            return false;
        }

        if ($oProjectStatus->getLastStatut($oProject->id_project)) {
            if ($oProjectStatus->status == \projects_status::AUTO_BID) {
                $this->bidAllAutoBid($oProject);
            } else {
                if ($oProjectStatus->status == \projects_status::EN_FUNDING) {
                    $this->refreshAllAutoBidRate($oProject);
                }
            }
        }
    }

    private function bidAllAutoBid($oProject)
    {
        /** @var \autobid_queue $oAutoBidQueue */
        $oAutoBidQueue = Loader::loadData('autobid_queue');
        /** @var \autobid $oAutoBid */
        $oAutoBid     = Loader::loadData('autobid');
        $iPeriod      = (int)$oProject->period;
        $sEvaluation  = $oProject->risk;
        $iCurrentRate = 10;

        $aAutoBidList = $oAutoBidQueue->getAutoBids($iPeriod, $sEvaluation, $iCurrentRate);
        foreach ($aAutoBidList as $aAutoBidSetting) {
            if ($oAutoBid->get($aAutoBidSetting['id_autobid'])) {
                $this->oAutoBidManager->bid($oAutoBid, $oProject, $iCurrentRate);
            }
        }
    }

    private function refreshAllAutoBidRate($oProject)
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
                    $this->oAutoBidManager->refreshRateOrReject($oBid, $fCurrentRate);
                }
            }
        }
    }

    public function cleanTempRefusedAutoBid(\projects $oProject)
    {
        /** @var \bids $oBid */
        $oBid = Loader::loadData('bids');
        $this->checkBids($oProject);
        $aRefusedAutoBid = $oBid->getAutoBids($oProject->id_project, \bids::STATUS_AUTOBID_REJECTED_TEMPORARILY, 1);
        if (false === empty($aRefusedAutoBid)) {
            $this->refreshAllAutoBidRate($oProject);
            $this->cleanTempRefusedAutoBid($oProject);
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

        $this->cleanTempRefusedAutoBid($oProject);

        // on passe le projet en fundé
        $oProjectStatusHistory->addStatus(\users::USER_ID_CRON, \projects_status::FUNDE, $oProject->id_project);

        if ($this->oLogger instanceof ULogger) {
            $this->oLogger->addRecord(ULogger::INFO, 'project : ' . $oProject->id_project . ' is now changed to status funded.');
        }

        $aBidList    = $oBid->select('id_project = ' . $oProject->id_project . ' AND status = ' . \bids::STATUS_BID_PENDING, 'rate ASC, added ASC');
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
                // Pour la partie qui depasse le montant de l'emprunt ( ca cest que pour le mec a qui on decoupe son montant)
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
            } else {// Pour les encheres qui depassent on rend l'argent
                // On regarde si on a pas deja un remb pour ce bid
                $this->oBidManager->reject($oBid);
            }
            $iTreatedBitNb++;
            if ($this->oLogger instanceof ULogger) {
                $this->oLogger->addRecord(ULogger::INFO, 'project : ' . $oProject->id_project . ' : ' . $iTreatedBitNb . '/' . $iBidNbTotal . ' bids treated.');
            }
        }

        // Traite the accepted bid by lender
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

        $aBidList      = $oBid->select('id_project = ' . $oProject->id_project, 'rate ASC,added ASC');
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

        $oSettings->get('EQ-Acompte d\'impôt sur le revenu', 'type');
        $prelevements_obligatoires = $oSettings->value;

        $oSettings->get('EQ-Contribution additionnelle au Prélèvement Social', 'type');
        $contributions_additionnelles = $oSettings->value;

        $oSettings->get('EQ-CRDS', 'type');
        $crds = $oSettings->value;

        $oSettings->get('EQ-CSG', 'type');
        $csg = $oSettings->value;

        $oSettings->get('EQ-Prélèvement de Solidarité', 'type');
        $prelevements_solidarite = $oSettings->value;

        $oSettings->get('EQ-Prélèvement social', 'type');
        $prelevements_sociaux = $oSettings->value;

        $oSettings->get('EQ-Retenue à la source', 'type');
        $retenues_source = $oSettings->value;

        $oProjectStatus->getLastStatut($oProject->id_project);

        // Si le projet est bien en funde on créer les echeances
        if ($oProjectStatus->status == \projects_status::FUNDE) {
            $lLoans = $oLoan->select('id_project = ' . $oProject->id_project);

            $iLoanNbTotal   = count($lLoans);
            $iTreatedLoanNb = 0;
            if ($this->oLogger instanceof ULogger) {
                $this->oLogger->addRecord(ULogger::INFO, 'project : ' . $oProject->id_project . ' : ' . $iLoanNbTotal . ' in total.');
            }

            // on parcourt les loans du projet en remboursement
            foreach ($lLoans as $l) {
                //////////////////////////////
                // Echeancier remboursement //
                //////////////////////////////

                $oLenderAccount->get($l['id_lender'], 'id_lender_account');
                $oClient->get($oLenderAccount->id_client_owner, 'id_client');

                $oClientAdresse->get($oLenderAccount->id_client_owner, 'id_client');

                // 0 : fr/fr
                // 1 : fr/resident etranger
                // 2 : no fr/resident etranger
                $etranger = 0;
                // fr/resident etranger
                if ($oClient->id_nationalite <= 1 && $oClientAdresse->id_pays_fiscal > 1) {
                    $etranger = 1;
                } // no fr/resident etranger
                elseif ($oClient->id_nationalite > 1 && $oClientAdresse->id_pays_fiscal > 1) {
                    $etranger = 2;
                }

                $oLoan->get($l['id_loan']);
                $tabl = $oLoan->getRepaymentSchedule($commission, $tva);

                // on crée les echeances de chaques preteurs
                foreach ($tabl['repayment_schedule'] as $k => $e) {
                    // Date d'echeance preteur
                    $dateEcheance = $this->oDate->dateAddMoisJoursV3($oProject->date_fin, $k);
                    $dateEcheance = date('Y-m-d H:i', $dateEcheance) . ':00';

                    // Date d'echeance emprunteur
                    $dateEcheance_emprunteur = $this->oDate->dateAddMoisJoursV3($oProject->date_fin, $k);
                    // on retire 6 jours ouvrés
                    $dateEcheance_emprunteur = $this->oWorkingDay->display_jours_ouvres($dateEcheance_emprunteur, 6);
                    $dateEcheance_emprunteur = date('Y-m-d H:i', $dateEcheance_emprunteur) . ':00';

                    // particulier
                    if (in_array($oClient->type, array(1, 3))) {
                        if ($etranger > 0) {
                            $montant_prelevements_obligatoires    = 0;
                            $montant_contributions_additionnelles = 0;
                            $montant_crds                         = 0;
                            $montant_csg                          = 0;
                            $montant_prelevements_solidarite      = 0;
                            $montant_prelevements_sociaux         = 0;

                            switch ($oLoan->id_type_contract) {
                                case \loans::TYPE_CONTRACT_BDC:
                                    $montant_retenues_source = round($retenues_source * $e['interest'], 2);
                                    break;
                                case \loans::TYPE_CONTRACT_IFP:
                                    $montant_retenues_source = 0;
                                    break;
                                default:
                                    $montant_retenues_source = 0;
                                    trigger_error('Unknown contract type: ' . $oLoan->id_type_contract, E_USER_WARNING);
                                    break;
                            }
                        } else {
                            if ($oLenderAccount->exonere == 1 // @todo should not be usefull and field should be deleted from DB but as long as it exists and BO interface is based on it, we must use it
                                && $oLenderAccount->debut_exoneration != '0000-00-00'
                                && $oLenderAccount->fin_exoneration != '0000-00-00'
                                && date('Y-m-d', strtotime($dateEcheance)) >= $oLenderAccount->debut_exoneration
                                && date('Y-m-d', strtotime($dateEcheance)) <= $oLenderAccount->fin_exoneration
                            ) {
                                $montant_prelevements_obligatoires = 0;
                            } else {
                                $montant_prelevements_obligatoires = round($prelevements_obligatoires * $e['interest'], 2);
                            }

                            $montant_contributions_additionnelles = round($contributions_additionnelles * $e['interest'], 2);
                            $montant_crds                         = round($crds * $e['interest'], 2);
                            $montant_csg                          = round($csg * $e['interest'], 2);
                            $montant_prelevements_solidarite      = round($prelevements_solidarite * $e['interest'], 2);
                            $montant_prelevements_sociaux         = round($prelevements_sociaux * $e['interest'], 2);
                            $montant_retenues_source              = 0;
                        }
                    } // entreprise
                    else {
                        $montant_prelevements_obligatoires    = 0;
                        $montant_contributions_additionnelles = 0;
                        $montant_crds                         = 0;
                        $montant_csg                          = 0;
                        $montant_prelevements_solidarite      = 0;
                        $montant_prelevements_sociaux         = 0;

                        switch ($oLoan->id_type_contract) {
                            case \loans::TYPE_CONTRACT_BDC:
                                $montant_retenues_source = round($retenues_source * $e['interest'], 2);
                                break;
                            case \loans::TYPE_CONTRACT_IFP:
                                $montant_retenues_source = 0;
                                break;
                            default:
                                $montant_retenues_source = 0;
                                trigger_error('Unknown contract type: ' . $oLoan->id_type_contract, E_USER_WARNING);
                                break;
                        }
                    }

                    $oRepaymentSchedule->id_lender                    = $l['id_lender'];
                    $oRepaymentSchedule->id_project                   = $oProject->id_project;
                    $oRepaymentSchedule->id_loan                      = $l['id_loan'];
                    $oRepaymentSchedule->ordre                        = $k;
                    $oRepaymentSchedule->montant                      = $e['repayment'] * 100;
                    $oRepaymentSchedule->capital                      = $e['capital'] * 100;
                    $oRepaymentSchedule->interets                     = $e['interest'] * 100;
                    $oRepaymentSchedule->commission                   = $e['commission'] * 100;
                    $oRepaymentSchedule->tva                          = $e['vat_amount'] * 100;
                    $oRepaymentSchedule->prelevements_obligatoires    = $montant_prelevements_obligatoires;
                    $oRepaymentSchedule->contributions_additionnelles = $montant_contributions_additionnelles;
                    $oRepaymentSchedule->crds                         = $montant_crds;
                    $oRepaymentSchedule->csg                          = $montant_csg;
                    $oRepaymentSchedule->prelevements_solidarite      = $montant_prelevements_solidarite;
                    $oRepaymentSchedule->prelevements_sociaux         = $montant_prelevements_sociaux;
                    $oRepaymentSchedule->retenues_source              = $montant_retenues_source;
                    $oRepaymentSchedule->date_echeance                = $dateEcheance;
                    $oRepaymentSchedule->date_echeance_emprunteur     = $dateEcheance_emprunteur;
                    $oRepaymentSchedule->create();
                }
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
            // Date d'echeance emprunteur
            $sPaymentDate = $this->oDate->dateAddMoisJoursV3($oProject->date_fin, $iIndex);
            // on retire 6 jours ouvrés
            $sPaymentDate = $this->oWorkingDay->display_jours_ouvres($sPaymentDate, 6);

            $sPaymentDate = date('Y-m-d H:i', $sPaymentDate) . ':00';

            $oPaymentSchedule->id_project               = $oProject->id_project;
            $oPaymentSchedule->ordre                    = $iIndex;
            $oPaymentSchedule->montant                  = $aPayment['montant'] * 100; // sum montant preteurs
            $oPaymentSchedule->capital                  = $aPayment['capital'] * 100; // sum capital preteurs
            $oPaymentSchedule->interets                 = $aPayment['interets'] * 100; // sum interets preteurs
            $oPaymentSchedule->commission               = $aCommission['commission_monthly'] * 100; // on recup com du projet
            $oPaymentSchedule->tva                      = $aCommission['vat_amount_monthly'] * 100; // et tva du projet
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


}