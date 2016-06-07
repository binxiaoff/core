<?php
namespace Unilend\Service;

use Unilend\core\Loader;
use \Symfony\Bridge\Monolog\Logger;
use Unilend\Service\Simulator\EntityManager;

class ProjectManager
{
    /** @var NotificationManager */
    private $oNotificationManager;

    /** @var \ficelle */
    private $oFicelle;

    /** @var array */
    private $aConfig;

    /** @var \dates */
    private $oDate;

    /** @var Logger */
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
        $this->aConfig = Loader::loadConfig();

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
     * @param Logger $oLogger
     */
    public function setLogger(Logger $oLogger)
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
        if ($this->oLogger instanceof Logger) {
            $this->oLogger->info('id_project=' . $oProject->id_project . ' Check bid info: ' . var_export($aLogContext), array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project));
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

        $this->reBidAutoBidDeeply($oProject, BidManager::MODE_REBID_AUTO_BID_CREATE, true);
        $this->addProjectStatus(\users::USER_ID_CRON, \projects_status::FUNDE, $oProject);

        if ($this->oLogger instanceof Logger) {
            $this->oLogger->info('id_project=' . $oProject->id_project . ' is now changed to status funded', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project));
        }

        $aBidList    = $oBid->select('id_project = ' . $oProject->id_project . ' AND status = ' . \bids::STATUS_BID_PENDING, 'rate ASC, ordre ASC');
        $iBidBalance = 0;

        $iBidNbTotal   = count($aBidList);
        $iTreatedBitNb = 0;
        if ($this->oLogger instanceof Logger) {
            $this->oLogger->info('id_project=' . $oProject->id_project . ' : ' . $iBidNbTotal . ' bids in total', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project));
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
                if ($this->oLogger instanceof Logger) {
                    $this->oLogger->info('id_project=' . $oProject->id_project . ' : The bid (' . $aBid['id_bid'] . ') status has been updated to 1', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project));
                }
            } else { // Pour les encheres qui depassent on rend l'argent
                // On regarde si on a pas deja un remb pour ce bid
                $this->oBidManager->reject($oBid, true);
            }
            $iTreatedBitNb++;
            if ($this->oLogger instanceof Logger) {
                $this->oLogger->info('id_project=' . $oProject->id_project . ' : ' . $iTreatedBitNb . '/' . $iBidNbTotal . ' bids treated', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project));
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

        if ($this->oLogger instanceof Logger) {
            $this->oLogger->info('id_project=' . $oProject->id_project . ' : ' . $iBidNbTotal . 'bids in total', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project));
        }

        foreach ($aBidList as $aBid) {
            $oBid->get($aBid['id_bid'], 'id_bid');
            $this->oBidManager->reject($oBid, true);
            $iTreatedBitNb++;
            if ($this->oLogger instanceof Logger) {
                $this->oLogger->info('id_project=' . $oProject->id_project . ' : ' . $iTreatedBitNb . '/' . $iBidNbTotal . 'bids treated', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project));
            }
        }
    }

    public function createRepaymentSchedule(\projects $oProject)
    {
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '512M');

        /** @var \settings $oSettings */
        $oSettings = $this->oEntityManager->getRepository('settings');
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
            if ($this->oLogger instanceof Logger) {
                $this->oLogger->info('id_project=' . $oProject->id_project . ' : ' . $iLoanNbTotal . ' in total', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project));
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

                $aRepaymentSchedule = array();
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
                    if (in_array($oClient->type, array(\clients::TYPE_PERSON, \clients::TYPE_PERSON_FOREIGNER))) {
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

                    $aRepaymentSchedule[] = array(
                        'id_lender'                    => $l['id_lender'],
                        'id_project'                   => $oProject->id_project,
                        'id_loan'                      => $l['id_loan'],
                        'ordre'                        => $k,
                        'montant'                      => $e['repayment'] * 100,
                        'capital'                      => $e['capital'] * 100,
                        'interets'                     => $e['interest'] * 100,
                        'commission'                   => $e['commission'] * 100,
                        'tva'                          => $e['vat_amount'] * 100,
                        'prelevements_obligatoires'    => $montant_prelevements_obligatoires,
                        'contributions_additionnelles' => $montant_contributions_additionnelles,
                        'crds'                         => $montant_crds,
                        'csg'                          => $montant_csg,
                        'prelevements_solidarite'      => $montant_prelevements_solidarite,
                        'prelevements_sociaux'         => $montant_prelevements_sociaux,
                        'retenues_source'              => $montant_retenues_source,
                        'date_echeance'                => $dateEcheance,
                        'date_echeance_emprunteur'     => $dateEcheance_emprunteur,
                    );
                }
                $oRepaymentSchedule->multiInsert($aRepaymentSchedule);

                $iTreatedLoanNb++;
                if ($this->oLogger instanceof Logger) {
                    $this->oLogger->info(
                        'id_project=' . $oProject->id_project . ' : ' . $iTreatedLoanNb . '/' . $iLoanNbTotal . ' lender loan treated. ' . $k . ' repayment schedules created',
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

        $oSettings->get('TVA', 'type');
        $fVAT = $oSettings->value;

        $fAmount           = $oProject->amount;
        $iMonthNb          = $oProject->period;
        $aCommission       = \repayment::getRepaymentCommission($fAmount, $iMonthNb, $fCommissionRate, $fVAT);
        $aPaymentList      = $oRepaymentSchedule->getSumRembEmpruntByMonths($oProject->id_project);
        $iPaymentsNbTotal  = count($aPaymentList);
        $iTreatedPaymentNb = 0;

        if ($this->oLogger instanceof Logger) {
            $this->oLogger->info('id_project=' . $oProject->id_project . ' : ' . $iPaymentsNbTotal . ' in total', array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $oProject->id_project));
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
            if ($this->oLogger instanceof Logger) {
                $this->oLogger->info(
                    'id_project=' . $oProject->id_project . ' : borrower echeance (' . $oPaymentSchedule->id_echeancier_emprunteur . ') has been created. ' . $iTreatedPaymentNb . '/' . $iPaymentsNbTotal . 'traited',
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
        switch ($oProjectStatus->status) {
            case \projects_status::A_TRAITER:
                /** @var \settings $oSettings */
                $oSettings = $this->oEntityManager->getRepository('settings');
                $oSettings->get('Adresse notification inscription emprunteur', 'type');
                $this->oMailerManager->sendProjectNotificationToStaff('notification-depot-de-dossier', $oProject, trim($oSettings->value));
                break;
            case \projects_status::ATTENTE_ANALYSTE:
                $this->oMailerManager->sendProjectNotificationToStaff('notification-projet-a-traiter', $oProject, \email::EMAIL_ADDRESS_ANALYSTS);
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
