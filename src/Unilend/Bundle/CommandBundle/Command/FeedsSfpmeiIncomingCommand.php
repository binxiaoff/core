<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Unilend\Bundle\CoreBusinessBundle\Entity\Notifications;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\Receptions;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\core\Loader;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

class FeedsSfpmeiIncomingCommand extends ContainerAwareCommand
{
    const FILE_ROOT_NAME                 = 'UNILEND-00040631007-';
    const FRENCH_BANK_TRANSFER_BNPP_CODE = '0568';

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var EntityManagerSimulator $entityManagerSimulator */
    private $entityManagerSimulator;

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
        $this->entityManagerSimulator = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var \clients $clients */
        $clients = $this->entityManagerSimulator->getRepository('clients');
        /** @var \transactions $transactions */
        $transactions = $this->entityManagerSimulator->getRepository('transactions');
        /** @var \projects $projects */
        $projects = $this->entityManagerSimulator->getRepository('projects');
        /** @var \companies $companies */
        $companies = $this->entityManagerSimulator->getRepository('companies');
        /** @var \bank_unilend $bank_unilend */
        $bank_unilend = $this->entityManagerSimulator->getRepository('bank_unilend');
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

        /** @var \settings $settings */
        $settings = $this->entityManagerSimulator->getRepository('settings');
        $this->entityManagerSimulator->getRepository('transactions_types');

        $this->logger = $this->getContainer()->get('monolog.logger.console');

        $aReceivedTransfersStatus = [05, 18, 45, 13];
        $aEmittedTransfersStatus  = [06, 21];
        $aRejectedTransfersStatus = [12];

        $aEmittedLeviesStatus  = [23, 25, 'A1', 'B1'];
        $aRejectedLeviesStatus = [10, 27, 'A3', 'B3'];

        $sReceptionPath = $this->getContainer()->getParameter('path.sftp') . 'sfpmei/receptions/';
        $sFileContent   = @file_get_contents($sReceptionPath . self::FILE_ROOT_NAME . date('Ymd') . '.txt');

        switch ($sFileContent) {
            case false: {
                $this->logger->info('No SFPMEI incoming file to process in "' . $sReceptionPath . '"', array('class' => __CLASS__, 'function' => __FUNCTION__));
                break;
            }
            default : {
                $aReceivedData = $this->parseReceptionFile($sReceptionPath . self::FILE_ROOT_NAME . date('Ymd') . '.txt', $aEmittedLeviesStatus);
                $aReception    = $entityManager->getRepository('UnilendCoreBusinessBundle:Receptions')->getByDate(new \DateTime());

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
                            $this->processWelcomeOffer($aRow);
                        } else {
                            $reception = new Receptions();
                            $reception->setRemb(0)
                            ->setStatusBo(Receptions::STATUS_PENDING)
                            ->setMotif($motif)
                            ->setMontant($aRow['montant'])
                            ->setType($type)
                            ->setStatusVirement($iBankTransferStatus)
                            ->setStatusPrelevement($iBankDebitStatus)
                            ->setLigne($aRow['ligne1'])
                            ->setTypeRemb(0)
                            ->setIdUser(null);

                            $entityManager->persist($reception);
                            $entityManager->flush();

                            if ($type === 1 && $iBankDebitStatus === 2) {
                                $this->processDirectDebit($motif, $transactions, $reception, $bank_unilend);
                            } elseif ($type === 2 && $iBankTransferStatus === 1) { // Received bank transfer
                                if (
                                    isset($aRow['libelleOpe3'])
                                    && 1 === preg_match('/RA-?([0-9]+)/', $aRow['libelleOpe3'], $aMatches)
                                    && $projects->get((int) $aMatches[1])
                                    && false === $transactions->get($reception->getIdReception(), 'status = ' . \transactions::STATUS_VALID . ' AND id_virement')
                                ) {
                                    $this->processBorrowerAnticipatedRepayment($reception, $transactions, $bank_unilend, $projects);
                                } elseif (
                                    isset($aRow['libelleOpe3'])
                                    && preg_match('/([0-9]+) REGULARISATION/', $aRow['libelleOpe3'], $matches)
                                    && $projects->get($matches[1])
                                ) {
                                    $this->processRegulation($motif, $reception, $projects, $transactions, $bank_unilend);
                                } elseif (self::FRENCH_BANK_TRANSFER_BNPP_CODE === $aRow['codeOpBNPP']) {
                                    $this->processLenderBankTransfer($motif, $reception, $clients, $transactions, $sFacebookLink, $sTwitterLink);
                                }
                            } elseif ($type === 1 && $iBankDebitStatus === 3) {
                                $this->processBorrowerRepaymentRejection($aRow, $projects, $companies, $transactions);
                            }
                        }
                    }

