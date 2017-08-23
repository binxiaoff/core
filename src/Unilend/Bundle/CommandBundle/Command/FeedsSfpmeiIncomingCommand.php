<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\EcheanciersEmprunteur;
use Unilend\Bundle\CoreBusinessBundle\Entity\Notifications;
use Unilend\Bundle\CoreBusinessBundle\Entity\Prelevements;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Receptions;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
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
        $entityManager                = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->logger                 = $this->getContainer()->get('monolog.logger.console');

        $projectRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects');

        $aReceivedTransfersStatus = [05, 18, 45, 13];
        $aEmittedTransfersStatus  = [06, 21];
        $aRejectedTransfersStatus = [12];

        $aEmittedLeviesStatus  = [23, 25, 'A1', 'B1'];
        $aRejectedLeviesStatus = [10, 27, 'A3', 'B3'];

        $receptionPath = $this->getContainer()->getParameter('path.sftp') . 'sfpmei/receptions/';

        if (false === @file_get_contents($receptionPath . self::FILE_ROOT_NAME . date('Ymd') . '.txt')) {
            $this->logger->info('No SFPMEI incoming file to process in "' . $receptionPath . '"', ['class' => __CLASS__, 'function' => __FUNCTION__]);
            exit;
        }

        $aReceivedData = $this->parseReceptionFile($receptionPath . self::FILE_ROOT_NAME . date('Ymd') . '.txt', $aEmittedLeviesStatus);
        $aReception    = $entityManager->getRepository('UnilendCoreBusinessBundle:Receptions')->getByDate(new \DateTime());

        if (false === empty($aReceivedData) && (empty($aReception) || $input->getOption('force-replay'))) {
            foreach ($aReceivedData as $aRow) {
                $motif               = '';
                $code                = $aRow['codeOpInterbancaire'];
                $type                = Receptions::TYPE_UNKNOWN;
                $iBankTransferStatus = 0;
                $iBankDebitStatus    = 0;

                if (in_array($code, $aReceivedTransfersStatus)) {
                    $type                = Receptions::TYPE_WIRE_TRANSFER;
                    $iBankTransferStatus = Receptions::WIRE_TRANSFER_STATUS_RECEIVED;
                } elseif (in_array($code, $aEmittedTransfersStatus)) {
                    $type                = Receptions::TYPE_WIRE_TRANSFER;
                    $iBankTransferStatus = Receptions::WIRE_TRANSFER_STATUS_SENT;
                } elseif (in_array($code, $aRejectedTransfersStatus)) {
                    $type                = Receptions::TYPE_WIRE_TRANSFER;
                    $iBankTransferStatus = Receptions::WIRE_TRANSFER_STATUS_REJECTED;
                } elseif (in_array($code, $aEmittedLeviesStatus)) {
                    $type             = Receptions::TYPE_DIRECT_DEBIT;
                    $iBankDebitStatus = Receptions::DIRECT_DEBIT_STATUS_SENT;
                } elseif (in_array($code, $aRejectedLeviesStatus)) {
                    $type             = Receptions::TYPE_DIRECT_DEBIT;
                    $iBankDebitStatus = Receptions::DIRECT_DEBIT_STATUS_REJECTED;
                }

                for ($index = 1; $index <= 5; $index++) {
                    if (false === empty($aRow['libelleOpe' . $index])) {
                        $motif .= trim($aRow['libelleOpe' . $index]) . '<br>';
                    }
                }

                $status = Receptions::STATUS_PENDING;

                if (false === empty($aRow['welcomeOffer'])) {
                    $status = Receptions::STATUS_ASSIGNED_AUTO;
                    $this->processWelcomeOffer($aRow);
                }

                if (false !== stripos($aRow['ligne1'], 'CANTONNEMENT') || false !== stripos($aRow['ligne1'], 'DECANTON')) {
                    $status = Receptions::STATUS_IGNORED_AUTO;
                }

                $reception = new Receptions();
                $reception
                    ->setRemb(0)
                    ->setStatusBo($status)
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

                if ($type === Receptions::TYPE_DIRECT_DEBIT && $iBankDebitStatus === Receptions::DIRECT_DEBIT_STATUS_SENT) {
                    $this->processDirectDebit($motif, $reception);
                } elseif ($type === Receptions::TYPE_WIRE_TRANSFER && $iBankTransferStatus === Receptions::WIRE_TRANSFER_STATUS_RECEIVED) {
                    if (
                        isset($aRow['libelleOpe3'])
                        && 1 === preg_match('/RA-?([0-9]+)/', $aRow['libelleOpe3'], $matches)
                        && $project = $projectRepository->find((int) $matches[1])
                    ) {
                        $this->processBorrowerAnticipatedRepayment($reception, $project);
                    } elseif (
                        isset($aRow['libelleOpe3'])
                        && preg_match('/([0-9]+) REGULARISATION/', $aRow['libelleOpe3'], $matches)
                        && $project = $projectRepository->find((int) $matches[1])
                    ) {
                        $this->processRegulation($motif, $reception, $project);
                    } elseif (self::FRENCH_BANK_TRANSFER_BNPP_CODE === $aRow['codeOpBNPP']) {
                        $this->processLenderBankTransfer($motif, $reception);
                    }
                } elseif ($type === Receptions::TYPE_DIRECT_DEBIT && $iBankDebitStatus === Receptions::DIRECT_DEBIT_STATUS_REJECTED) {
                    $this->processBorrowerRepaymentRejection($aRow, $reception);
                }
            }

            $slackManager = $this->getContainer()->get('unilend.service.slack_manager');
            $slackManager->sendMessage('SFPMEI - ' . count($aReceivedData) . ' opérations réceptionnées');
        }
    }

    /**
     * @param string $file
     * @param array  $aEmittedLeviesStatus
     *
     * @return array
     */
    private function parseReceptionFile($file, array $aEmittedLeviesStatus)
    {
        $aPattern = [
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
        ];

        $aResult  = [];
        $rHandler = fopen($file, 'r');

        if ($rHandler) {
            $i = 0;
            while (($sLine = fgets($rHandler)) !== false) {
                $sLine       = trim($sLine, "\n\r");
                $sRecordCode = substr($sLine, 0, 2);

                if ($sRecordCode == 04) {
                    $i++;
                    $iLine = 1;

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
                    $aResult[$i]['welcomeOffer']        = false !== strpos($sLine, 'BIENVENUE');

                    if (false === in_array(substr($sLine, 32, 2), $aEmittedLeviesStatus)) {
                        $aResult[$i]['libelleOpe1'] = substr($sLine, 48, 31);
                    }
                    $amount                 = substr($sLine, 90, 14);
                    $sFirstAmountPart       = ltrim(substr($amount, 0, 13), '0');
                    $sLastAmountPart        = substr($amount, -1, 1);
                    $aResult[$i]['montant'] = $sFirstAmountPart . $aPattern[$sLastAmountPart];
                }

                if ($sRecordCode == 05) {
                    if (false !== strpos($sLine, 'BIENVENUE')) {
                        $aResult[$i]['welcomeOffer'] = true;
                    }

                    if (in_array(substr($sLine, 32, 2), $aEmittedLeviesStatus)) {
                        if (in_array(trim(substr($sLine, 45, 3)), ['LCC', 'LC2'])) {
                            $iLine                              += 1;
                            $aResult[$i]['libelleOpe' . $iLine] = trim(substr($sLine, 45));
                        }
                    } else {
                        $iLine                              += 1;
                        $aResult[$i]['libelleOpe' . $iLine] = trim(substr($sLine, 45));
                    }
                }
            }

            fclose($rHandler);
        } else {
            $this->logger->error('SFPMEI incoming file "' . $file . '" not processed');
        }

        return $aResult;
    }

    /**
     * @param array $aRow
     */
    private function processWelcomeOffer(array $aRow)
    {
        $amount = round(bcdiv($aRow['montant'], 100, 4), 2);
        $this->getContainer()->get('unilend.service.operation_manager')->provisionUnilendPromotionalWallet($amount);
    }

    /**
     * @param string     $motif
     * @param Receptions $reception
     */
    private function processDirectDebit($motif, Receptions $reception)
    {
        if (1 === preg_match('#[0-9]+#', $motif, $extract)) {
            $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
            $projectId     = (int) $extract[0];
            /** @var EcheanciersEmprunteur $nextPayment */
            $nextPayment = $entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur')
                ->findOneBy(['idProject' => $projectId, 'statusEmprunteur' => EcheanciersEmprunteur::STATUS_PENDING], ['ordre' => 'ASC']);

            /** @var Prelevements $bankDirectDebit */
            $bankDirectDebit = $entityManager->getRepository('UnilendCoreBusinessBundle:Prelevements')
                ->findOneBy(['idProject' => $projectId, 'numPrelevement' => $nextPayment->getOrdre()]);
            if ($nextPayment && $bankDirectDebit && false !== strpos($motif, $bankDirectDebit->getMotif())) {
                $operationManager = $this->getContainer()->get('unilend.service.operation_manager');
                $repaymentManager = $this->getContainer()->get('unilend.service.project_repayment_manager');

                $project = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($projectId);
                $client  = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($project->getIdCompany()->getIdClientOwner());

                if ($project instanceof Projects) {
                    $reception->setIdProject($project)
                        ->setIdClient($client)
                        ->setStatusBo(Receptions::STATUS_ASSIGNED_AUTO)
                        ->setAssignmentDate(new \DateTime())
                        ->setRemb(1);
                    $entityManager->flush();

                    $operationManager->provisionBorrowerWallet($reception);

                    if ($project->getStatus() < ProjectsStatus::PROBLEME) {
                        $user = $entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find(Users::USER_ID_CRON);
                        $repaymentManager->pay($reception, $user);
                    }
                }
            }
        }
    }

    /**
     * @param Receptions $reception
     * @param Projects   $project
     */
    private function processBorrowerAnticipatedRepayment(Receptions $reception, Projects $project)
    {
        $entityManager    = $this->getContainer()->get('doctrine.orm.entity_manager');
        $operationManager = $this->getContainer()->get('unilend.service.operation_manager');
        $repaymentManager = $this->getContainer()->get('unilend.service.project_repayment_manager');

        $client = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($project->getIdCompany()->getIdClientOwner());

        $reception->setIdProject($project)
            ->setIdClient($client)
            ->setStatusBo(Receptions::STATUS_ASSIGNED_AUTO)
            ->setTypeRemb(Receptions::REPAYMENT_TYPE_EARLY)
            ->setAssignmentDate(new \DateTime())
            ->setRemb(1);
        $entityManager->flush();

        $operationManager->provisionBorrowerWallet($reception);
        $user = $entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find(Users::USER_ID_CRON);
        $repaymentManager->planEarlyRepayment($project, $reception, $user);
    }

    /**
     * @param string     $motif
     * @param Receptions $reception
     * @param Projects   $project
     */
    private function processRegulation($motif, Receptions $reception, Projects $project)
    {
        $entityManager    = $this->getContainer()->get('doctrine.orm.entity_manager');
        $operationManager = $this->getContainer()->get('unilend.service.operation_manager');
        $repaymentManager = $this->getContainer()->get('unilend.service.project_repayment_manager');

        $client = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($project->getIdCompany()->getIdClientOwner());

        $reception->setIdProject($project)
            ->setIdClient($client)
            ->setStatusBo(Receptions::STATUS_ASSIGNED_AUTO)
            ->setTypeRemb(Receptions::REPAYMENT_TYPE_REGULARISATION)
            ->setRemb(1)
            ->setAssignmentDate(new \DateTime())
            ->setMotif($motif);
        $entityManager->flush();

        $operationManager->provisionBorrowerWallet($reception);

        $user = $entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find(Users::USER_ID_CRON);
        $repaymentManager->pay($reception, $user);
    }

    /**
     * @param            $pattern
     * @param Receptions $reception
     */
    private function processLenderBankTransfer($pattern, Receptions $reception)
    {
        /** @var \notifications $notifications */
        $notifications = $this->entityManagerSimulator->getRepository('notifications');
        /** @var \clients_gestion_notifications $clients_gestion_notifications */
        $clients_gestion_notifications = $this->entityManagerSimulator->getRepository('clients_gestion_notifications');
        /** @var \clients_gestion_mails_notif $clients_gestion_mails_notif */
        $clients_gestion_mails_notif = $this->entityManagerSimulator->getRepository('clients_gestion_mails_notif');

        $entityManager   = $this->getContainer()->get('doctrine.orm.entity_manager');
        $numberFormatter = $this->getContainer()->get('number_formatter');

        $operationRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation');

        if (1 === preg_match('/([0-9]{6}) ?[A-Z]+/', $pattern, $matches)) {
            $client = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find((int) $matches[1]);
            if ($client instanceof Clients) {
                /** @var Wallet $wallet */
                $wallet = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);
                if (null !== $wallet) {
                    $pattern       = str_replace(' ', '', $pattern);
                    $lenderPattern = str_replace(' ', '', $wallet->getWireTransferPattern());

                    if (false !== strpos($pattern, $lenderPattern)) {
                        $reception->setIdClient($wallet->getIdClient())
                            ->setStatusBo(Receptions::STATUS_ASSIGNED_AUTO)
                            ->setRemb(1); // todo: delete the field
                        $entityManager->flush();

                        $this->getContainer()->get('unilend.service.operation_manager')->provisionLenderWallet($wallet, $reception);

                        if ($client->getEtapeInscriptionPreteur() < Clients::SUBSCRIPTION_STEP_MONEY_DEPOSIT) {
                            $client->setEtapeInscriptionPreteur(Clients::SUBSCRIPTION_STEP_MONEY_DEPOSIT);
                            $entityManager->flush($client);
                        }

                        if ($client->getStatus() == Clients::STATUS_ONLINE) {
                            $notifications->type      = Notifications::TYPE_BANK_TRANSFER_CREDIT;
                            $notifications->id_lender = $wallet->getId();
                            $notifications->amount    = $reception->getMontant();
                            $notifications->create();

                            $provisionOperation   = $operationRepository->findOneBy(['idWireTransferIn' => $reception]);
                            $walletBalanceHistory = $entityManager->getRepository('UnilendCoreBusinessBundle:WalletBalanceHistory')->findOneBy([
                                'idOperation' => $provisionOperation,
                                'idWallet'    => $wallet
                            ]);

                            $clients_gestion_mails_notif->id_client                 = $client->getIdClient();
                            $clients_gestion_mails_notif->id_notif                  = \clients_gestion_type_notif::TYPE_BANK_TRANSFER_CREDIT;
                            $clients_gestion_mails_notif->date_notif                = date('Y-m-d H:i:s');
                            $clients_gestion_mails_notif->id_notification           = $notifications->id_notification;
                            $clients_gestion_mails_notif->id_wallet_balance_history = $walletBalanceHistory->getId();
                            $clients_gestion_mails_notif->create();

                            if ($clients_gestion_notifications->getNotif($client->getIdClient(), \clients_gestion_type_notif::TYPE_BANK_TRANSFER_CREDIT, 'immediatement')) {
                                $clients_gestion_mails_notif->get($clients_gestion_mails_notif->id_clients_gestion_mails_notif, 'id_clients_gestion_mails_notif');
                                $clients_gestion_mails_notif->immediatement = 1;
                                $clients_gestion_mails_notif->update();

                                $sUrl         = $this->getContainer()->getParameter('router.request_context.scheme') . '://' . $this->getContainer()->getParameter('url.host_default');
                                $sStaticUrl   = $this->getContainer()->get('assets.packages')->getUrl('');
                                $facebookLink = $entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Facebook'])->getValue();
                                $twitterLink  = $entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Twitter'])->getValue();

                                $varMail = [
                                    'surl'            => $sStaticUrl,
                                    'url'             => $sUrl,
                                    'prenom_p'        => $client->getPrenom(),
                                    'fonds_depot'     => $numberFormatter->format(round(bcdiv($reception->getMontant(), 100, 4), 2)),
                                    'solde_p'         => $numberFormatter->format((float) $wallet->getAvailableBalance()),
                                    'motif_virement'  => $wallet->getWireTransferPattern(),
                                    'projets'         => $sUrl . '/projets-a-financer',
                                    'gestion_alertes' => $sUrl . '/profile',
                                    'lien_fb'         => $facebookLink,
                                    'lien_tw'         => $twitterLink
                                ];

                                $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('preteur-alimentation', $varMail);
                                try {
                                    $message->setTo($client->getEmail());
                                    $mailer = $this->getContainer()->get('mailer');
                                    $mailer->send($message);
                                } catch (\Exception $exception) {
                                    $this->getContainer()->get('monolog.logger.console')->warning(
                                        'Could not send email: preteur-alimentation - Exception: ' . $exception->getMessage(),
                                        ['id_mail_template' => $message->getTemplateId(), 'id_client' => $client->getIdClient(), 'class' => __CLASS__, 'function' => __FUNCTION__]
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param array      $aRow
     * @param Receptions $reception
     */
    private function processBorrowerRepaymentRejection(array $aRow, Receptions $reception)
    {
        $entityManager    = $this->getContainer()->get('doctrine.orm.entity_manager');
        $operationManager = $this->getContainer()->get('unilend.service.operation_manager');
        $repaymentManager = $this->getContainer()->get('unilend.service.project_repayment_manager');

        if (1 === preg_match('#^RUM[^0-9]*([0-9]+)#', $aRow['libelleOpe3'], $matches)) {
            /** @var Projects $project */
            $project = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find((int) $matches[1]);

            if (1 === preg_match('#^RCNUNILEND/([0-9]{8})/([0-9]+)#', $aRow['libelleOpe4'], $matches)) {
                $from                        = \DateTime::createFromFormat('Ymd', $matches[1]);
                $originalRejectedDirectDebit = $entityManager->getRepository('UnilendCoreBusinessBundle:Receptions')->findOriginalDirectDebitByRejectedOne($reception, $from);

                if ($project && $originalRejectedDirectDebit) {
                    $project->setRembAuto(Projects::AUTO_REPAYMENT_OFF);
                    $entityManager->flush();

                    /** @var Wallet $wallet */
                    $wallet = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($project->getIdCompany()->getIdClientOwner(), WalletType::BORROWER);

                    if ($wallet) {
                        $reception
                            ->setStatusBo(Receptions::STATUS_ASSIGNED_AUTO)
                            ->setIdProject($project)
                            ->setIdClient($wallet->getIdClient())
                            ->setRemb(0)
                            ->setIdReceptionRejected($originalRejectedDirectDebit);
                        $entityManager->flush();

                        $amount = round(bcdiv($reception->getMontant(), 100, 4), 2);
                        $operationManager->cancelProvisionBorrowerWallet($wallet, $amount, $reception);
                        $user = $entityManager->getRepository('UnilendCoreBusinessBundle:Users')->find(Users::USER_ID_CRON);
                        $repaymentManager->rejectPayment($reception, $user);
                    }
                }
            }
        }
    }
}
