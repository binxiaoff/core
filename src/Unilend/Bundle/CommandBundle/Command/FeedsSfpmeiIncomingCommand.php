<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Unilend\core\Loader;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class FeedsSfpmeiIncomingCommand extends ContainerAwareCommand
{
    const FILE_ROOT_NAME = 'UNILEND-00040631007-';

    /** @var LoggerInterface $oLogger */
    private $oLogger;

    /** @var EntityManager $oEntityManager */
    private $oEntityManager;

    protected function configure()
    {
        $this
            ->setName('feeds:sfpmei_incoming')
            ->setDescription('Process the incoming files generated by SFPMEI')
            ->addOption('force-replay', 'f', InputOption::VALUE_NONE, 'To force the cron to replay.')
            ->setHelp(<<<EOF
The <info>feeds:sfpmei-incoming</info> command process the incoming feeds generated by SFPMEI.
<info>php bin/console feeds:sfpmei_incoming</info>
EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->oEntityManager = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var \receptions $receptions */
        $receptions = $this->oEntityManager->getRepository('receptions');
        /** @var \clients $clients */
        $clients = $this->oEntityManager->getRepository('clients');
        /** @var \transactions $transactions */
        $transactions = $this->oEntityManager->getRepository('transactions');
        /** @var \projects $projects */
        $projects = $this->oEntityManager->getRepository('projects');
        /** @var \companies $companies */
        $companies = $this->oEntityManager->getRepository('companies');
        /** @var \bank_unilend $bank_unilend */
        $bank_unilend = $this->oEntityManager->getRepository('bank_unilend');

        /** @var \settings $settings */
        $settings = $this->oEntityManager->getRepository('settings');
        $this->oEntityManager->getRepository('transactions_types');

        $this->oLogger = $this->getContainer()->get('monolog.logger.console');

        $aReceivedTransfersStatus = array(05, 18, 45, 13);
        $aEmittedTransfersStatus  = array(06, 21);
        $aRejectedTransfersStatus = array(12);

        $aEmittedLeviesStatus  = array(23, 25, 'A1', 'B1');
        $aRejectedLeviesStatus = array(10, 27, 'A3', 'B3');

        $sReceptionPath = $this->getContainer()->getParameter('path.sftp') . 'sfpmei/receptions/';
        $sFileContent   = @file_get_contents($sReceptionPath . self::FILE_ROOT_NAME . date('Ymd') . '.txt');

        switch ($sFileContent) {
            case false: {
                $this->oLogger->info('No SFPMEI incoming file to process in "' . $sReceptionPath . '"', array('class' => __CLASS__, 'function' => __FUNCTION__));
                break;
            }
            default : {
                $aReceivedData = $this->parseReceptionFile($sReceptionPath . self::FILE_ROOT_NAME . date('Ymd') . '.txt', $aEmittedLeviesStatus);
                $aReception    = $receptions->select('DATE(added) = "' . date('Y-m-d') . '"');

                if (false === empty($aReceivedData) && (empty($aReception) || $input->getOption('force-replay'))) {
                    $settings->get('Facebook', 'type');
                    $sFacebookLink = $settings->value;
                    $settings->get('Twitter', 'type');
                    $sTwitterLink = $settings->value;

                    foreach ($aReceivedData as $aRow) {
                        $transactions->unsetData();
                        $code = $aRow['codeOpInterbancaire'];

                        if (in_array($code, $aReceivedTransfersStatus)) {
                            $type                = 2;
                            $iBankTransferStatus = 1;
                            $iBankDebitStatus    = 0;
                        } elseif (in_array($code, $aEmittedTransfersStatus)) {
                            $type                = 2;
                            $iBankTransferStatus = 2;
                            $iBankDebitStatus    = 0;
                        } elseif (in_array($code, $aRejectedTransfersStatus)) {
                            $type                = 2;
                            $iBankTransferStatus = 3;
                            $iBankDebitStatus    = 0;
                        } elseif (in_array($code, $aEmittedLeviesStatus)) {
                            $type                = 1;
                            $iBankTransferStatus = 0;
                            $iBankDebitStatus    = 2;
                        } elseif (in_array($code, $aRejectedLeviesStatus)) {
                            $type                = 1;
                            $iBankTransferStatus = 0;
                            $iBankDebitStatus    = 3;
                        } else {
                            $type                = 4; // recap payline
                            $iBankTransferStatus = 0;
                            $iBankDebitStatus    = 0;
                        }
                        $motif = '';

                        for ($index = 1; $index <= 5; $index++) {
                            if (false === empty($aRow['libelleOpe' . $index])) {
                                $motif .= trim($aRow['libelleOpe' . $index]) . '<br>';
                            }
                        }

                        if (isset($aRow['unilend_bienvenue'])) {
                            $this->processWelcomeOffer($aRow, $transactions, $bank_unilend);
                        } else {
                            $receptions->id_client          = 0;
                            $receptions->id_project         = 0;
                            $receptions->status_bo          = 0;
                            $receptions->remb               = 0;
                            $receptions->motif              = $motif;
                            $receptions->montant            = $aRow['montant'];
                            $receptions->type               = $type;
                            $receptions->status_virement    = $iBankTransferStatus;
                            $receptions->status_prelevement = $iBankDebitStatus;
                            $receptions->ligne              = $aRow['ligne1'];
                            $receptions->create();

                            if ($type === 1 && $iBankDebitStatus === 2) {
                                $this->processDirectDebit($motif, $transactions, $projects, $companies, $clients, $receptions, $bank_unilend);
                            } elseif ($type === 2 && $iBankTransferStatus === 1) { // Received bank transfer
                                if (
                                    isset($aRow['libelleOpe3'])
                                    && 1 === preg_match('/RA-?([0-9]+)/', $aRow['libelleOpe3'], $aMatches)
                                    && $projects->get((int) $aMatches[1])
                                    && false === $transactions->get($receptions->id_reception, 'status = 1 AND etat = 1 AND id_virement')
                                ) {
                                    $this->processBorrowerAnticipatedRepayment($receptions, $transactions, $bank_unilend, $projects);
                                } elseif (isset($aRow['libelleOpe3']) && strstr($aRow['libelleOpe3'], 'REGULARISATION')) {
                                    $this->processRegulation($motif, $aRow['libelleOpe3'], $receptions, $projects, $companies, $transactions, $bank_unilend);
                                } else {
                                    $this->processLenderBankTransfer($motif, $receptions, $clients, $transactions, $sFacebookLink, $sTwitterLink);
                                }
                            } elseif ($type === 1 && $iBankDebitStatus === 3) {
                                $this->processBorrowerRepaymentRejection($aRow, $projects, $companies, $transactions, $receptions, $bank_unilend);
                            }
                        }
                    }
                }
                break;
            }
        }
    }

    /**
     * @param string $file
     * @param array $aEmittedLeviesStatus
     * @return array
     */
    private function parseReceptionFile($file, array $aEmittedLeviesStatus)
    {
        $aPattern = array(
            '{' => 0,
            'A' => 1,
            'B' => 2,
            'C' => 3,
            'D' => 4,
            'E' => 5,
            'F' => 6,
            'G' => 7,
            'H' => 8,
            'I' => 9,
            '}' => 0,
            'J' => 1,
            'K' => 2,
            'L' => 3,
            'M' => 4,
            'N' => 5,
            'O' => 6,
            'P' => 7,
            'Q' => 8,
            'R' => 9
        );

        $aResult      = array();
        $aRestriction = array();
        $rHandler     = fopen($file, 'r');

        if ($rHandler) {
            $i = 0;
            while (($sLine = fgets($rHandler)) !== false) {
                if (false !== stripos($sLine, 'CANTONNEMENT') || false !== stripos($sLine, 'DECANTON')) {
                    $sRecordCode = substr($sLine, 0, 2);
                    if ($sRecordCode == 04) {
                        $i++;
                    }
                    $aRestriction[$i] = $i;
                } else {
                    $sRecordCode = substr($sLine, 0, 2);

                    if ($sRecordCode == 04) {
                        $i++;
                        $iLine = 1;

                        if (strpos($sLine, 'BIENVENUE') == true) {
                            $aResult[$i]['unilend_bienvenue'] = true;
                        }
                        $aResult[$i]['codeEnregi']          = substr($sLine, 0, 2);
                        $aResult[$i]['codeBanque']          = substr($sLine, 2, 5);
                        $aResult[$i]['codeOpBNPP']          = substr($sLine, 7, 4);
                        $aResult[$i]['codeGuichet']         = substr($sLine, 11, 5);
                        $aResult[$i]['codeDevises']         = substr($sLine, 16, 3);
                        $aResult[$i]['nbDecimales']         = substr($sLine, 19, 1);
                        $aResult[$i]['zoneReserv1']         = substr($sLine, 20, 1);
                        $aResult[$i]['numCompte']           = substr($sLine, 21, 11);
                        $aResult[$i]['codeOpInterbancaire'] = substr($sLine, 32, 2);
                        $aResult[$i]['dateEcriture']        = substr($sLine, 34, 6);
                        $aResult[$i]['codeMotifRejet']      = substr($sLine, 40, 2);
                        $aResult[$i]['dateValeur']          = substr($sLine, 42, 6);
                        $aResult[$i]['zoneReserv2']         = substr($sLine, 79, 2);
                        $aResult[$i]['numEcriture']         = substr($sLine, 81, 7);
                        $aResult[$i]['codeExoneration']     = substr($sLine, 88, 1);
                        $aResult[$i]['zoneReserv3']         = substr($sLine, 89, 1);
                        $aResult[$i]['refOp']               = substr($sLine, 104, 16);
                        $aResult[$i]['ligne1']              = $sLine;

                        if (! in_array(substr($sLine, 32, 2), $aEmittedLeviesStatus)) {
                            $aResult[$i]['libelleOpe1'] = substr($sLine, 48, 31);
                        }
                        $amount                 = substr($sLine, 90, 14);
                        $sFirstAmountPart       = ltrim(substr($amount, 0, 13), '0');
                        $sLastAmountPart        = substr($amount, -1, 1);
                        $aResult[$i]['montant'] = $sFirstAmountPart . $aPattern[$sLastAmountPart];
                    }

                    if ($sRecordCode == 05) {
                        if (strpos($sLine, 'BIENVENUE') == true) {
                            $aResult[$i]['unilend_bienvenue'] = true;
                        }

                        if (in_array(substr($sLine, 32, 2), $aEmittedLeviesStatus)) {
                            if (in_array(trim(substr($sLine, 45, 3)), array('LCC', 'LC2'))) {
                                $iLine += 1;
                                $aResult[$i]['libelleOpe' . $iLine] = trim(substr($sLine, 45));
                            }
                        } else {
                            $iLine += 1;
                            $aResult[$i]['libelleOpe' . $iLine] = trim(substr($sLine, 45));
                        }
                    }
                }
            }
            fclose($rHandler);
            foreach ($aRestriction as $item) {
                unset($aResult[$item]);
            }
        } else {
            $this->oLogger->error('SFPMEI incoming file "' . $file . '" not processed');
        }
        return $aResult;
    }

    /**
     * @param int $iProjectId
     * @param float $fAmount
     */
    private function updateRepayment($iProjectId, $fAmount)
    {
        /** @var \echeanciers_emprunteur $echeanciers_emprunteur */
        $echeanciers_emprunteur = $this->oEntityManager->getRepository('echeanciers_emprunteur');
        /** @var \echeanciers $echeanciers */
        $echeanciers = $this->oEntityManager->getRepository('echeanciers');
        /** @var \projects_remb $projects_remb */
        $projects_remb = $this->oEntityManager->getRepository('projects_remb');

        $aRepaymentSchedules = $echeanciers_emprunteur->select('status_emprunteur = 0 AND id_project = ' . $iProjectId, 'ordre ASC');

        foreach ($aRepaymentSchedules as $aRepayment) {
            $fMonthlyAmount = $echeanciers->getMontantRembEmprunteur(bcdiv($aRepayment['montant'], 100, 2), bcdiv($aRepayment['commission'], 100, 2), bcdiv($aRepayment['tva'], 100, 2));

            if ($fMonthlyAmount <= $fAmount) {
                $echeanciers->updateStatusEmprunteur($iProjectId, $aRepayment['ordre']);

                $echeanciers_emprunteur->get($iProjectId, 'ordre = ' . $aRepayment['ordre'] . ' AND id_project');
                $echeanciers_emprunteur->status_emprunteur             = 1;
                $echeanciers_emprunteur->date_echeance_emprunteur_reel = date('Y-m-d H:i:s');
                $echeanciers_emprunteur->update();

                $fAmount = $fAmount - $fMonthlyAmount;

                if ($projects_remb->counter('id_project = "' . $iProjectId . '" AND ordre = "' . $aRepayment['ordre'] . '" AND status IN(0, 1)') <= 0) {
                    $date_echeance_preteur = $echeanciers->select('id_project = "' . $iProjectId . '" AND ordre = "' . $aRepayment['ordre'] . '"', '', 0, 1);

                    $projects_remb->id_project                = $iProjectId;
                    $projects_remb->ordre                     = $aRepayment['ordre'];
                    $projects_remb->date_remb_emprunteur_reel = date('Y-m-d H:i:s');
                    $projects_remb->date_remb_preteurs        = $date_echeance_preteur[0]['date_echeance'];
                    $projects_remb->date_remb_preteurs_reel   = '0000-00-00 00:00:00';
                    $projects_remb->status                    = \projects_remb::STATUS_PENDING;
                    $projects_remb->create();
                }
            } else {
                break;
            }
        }
    }

    /**
     * @param array $aRow
     * @param \transactions $transactions
     * @param \bank_unilend $bank_unilend
     */
    private function processWelcomeOffer(array $aRow, \transactions $transactions, \bank_unilend $bank_unilend)
    {
        $this->oLogger->info('Bank transfer welcome offer: ' . json_encode($aRow['unilend_bienvenue']), array('class' => __CLASS__, 'function' => __FUNCTION__));

        $transactions->id_prelevement   = 0;
        $transactions->id_client        = 0;
        $transactions->montant          = $aRow['montant'];
        $transactions->id_langue        = 'fr';
        $transactions->date_transaction = date('Y-m-d H:i:s');
        $transactions->status           = 1;
        $transactions->etat             = 1;
        $transactions->transaction      = 1;
        $transactions->type_transaction = \transactions_types::TYPE_UNILEND_WELCOME_OFFER_BANK_TRANSFER;
        $transactions->ip_client        = '';
        $transactions->create();

        $bank_unilend->id_transaction = $transactions->id_transaction;
        $bank_unilend->id_project     = 0;
        $bank_unilend->montant        = $aRow['montant'];
        $bank_unilend->type           = 4; // Unilend welcome offer
        $bank_unilend->create();
    }

    /**
     * @param string $motif
     * @param \transactions $transactions
     * @param \projects $projects
     * @param \companies $companies
     * @param \clients $clients
     * @param \receptions $receptions
     * @param \bank_unilend $bank_unilend
     */
    private function processDirectDebit($motif, \transactions $transactions, \projects $projects, \companies $companies, \clients $clients, \receptions &$receptions, \bank_unilend $bank_unilend)
    {
        preg_match('#[0-9]+#', $motif, $extract);
        $iProjectId = (int) $extract[0];

        /** @var \echeanciers_emprunteur $oRepaymentSchedule */
        $oRepaymentSchedule = $this->oEntityManager->getRepository('echeanciers_emprunteur');
        $aNextRepayment     = $oRepaymentSchedule->select('id_project = ' . $iProjectId . ' AND status_emprunteur = 0', 'ordre ASC', 0, 1);

        /** @var \prelevements $oBankDirectDebit */
        $oBankDirectDebit = $this->oEntityManager->getRepository('prelevements');
        if (
            count($aNextRepayment) > 0
            && $oBankDirectDebit->get($iProjectId . '" AND num_prelevement = "' . $aNextRepayment[0]['ordre'], 'id_project')
            && false !== strpos($motif, $oBankDirectDebit->motif)
            && false === $transactions->get($receptions->id_reception, 'status = 1 AND etat = 1 AND type_transaction = ' . \transactions_types::TYPE_BORROWER_REPAYMENT . ' AND id_prelevement')
        ) {
            $projects->get($iProjectId, 'id_project');
            $companies->get($projects->id_company, 'id_company');
            $clients->get($companies->id_client_owner, 'id_client');

            $receptions->id_client  = $clients->id_client;
            $receptions->id_project = $projects->id_project;
            $receptions->status_bo  = 2;
            $receptions->remb       = 1;
            $receptions->update();

            $transactions->id_prelevement   = $receptions->id_reception;
            $transactions->id_client        = $clients->id_client;
            $transactions->montant          = $receptions->montant;
            $transactions->id_langue        = 'fr';
            $transactions->date_transaction = date('Y-m-d H:i:s');
            $transactions->status           = 1;
            $transactions->etat             = 1;
            $transactions->transaction      = 1;
            $transactions->type_transaction = \transactions_types::TYPE_BORROWER_REPAYMENT;
            $transactions->ip_client        = '';
            $transactions->create();

            $bank_unilend->id_transaction = $transactions->id_transaction;
            $bank_unilend->id_project     = $projects->id_project;
            $bank_unilend->montant        = $receptions->montant;
            $bank_unilend->type           = 1;
            $bank_unilend->create();

            $this->updateRepayment($projects->id_project, bcdiv($receptions->montant, 100, 2));
        }
    }

    /**
     * @param \receptions $receptions
     * @param \transactions $transactions
     * @param \bank_unilend $bank_unilend
     * @param \projects $projects
     */
    private function processBorrowerAnticipatedRepayment(\receptions &$receptions, \transactions $transactions, \bank_unilend $bank_unilend, \projects $projects)
    {
        $receptions->id_project = $projects->id_project;
        $receptions->type_remb  = \receptions::REPAYMENT_TYPE_EARLY;
        $receptions->status_bo  = 2;
        $receptions->update();

        $transactions->id_virement      = $receptions->id_reception;
        $transactions->id_project       = $projects->id_project;
        $transactions->montant          = $receptions->montant;
        $transactions->id_langue        = 'fr';
        $transactions->date_transaction = date('Y-m-d H:i:s');
        $transactions->status           = 1;
        $transactions->etat             = 1;
        $transactions->transaction      = 1;
        $transactions->type_transaction = \transactions_types::TYPE_BORROWER_ANTICIPATED_REPAYMENT;
        $transactions->ip_client        = '';
        $transactions->create();

        $bank_unilend->id_transaction = $transactions->id_transaction;
        $bank_unilend->id_project     = $projects->id_project;
        $bank_unilend->montant        = $receptions->montant;
        $bank_unilend->type           = 1; // remb emprunteur
        $bank_unilend->status         = 0; // chez unilend
        $bank_unilend->create();
        /** @var \settings $oSettings */
        $oSettings = $this->oEntityManager->getRepository('settings');
        $oSettings->get('Adresse notification nouveau remboursement anticipe', 'type');
        $sEmail = $oSettings->value;

        $sUrl       = $this->getContainer()->getParameter('router.request_context.scheme') . '://' .
                      $this->getContainer()->getParameter('url.host_default');
        $sStaticUrl = $this->getContainer()->get('assets.packages')->getUrl('');
        $varMail = array(
            '$surl'       => $sStaticUrl,
            '$url'        => $sUrl,
            '$id_projet'  => $projects->id_project,
            '$montant'    => bcdiv($transactions->montant, 100, 2),
            '$nom_projet' => $projects->title
        );

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('notification-nouveau-remboursement-anticipe', $varMail, false);
        $message->setTo($sEmail);
        $mailer = $this->getContainer()->get('mailer');
        $mailer->send($message);
    }

    /**
     * @param string $sMotif
     * @param $sOperation
     * @param \receptions $receptions
     * @param \projects $projects
     * @param \companies $companies
     * @param \transactions $transactions
     * @param \bank_unilend $bank_unilend
     */
    private function processRegulation($sMotif, $sOperation, \receptions &$receptions, \projects $projects, \companies $companies, \transactions $transactions, \bank_unilend $bank_unilend)
    {
        preg_match_all('#[0-9]+#', $sOperation, $extract);

        foreach ($extract[0] as $sNumber) {
            if ($projects->get((int) $sNumber, 'id_project')) {
                $companies->get($projects->id_company, 'id_company');

                $receptions->motif      = $sMotif;
                $receptions->id_client  = $companies->id_client_owner;
                $receptions->id_project = $projects->id_project;
                $receptions->status_bo  = 2;
                $receptions->type_remb  = 2;
                $receptions->remb       = 1;
                $receptions->update();

                $transactions->id_virement      = $receptions->id_reception;
                $transactions->montant          = $receptions->montant;
                $transactions->id_langue        = 'fr';
                $transactions->date_transaction = date('Y-m-d H:i:s');
                $transactions->status           = 1;
                $transactions->etat             = 1;
                $transactions->transaction      = 1;
                $transactions->type_transaction = \transactions_types::TYPE_REGULATION_BANK_TRANSFER;
                $transactions->ip_client        = '';
                $transactions->create();

                $bank_unilend->id_transaction = $transactions->id_transaction;
                $bank_unilend->id_project     = $projects->id_project;
                $bank_unilend->montant        = $receptions->montant;
                $bank_unilend->type           = 1;
                $bank_unilend->create();

                $this->updateRepayment($projects->id_project, bcdiv($receptions->montant, 100, 2));
                break;
            }
        }
    }

    /**
     * @param string $motif
     * @param \receptions $receptions
     * @param \clients $clients
     * @param \transactions $transactions
     * @param string $sFacebookLink
     * @param string $sTwitterLink
     */
    private function processLenderBankTransfer($motif, \receptions &$receptions, \clients &$clients, \transactions $transactions, $sFacebookLink, $sTwitterLink)
    {
        /** @var \ficelle $oFicelle */
        $oFicelle = Loader::loadLib('ficelle');
        /** @var \lenders_accounts $lenders */
        $lenders = $this->oEntityManager->getRepository('lenders_accounts');
        /** @var \notifications $notifications */
        $notifications = $this->oEntityManager->getRepository('notifications');
        /** @var \clients_gestion_notifications $clients_gestion_notifications */
        $clients_gestion_notifications = $this->oEntityManager->getRepository('clients_gestion_notifications');
        /** @var \clients_gestion_mails_notif $clients_gestion_mails_notif */
        $clients_gestion_mails_notif = $this->oEntityManager->getRepository('clients_gestion_mails_notif');
        /** @var \wallets_lines $wallets */
        $wallets = $this->oEntityManager->getRepository('wallets_lines');
        /** @var \bank_lines $bank */
        $bank = $this->oEntityManager->getRepository('bank_lines');

        preg_match_all('#[0-9]+#', $motif, $extract);

        foreach ($extract[0] as $sNumber) {
            if ($clients->get((int) $sNumber, 'id_client')) {
                $sLenderPattern = str_replace(' ', '', $clients->getLenderPattern($clients->id_client));

                if (
                    (false !== strpos(str_replace(' ', '', $motif), $sLenderPattern) || true === $clients->isLenderPattern($clients->id_client, str_replace(' ', '', $motif)))
                    && false === $transactions->get($receptions->id_reception, 'status = 1 AND etat = 1 AND id_virement')
                ) {
                    $receptions->get($receptions->id_reception, 'id_reception');
                    $receptions->id_client = $clients->id_client;
                    $receptions->status_bo = 2;
                    $receptions->remb      = 1;
                    $receptions->update();

                    $lenders->get($clients->id_client, 'id_client_owner');
                    $lenders->status = 1;
                    $lenders->update();

                    $transactions->id_virement      = $receptions->id_reception;
                    $transactions->id_client        = $lenders->id_client_owner;
                    $transactions->montant          = $receptions->montant;
                    $transactions->id_langue        = 'fr';
                    $transactions->date_transaction = date('Y-m-d H:i:s');
                    $transactions->status           = 1;
                    $transactions->etat             = 1;
                    $transactions->transaction      = 1;
                    $transactions->type_transaction = \transactions_types::TYPE_LENDER_BANK_TRANSFER_CREDIT;
                    $transactions->ip_client        = '';
                    $transactions->create();

                    $wallets->id_lender                = $lenders->id_lender_account;
                    $wallets->type_financial_operation = 30; // alimenation
                    $wallets->id_transaction           = $transactions->id_transaction;
                    $wallets->type                     = 1; // physique
                    $wallets->amount                   = $receptions->montant;
                    $wallets->status                   = 1;
                    $wallets->create();

                    $bank->id_wallet_line    = $wallets->id_wallet_line;
                    $bank->id_lender_account = $lenders->id_lender_account;
                    $bank->status            = 1;
                    $bank->amount            = $receptions->montant;
                    $bank->create();

                    if ($clients->etape_inscription_preteur < 3) {
                        $clients->etape_inscription_preteur = 3;
                        $clients->update();
                    }

                    if ($clients->status == 1) {
                        $notifications->type      = \notifications::TYPE_BANK_TRANSFER_CREDIT;
                        $notifications->id_lender = $lenders->id_lender_account;
                        $notifications->amount    = $receptions->montant;
                        $notifications->create();

                        $clients_gestion_mails_notif->id_client       = $lenders->id_client_owner;
                        $clients_gestion_mails_notif->id_notif        = \clients_gestion_type_notif::TYPE_BANK_TRANSFER_CREDIT;
                        $clients_gestion_mails_notif->date_notif      = date('Y-m-d H:i:s');
                        $clients_gestion_mails_notif->id_notification = $notifications->id_notification;
                        $clients_gestion_mails_notif->id_transaction  = $transactions->id_transaction;
                        $clients_gestion_mails_notif->create();

                        if ($clients_gestion_notifications->getNotif($lenders->id_client_owner, \clients_gestion_type_notif::TYPE_BANK_TRANSFER_CREDIT, 'immediatement') == true) {
                            $clients_gestion_mails_notif->get($clients_gestion_mails_notif->id_clients_gestion_mails_notif, 'id_clients_gestion_mails_notif');
                            $clients_gestion_mails_notif->immediatement = 1;
                            $clients_gestion_mails_notif->update();

                            $sUrl = $this->getContainer()->getParameter('router.request_context.scheme') . '://' . $this->getContainer()->getParameter('url.host_default');
                            $sStaticUrl = $this->getContainer()->get('assets.packages')->getUrl('');

                            $varMail = array(
                                'surl'            => $sStaticUrl,
                                'url'             => $sUrl,
                                'prenom_p'        => $clients->prenom,
                                'fonds_depot'     => $oFicelle->formatNumber(bcdiv($receptions->montant, 100, 2)),
                                'solde_p'         => $oFicelle->formatNumber($transactions->getSolde($receptions->id_client)),
                                'motif_virement'  => $sLenderPattern,
                                'projets'         => $sUrl . '/projets-a-financer',
                                'gestion_alertes' => $sUrl . '/profile',
                                'lien_fb'         => $sFacebookLink,
                                'lien_tw'         => $sTwitterLink
                            );

                            /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                            $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('preteur-alimentation', $varMail);
                            $message->setTo($clients->email);
                            $mailer = $this->getContainer()->get('mailer');
                            $mailer->send($message);
                        }
                    }
                }
                break;
            }
        }
    }

    /**
     * @param array $aRow
     * @param \projects $projects
     * @param \companies $companies
     * @param \transactions $transactions
     * @param \receptions $receptions
     * @param \bank_unilend $bank_unilend
     */
    private function processBorrowerRepaymentRejection(array $aRow, \projects $projects, \companies $companies, \transactions $transactions, \receptions &$receptions, \bank_unilend $bank_unilend)
    {
        /** @var \echeanciers $oEcheanciers */
        $oEcheanciers = $this->oEntityManager->getRepository('echeanciers');
        /** @var \echeanciers_emprunteur $oEcheanciersEmprunteur */
        $oEcheanciersEmprunteur = $this->oEntityManager->getRepository('echeanciers_emprunteur');
        /** @var \prelevements $oPrelevements */
        $oPrelevements = $this->oEntityManager->getRepository('prelevements');
        /** @var \projects_remb $oProjectsRemb */
        $oProjectsRemb = $this->oEntityManager->getRepository('projects_remb');
        /** @var \transactions $oTransactions */
        $oTransactions = $this->oEntityManager->getRepository('transactions');

        if (
            1 === preg_match('#^RUM[^0-9]*([0-9]+)#', $aRow['libelleOpe3'], $aMatches)
            && $projects->get((int) $aMatches[1])
            && 1 === preg_match('#^RCNUNILEND/([0-9]{8})/([0-9]+)#', $aRow['libelleOpe4'], $aMatches)
            && $oPrelevements->get((int) $aMatches[2])
            && $projects->id_project == $oPrelevements->id_project
            && $companies->get($projects->id_company)
            && $transactions->get($aRow['montant'], 'status = 1 AND etat = 1 AND type_transaction = ' . \transactions_types::TYPE_BORROWER_REPAYMENT . ' AND DATE(date_transaction) >= STR_TO_DATE("' . $aMatches[1] . '", "%Y%m%d") AND id_client = ' . $companies->id_client_owner . ' AND montant')
            && false === $oTransactions->get($transactions->id_prelevement, 'status = 1 AND etat = 1 AND type_transaction = ' . \transactions_types::TYPE_BORROWER_REPAYMENT_REJECTION . ' AND id_prelevement')
        ) {
            $projects->remb_auto = 1;
            $projects->update();

            $oTransactions->id_prelevement   = $transactions->id_prelevement;
            $oTransactions->id_client        = $companies->id_client_owner;
            $oTransactions->montant          = -$receptions->montant;
            $oTransactions->id_langue        = 'fr';
            $oTransactions->date_transaction = date('Y-m-d H:i:s');
            $oTransactions->status           = 1;
            $oTransactions->etat             = 1;
            $oTransactions->transaction      = 1;
            $oTransactions->type_transaction = \transactions_types::TYPE_BORROWER_REPAYMENT_REJECTION;
            $oTransactions->ip_client        = '';
            $oTransactions->create();

            $bank_unilend->id_transaction = $oTransactions->id_transaction;
            $bank_unilend->id_project     = $projects->id_project;
            $bank_unilend->montant        = -$receptions->montant;
            $bank_unilend->type           = 1;
            $bank_unilend->create();

            $receptions->get($transactions->id_prelevement);
            $receptions->status_bo = 3; // rejeté
            $receptions->remb      = 0;
            $receptions->update();

            $fNewAmount = bcdiv($receptions->montant, 100, 2);

            foreach ($oEcheanciersEmprunteur->select('status_emprunteur = 1 AND id_project = ' . $projects->id_project, 'ordre DESC') as $e) {
                $fMonthlyAmount = $oEcheanciers->getMontantRembEmprunteur(bcdiv($e['montant'], 100, 2), bcdiv($e['commission'], 100, 2), bcdiv($e['tva'], 100, 2));

                if ($fMonthlyAmount <= $fNewAmount) {
                    $oEcheanciers->updateStatusEmprunteur($projects->id_project, $e['ordre'], 'annuler');

                    $oEcheanciersEmprunteur->get($projects->id_project, 'ordre = ' . $e['ordre'] . ' AND id_project');
                    $oEcheanciersEmprunteur->status_emprunteur             = 0;
                    $oEcheanciersEmprunteur->date_echeance_emprunteur_reel = '0000-00-00 00:00:00';
                    $oEcheanciersEmprunteur->update();

                    $fNewAmount = $fNewAmount - $fMonthlyAmount;

                    if ($oProjectsRemb->counter('id_project = "' . $projects->id_project . '" AND ordre = "' . $e['ordre'] . '" AND status = 0') > 0) {
                        $oProjectsRemb->get($e['ordre'], 'status = 0 AND id_project = "' . $projects->id_project . '" AND ordre');
                        $oProjectsRemb->status = \projects_remb::STATUS_REJECTED;
                        $oProjectsRemb->update();
                    }
                } else {
                    break;
                }
            }
        }
    }
}