                    $slackManager = $this->getContainer()->get('unilend.service.slack_manager');
                    $slackManager->sendMessage('SFPMEI - ' . count($aReceivedData) . ' opérations réceptionnées');
                }
                break;
            }
        }
    }

    /**
     * @param string $file
     * @param array  $aEmittedLeviesStatus
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
            $this->logger->error('SFPMEI incoming file "' . $file . '" not processed');
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
        $echeanciers_emprunteur = $this->entityManagerSimulator->getRepository('echeanciers_emprunteur');
        /** @var \echeanciers $echeanciers */
        $echeanciers = $this->entityManagerSimulator->getRepository('echeanciers');
        /** @var \projects_remb $projects_remb */
        $projects_remb = $this->entityManagerSimulator->getRepository('projects_remb');

        $aRepaymentSchedules = $echeanciers_emprunteur->select('id_project = ' . $iProjectId . ' AND status_emprunteur = 0', 'ordre ASC');

        foreach ($aRepaymentSchedules as $aRepayment) {
            $fMonthlyAmount = round(bcdiv($aRepayment['montant'], 100, 2) + bcdiv($aRepayment['commission'], 100, 2) + bcdiv($aRepayment['tva'], 100, 2), 2);

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
     */
    private function processWelcomeOffer(array $aRow)
    {
        $this->logger->info('Bank transfer welcome offer: ' . json_encode($aRow['unilend_bienvenue']), array('class' => __CLASS__, 'function' => __FUNCTION__));

        $amount = round(bcdiv($aRow['montant'], 100, 4), 2);
        $this->getContainer()->get('unilend.service.operation_manager')->provisionUnilendPromotionalWallet($amount);
    }

    /**
     * @param string $motif
     * @param \transactions $transactions
     * @param Receptions $reception
     * @param \bank_unilend $bank_unilend
     */
    private function processDirectDebit($motif, \transactions $transactions, Receptions $reception, \bank_unilend $bank_unilend)
    {
        preg_match('#[0-9]+#', $motif, $extract);
        $projectId = (int) $extract[0];

        /** @var \echeanciers_emprunteur $repaymentSchedule */
        $repaymentSchedule = $this->entityManagerSimulator->getRepository('echeanciers_emprunteur');
        $nextRepayment    = $repaymentSchedule->select('id_project = ' . $projectId . ' AND status_emprunteur = 0', 'ordre ASC', 0, 1);

        /** @var \prelevements $bankDirectDebit */
        $bankDirectDebit = $this->entityManagerSimulator->getRepository('prelevements');
        if (
            count($nextRepayment) > 0
            && $bankDirectDebit->get($projectId . '" AND num_prelevement = "' . $nextRepayment[0]['ordre'], 'id_project')
            && false !== strpos($motif, $bankDirectDebit->motif)
            && false === $transactions->get($reception->getIdReception(), 'status = ' . \transactions::STATUS_VALID . ' AND type_transaction = ' . \transactions_types::TYPE_BORROWER_REPAYMENT . ' AND id_prelevement')
        ) {
            $entityManager    = $this->getContainer()->get('doctrine.orm.entity_manager');
            $operationManager = $this->getContainer()->get('unilend.service.operation_manager');
            $project          = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($projectId);
            $client           = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($project->getIdCompany()->getIdClientOwner());

            if ($project instanceof Projects) {
                $reception->setIdProject($project)
                          ->setIdClient($client)
                          ->setStatusBo(Receptions::STATUS_AUTO_ASSIGNED)
                          ->setAssignmentDate(new \DateTime())
                          ->setRemb(1);
                $entityManager->flush();

                $operationManager->provisionBorrowerWallet($reception);

                $this->updateRepayment($project->getIdProject(), bcdiv($reception->getMontant(), 100, 2));
            }
        }
    }

    /**
     * @param Receptions $reception
     * @param \transactions $transactions
     * @param \bank_unilend $bank_unilend
     * @param \projects $projects
     */
    private function processBorrowerAnticipatedRepayment(Receptions $reception, \transactions $transactions, \bank_unilend $bank_unilend, \projects $projects)
    {
        $entityManager    = $this->getContainer()->get('doctrine.orm.entity_manager');
        $operationManager = $this->getContainer()->get('unilend.service.operation_manager');
        $project          = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($projects->id_project);
        $client           = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($project->getIdCompany()->getIdClientOwner());
        $reception->setIdProject($project)
                  ->setIdClient($client)
                  ->setStatusBo(Receptions::STATUS_AUTO_ASSIGNED)
                  ->setTypeRemb(Receptions::REPAYMENT_TYPE_EARLY)
                  ->setAssignmentDate(new \DateTime())
                  ->setRemb(1);
        $entityManager->flush();

        $operationManager->provisionBorrowerWallet($reception);
        /** @var \settings $settings */
        $settings = $this->entityManagerSimulator->getRepository('settings');
        $settings->get('Adresse notification nouveau remboursement anticipe', 'type');
        $email = $settings->value;

        $url       = $this->getContainer()->getParameter('router.request_context.scheme') . '://' .
                      $this->getContainer()->getParameter('url.host_default');
        $staticUrl = $this->getContainer()->get('assets.packages')->getUrl('');
        $varMail = [
            '$surl'       => $staticUrl,
            '$url'        => $url,
            '$id_projet'  => $project->getIdProject(),
            '$montant'    => bcdiv($reception->getMontant(), 100, 2),
            '$nom_projet' => $project->getTitle()
        ];

        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
        $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('notification-nouveau-remboursement-anticipe', $varMail, false);
        $message->setTo($email);
        $mailer = $this->getContainer()->get('mailer');
        $mailer->send($message);
    }

    /**
     * @param string $sMotif
     * @param Receptions $reception
     * @param \projects $projects
     * @param \transactions $transactions
     * @param \bank_unilend $bankUnilend
     */
    private function processRegulation($sMotif, Receptions $reception, \projects $projects, \transactions $transactions, \bank_unilend $bankUnilend)
    {
        $entityManager    = $this->getContainer()->get('doctrine.orm.entity_manager');
        $operationManager = $this->getContainer()->get('unilend.service.operation_manager');
        $project          = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($projects->id_project);
        $client           = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($project->getIdCompany()->getIdClientOwner());
        $reception->setIdProject($project)
                  ->setIdClient($client)
                  ->setStatusBo(Receptions::STATUS_AUTO_ASSIGNED)
                  ->setTypeRemb(Receptions::REPAYMENT_TYPE_REGULARISATION)
                  ->setRemb(1)
                  ->setAssignmentDate(new \DateTime())
                  ->setMotif($sMotif);
        $entityManager->flush();

        $operationManager->provisionBorrowerWallet($reception);

        $this->updateRepayment($project->getIdProject(), bcdiv($reception->getMontant(), 100, 2));
    }

    /**
     * @param string $motif
     * @param Receptions $reception
     * @param \clients $clients
     * @param \transactions $transactions
     * @param string $sFacebookLink
     * @param string $sTwitterLink
     */
    private function processLenderBankTransfer($motif, Receptions $reception, \clients &$clients, \transactions $transactions, $sFacebookLink, $sTwitterLink)
    {
        /** @var \ficelle $oFicelle */
        $oFicelle = Loader::loadLib('ficelle');
        /** @var \notifications $notifications */
        $notifications = $this->entityManagerSimulator->getRepository('notifications');
        /** @var \clients_gestion_notifications $clients_gestion_notifications */
        $clients_gestion_notifications = $this->entityManagerSimulator->getRepository('clients_gestion_notifications');
        /** @var \clients_gestion_mails_notif $clients_gestion_mails_notif */
        $clients_gestion_mails_notif = $this->entityManagerSimulator->getRepository('clients_gestion_mails_notif');

        if (
            preg_match('/([0-9]{6}) ?[A-Z]+/', $motif, $matches)
            && $clients->get((int) $matches[1], 'id_client')
            && $clients->isLenderPattern($clients->id_client, $motif)
            && false === $transactions->get($reception->getIdReception(), 'status = ' . \transactions::STATUS_VALID . ' AND id_virement')
        ) {
            $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
            $wallet        = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($clients->id_client, WalletType::LENDER);

            $reception->setIdClient($wallet->getIdClient())
                      ->setStatusBo(Receptions::STATUS_AUTO_ASSIGNED)
                      ->setRemb(1); // todo: delete the field
            $entityManager->flush();

            $this->getContainer()->get('unilend.service.operation_manager')->provisionLenderWallet($wallet, $reception);

            if ($clients->etape_inscription_preteur < 3) {
                $clients->etape_inscription_preteur = 3;
                $clients->update();
            }

            if ($clients->status == 1) {
                $transactions->get($reception->getIdReception(), 'status = ' . \transactions::STATUS_VALID . ' AND id_virement');

                $notifications->type      = Notifications::TYPE_BANK_TRANSFER_CREDIT;
                $notifications->id_lender = $wallet->getId();
                $notifications->amount    = $reception->getMontant();
                $notifications->create();

                $clients_gestion_mails_notif->id_client       = $clients->id_client;
                $clients_gestion_mails_notif->id_notif        = \clients_gestion_type_notif::TYPE_BANK_TRANSFER_CREDIT;
                $clients_gestion_mails_notif->date_notif      = date('Y-m-d H:i:s');
                $clients_gestion_mails_notif->id_notification = $notifications->id_notification;
                $clients_gestion_mails_notif->id_transaction  = $transactions->id_transaction;
                $clients_gestion_mails_notif->create();

                if ($clients_gestion_notifications->getNotif($clients->id_client, \clients_gestion_type_notif::TYPE_BANK_TRANSFER_CREDIT, 'immediatement')) {
                    $clients_gestion_mails_notif->get($clients_gestion_mails_notif->id_clients_gestion_mails_notif, 'id_clients_gestion_mails_notif');
                    $clients_gestion_mails_notif->immediatement = 1;
                    $clients_gestion_mails_notif->update();

                    $sUrl = $this->getContainer()->getParameter('router.request_context.scheme') . '://' . $this->getContainer()->getParameter('url.host_default');
                    $sStaticUrl = $this->getContainer()->get('assets.packages')->getUrl('');

                    $varMail = array(
                        'surl'            => $sStaticUrl,
                        'url'             => $sUrl,
                        'prenom_p'        => $clients->prenom,
                        'fonds_depot'     => $oFicelle->formatNumber(bcdiv($reception->getMontant(), 100, 2)),
                        'solde_p'         => $oFicelle->formatNumber($wallet->getAvailableBalance()),
                        'motif_virement'  => $wallet->getWireTransferPattern(),
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
    }

    /**
     * @param array $aRow
     * @param \projects $projects
     * @param \companies $companies
     * @param \transactions $transactions
     */
    private function processBorrowerRepaymentRejection(array $aRow, \projects $projects, \companies $companies, \transactions $transactions)
    {
        /** @var \echeanciers $oEcheanciers */
        $oEcheanciers = $this->entityManagerSimulator->getRepository('echeanciers');
        /** @var \echeanciers_emprunteur $oEcheanciersEmprunteur */
        $oEcheanciersEmprunteur = $this->entityManagerSimulator->getRepository('echeanciers_emprunteur');
        /** @var \prelevements $oPrelevements */
        $oPrelevements = $this->entityManagerSimulator->getRepository('prelevements');
        /** @var \projects_remb $oProjectsRemb */
        $oProjectsRemb = $this->entityManagerSimulator->getRepository('projects_remb');
        /** @var \transactions $oTransactions */
        $oTransactions = $this->entityManagerSimulator->getRepository('transactions');

        if (
            1 === preg_match('#^RUM[^0-9]*([0-9]+)#', $aRow['libelleOpe3'], $aMatches)
            && $projects->get((int) $aMatches[1])
            && 1 === preg_match('#^RCNUNILEND/([0-9]{8})/([0-9]+)#', $aRow['libelleOpe4'], $aMatches)
            && $oPrelevements->get((int) $aMatches[2])
            && $projects->id_project == $oPrelevements->id_project
            && $companies->get($projects->id_company)
            && $transactions->get($aRow['montant'], 'status = ' . \transactions::STATUS_VALID . ' AND type_transaction = ' . \transactions_types::TYPE_BORROWER_REPAYMENT . ' AND DATE(date_transaction) >= STR_TO_DATE("' . $aMatches[1] . '", "%Y%m%d") AND id_client = ' . $companies->id_client_owner . ' AND montant')
            && false === $oTransactions->get($transactions->id_prelevement, 'status = ' . \transactions::STATUS_VALID . ' AND type_transaction = ' . \transactions_types::TYPE_BORROWER_REPAYMENT_REJECTION . ' AND id_prelevement')
        ) {
            $projects->remb_auto = 1;
            $projects->update();

            $entityManager    = $this->getContainer()->get('doctrine.orm.entity_manager');
            $operationManager = $this->getContainer()->get('unilend.service.operation_manager');
            $reception        = $entityManager->getRepository('UnilendCoreBusinessBundle:Receptions')->find($transactions->id_prelevement);
            $wallet           = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($reception->getIdClient()->getIdClient(), WalletType::BORROWER);
            if ($wallet) {
                $amount = round(bcdiv($reception->getMontant(), 100, 4), 2);
                $operationManager->cancelProvisionBorrowerWallet($wallet, $amount, $reception);

                $reception->setStatusBo(Receptions::STATUS_REJECTED);
                $reception->setRemb(0);
                $entityManager->flush();

                $fNewAmount = bcdiv($reception->getMontant(), 100, 2);

                foreach ($oEcheanciersEmprunteur->select('id_project = ' . $projects->id_project . ' AND status_emprunteur = 1', 'ordre DESC') as $e) {
                    $fMonthlyAmount = round(bcdiv($e['montant'], 100, 2) + bcdiv($e['commission'], 100, 2) + bcdiv($e['tva'], 100, 2), 2);

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
}
