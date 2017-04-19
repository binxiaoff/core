<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Entity\Users;
use Unilend\Bundle\CoreBusinessBundle\Service\MailerManager;
use Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Service\TaxManager;
use Unilend\core\Loader;

class AutomaticLenderRepaymentCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('lender:repayment')
            ->setDescription('generates repayments for projects with automatic repayment process and sends invoice to the borrower');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var \projects $projects */
        $projects = $entityManager->getRepository('projects');
        /** @var \echeanciers_emprunteur $echeanciers_emprunteur */
        $echeanciers_emprunteur = $entityManager->getRepository('echeanciers_emprunteur');
        /** @var \echeanciers $echeanciers */
        $echeanciers = $entityManager->getRepository('echeanciers');
        /** @var \companies $companies */
        $companies = $entityManager->getRepository('companies');
        /** @var \transactions $transactions */
        $transactions = $entityManager->getRepository('transactions');
        /** @var \lenders_accounts $lenders */
        $lenders = $entityManager->getRepository('lenders_accounts');
        /** @var \projects_status_history $projects_status_history */
        $projects_status_history = $entityManager->getRepository('projects_status_history');
        /** @var \wallets_lines $wallets_lines */
        $wallets_lines = $entityManager->getRepository('wallets_lines');
        /** @var \bank_unilend $bank_unilend */
        $bank_unilend = $entityManager->getRepository('bank_unilend');
        /** @var \platform_account_unilend $oAccountUnilend */
        $oAccountUnilend = $entityManager->getRepository('platform_account_unilend');
        /** @var \projects_remb_log $repaymentLog */
        $repaymentLog = $entityManager->getRepository('projects_remb_log');
        /** @var \projects_remb $projectRepayment */
        $projectRepayment = $entityManager->getRepository('projects_remb');
        /** @var \dates $dates */
        $dates = Loader::loadLib('dates');
        /** @var LoggerInterface $logger */
        $logger                = $this->getContainer()->get('monolog.logger.console');
        $stopWatch             = $this->getContainer()->get('debug.stopwatch');
        $operationManager      = $this->getContainer()->get('unilend.service.operation_manager');

        $repaymentScheduleRepo = $this->getContainer()->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Echeanciers');
        $paymentScheduleRepo   = $this->getContainer()->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur');

        $url       = $this->getContainer()->getParameter('router.request_context.scheme') . '://' . $this->getContainer()->getParameter('url.host_default');
        $staticUrl = $this->getContainer()->get('assets.packages')->getUrl('');

        foreach ($projectRepayment->getProjectsToRepay(new \DateTime(), 1) as $r) {
            $stopWatch->start('autoRepayment');
            $repaymentLog->id_project       = $r['id_project'];
            $repaymentLog->ordre            = $r['ordre'];
            $repaymentLog->debut            = date('Y-m-d H:i:s');
            $repaymentLog->fin              = '0000-00-00 00:00:00';
            $repaymentLog->montant_remb_net = 0;
            $repaymentLog->etat             = 0;
            $repaymentLog->nb_pret_remb     = 0;
            $repaymentLog->create();

            $dernierStatut     = $projects_status_history->select('id_project = ' . $r['id_project'], 'added DESC, id_project_status_history DESC', 0, 1);
            $dateDernierStatut = $dernierStatut[0]['added'];
            $timeAdd           = strtotime($dateDernierStatut);
            $day               = date('d', $timeAdd);
            $month             = $dates->tableauMois['fr'][date('n', $timeAdd)];
            $year              = date('Y', $timeAdd);
            $lEcheances        = $echeanciers->select('id_project = ' . $r['id_project'] . ' AND status_emprunteur = 1 AND ordre = ' . $r['ordre'] . ' AND status = 0');
            $nb_pret_remb      = 0;
            $iTotalTaxAmount   = 0;
            $montant           = 0;

            if ($lEcheances != false) {
                /** @var TaxManager $taxManager */
                $taxManager = $this->getContainer()->get('unilend.service.tax_manager');
                /** @var \lender_repayment $lenderRepayment */
                $lenderRepayment = $entityManager->getRepository('lender_repayment');

                foreach ($lEcheances as $e) {
                    $repaymentDate = date('Y-m-d H:i:s');
                    try {
                        if (false === $transactions->exist($e['id_echeancier'], 'id_echeancier')) {
                            $montant += $e['montant'];
                            $nb_pret_remb++;

                            $lenders->get($e['id_lender'], 'id_lender_account');
                            $projects->get($e['id_project'], 'id_project');

                            $lenderRepayment->id_lender  = $e['id_lender'];
                            $lenderRepayment->id_company = $projects->id_company;
                            $lenderRepayment->amount     = $e['montant'];
                            $lenderRepayment->create();

                            $repaymentSchedule = $repaymentScheduleRepo->find($e['id_echeancier']);
                            $operationManager->repayment($repaymentSchedule);

                            $echeanciers->get($e['id_echeancier'], 'id_echeancier');
                            $echeanciers->capital_rembourse   = $echeanciers->capital;
                            $echeanciers->interets_rembourses = $echeanciers->interets;
                            $echeanciers->status              = \echeanciers::STATUS_REPAID;
                            $echeanciers->date_echeance_reel  = $repaymentDate;
                            $echeanciers->update();

                            $transactions->id_client        = $lenders->id_client_owner;
                            $transactions->montant          = $e['capital'];
                            $transactions->id_echeancier    = $e['id_echeancier'];
                            $transactions->id_langue        = 'fr';
                            $transactions->date_transaction = $repaymentDate;
                            $transactions->status           = \transactions::STATUS_VALID;
                            $transactions->type_transaction = \transactions_types::TYPE_LENDER_REPAYMENT_CAPITAL;
                            $transactions->create();

                            $iTaxOnCapital = $taxManager->taxTransaction($transactions);

                            $wallets_lines->id_lender                = $e['id_lender'];
                            $wallets_lines->type_financial_operation = \wallets_lines::TYPE_REPAYMENT;
                            $wallets_lines->id_transaction           = $transactions->id_transaction;
                            $wallets_lines->status                   = 1;
                            $wallets_lines->type                     = \wallets_lines::VIRTUAL;
                            $wallets_lines->amount                   = $transactions->montant;
                            $wallets_lines->create();
                            $wallets_lines->unsetData();

                            $transactions->unsetData();
                            $transactions->id_client        = $lenders->id_client_owner;
                            $transactions->montant          = $e['interets'];
                            $transactions->id_echeancier    = $e['id_echeancier'];
                            $transactions->id_langue        = 'fr';
                            $transactions->date_transaction = $repaymentDate;
                            $transactions->status           = \transactions::STATUS_VALID;
                            $transactions->type_transaction = \transactions_types::TYPE_LENDER_REPAYMENT_INTERESTS;
                            $transactions->create();

                            $iTaxOnInterests = $taxManager->taxTransaction($transactions);
                            $iTotalTaxAmount = bcadd($iTotalTaxAmount, bcadd($iTaxOnCapital, $iTaxOnInterests));

                            $wallets_lines->id_lender                = $e['id_lender'];
                            $wallets_lines->type_financial_operation = \wallets_lines::TYPE_REPAYMENT;
                            $wallets_lines->id_transaction           = $transactions->id_transaction;
                            $wallets_lines->status                   = 1;
                            $wallets_lines->type                     = \wallets_lines::VIRTUAL;
                            $wallets_lines->amount                   = $transactions->montant;
                            $wallets_lines->create();
                        } else {
                            $logger->error(
                                'The transaction has already been created for the repayment (id_echeancier: ' . $e['id_echeancier'] . '). The repayment may have been repaid manually.',
                                array('class' => __CLASS__, 'function' => __FUNCTION__)
                            );
                        }
                    } catch (\Exception $exception) {
                        $logger->error('id_project=' . $e['id_project'] . ', id_echeancier=' . $e['id_echeancier'] . ' - An error occurred when calculating the refund details at line: ' . $exception->getLine() . ' - Exception message: ' . $exception->getMessage() . ' - Exception code: ' . $exception->getCode(),
                            array('class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $e['id_project']));
                    }
                }
            } else {
                $projectRepayment->get($r['id_project_remb'], 'id_project_remb');
                $projectRepayment->status = \projects_remb::STATUS_ERROR;
                $projectRepayment->update();

                $repaymentLog->fin = date('Y-m-d H:i:s');
                $repaymentLog->update();

                $logger->warning(
                    'Cannot find pending lenders\'s repayment schedule to repay for project ' . $r['id_project'] . ' (order: ' . $r['ordre'] . '). The repayment may have been repaid manually.',
                    array('class' => __CLASS__, 'function' => __FUNCTION__)
                );

                continue;
            }

            if (0 != $montant) {
                /** @var \clients $emprunteur */
                $emprunteur = $entityManager->getRepository('clients');

                $rembNetTotal = $montant - $iTotalTaxAmount;

                $projects->get($r['id_project'], 'id_project');
                $companies->get($projects->id_company, 'id_company');
                $emprunteur->get($companies->id_client_owner, 'id_client');
                $echeanciers_emprunteur->get($r['id_project'], ' ordre = ' . $r['ordre'] . ' AND id_project');

                $transactions->unsetData();
                $transactions->montant_unilend          = - $rembNetTotal;
                $transactions->montant_etat             = $iTotalTaxAmount;
                $transactions->id_echeancier_emprunteur = $echeanciers_emprunteur->id_echeancier_emprunteur;
                $transactions->id_langue                = 'fr';
                $transactions->date_transaction         = date('Y-m-d H:i:s');
                $transactions->status                   = \transactions::STATUS_VALID;
                $transactions->type_transaction         = \transactions_types::TYPE_UNILEND_REPAYMENT;
                $transactions->create();

                $bank_unilend->id_transaction         = $transactions->id_transaction;
                $bank_unilend->id_project             = $r['id_project'];
                $bank_unilend->montant                = - $rembNetTotal;
                $bank_unilend->etat                   = $iTotalTaxAmount;
                $bank_unilend->type                   = \bank_unilend::TYPE_REPAYMENT_LENDER;
                $bank_unilend->id_echeance_emprunteur = $echeanciers_emprunteur->id_echeancier_emprunteur;
                $bank_unilend->status                 = 1;
                $bank_unilend->create();

                $oAccountUnilend->addDueDateCommssion($echeanciers_emprunteur->id_echeancier_emprunteur);

                $paymentSchedule = $paymentScheduleRepo->find($echeanciers_emprunteur->id_echeancier_emprunteur);
                $operationManager->repaymentCommission($paymentSchedule);

                /** @var \settings $settings */
                $settings = $entityManager->getRepository('settings');
                $settings->get('Facebook', 'type');
                $sFB = $settings->value;
                $settings->get('Twitter', 'type');
                $sTwitter = $settings->value;

                /** @var \ficelle $ficelle */
                $ficelle = Loader::loadLib('ficelle');

                $varMail = array(
                    'surl'            => $staticUrl,
                    'url'             => $url,
                    'prenom'          => $emprunteur->prenom,
                    'pret'            => $ficelle->formatNumber($projects->amount),
                    'entreprise'      => stripslashes(trim($companies->name)),
                    'projet-title'    => $projects->title,
                    'compte-p'        => $url,
                    'projet-p'        => $url . '/projects/detail/' . $projects->slug,
                    'link_facture'    => $url . '/pdf/facture_ER/' . $emprunteur->hash . '/' . $r['id_project'] . '/' . $r['ordre'],
                    'datedelafacture' => $day . ' ' . $month . ' ' . $year,
                    'mois'            => strtolower($dates->tableauMois['fr'][date('n')]),
                    'annee'           => date('Y'),
                    'lien_fb'         => $sFB,
                    'lien_tw'         => $sTwitter,
                    'montantRemb'     => $ficelle->formatNumber(bcdiv(bcadd(bcadd($paymentSchedule->getMontant(), $paymentSchedule->getCommission()), $paymentSchedule->getTva()), 100, 2))
                );

                $logger->debug('Automatic repayment, send email : facture-emprunteur-remboursement. Data to use: ' . json_encode($varMail), ['class' => __CLASS__, 'function' => __FUNCTION__, 'id_project' => $r['id_project'] ]);

                /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('facture-emprunteur-remboursement', $varMail);
                $message->setTo($emprunteur->email);
                $mailer = $this->getContainer()->get('mailer');
                $mailer->send($message);

                /** @var \compteur_factures $oInvoiceCounter */
                $oInvoiceCounter = $entityManager->getRepository('compteur_factures');
                /** @var \echeanciers $oLenderRepaymentSchedule */
                $oLenderRepaymentSchedule = $entityManager->getRepository('echeanciers');
                /** @var \echeanciers_emprunteur $oBorrowerRepaymentSchedule */
                $oBorrowerRepaymentSchedule = $entityManager->getRepository('echeanciers_emprunteur');
                /** @var \factures $oInvoice */
                $oInvoice = $entityManager->getRepository('factures');

                $aLenderRepayment = $oLenderRepaymentSchedule->select('id_project = ' . $projects->id_project . ' AND ordre = ' . $r['ordre'], '', 0, 1);

                if ($oBorrowerRepaymentSchedule->get($projects->id_project, 'ordre = ' . $r['ordre'] . '  AND id_project')) {
                    $oInvoice->num_facture     = 'FR-E' . date('Ymd', strtotime($aLenderRepayment[0]['date_echeance_reel'])) . str_pad($oInvoiceCounter->compteurJournalier($projects->id_project,
                            $aLenderRepayment[0]['date_echeance_reel']), 5, '0', STR_PAD_LEFT);
                    $oInvoice->date            = $aLenderRepayment[0]['date_echeance_reel'];
                    $oInvoice->id_company      = $companies->id_company;
                    $oInvoice->id_project      = $projects->id_project;
                    $oInvoice->ordre           = $r['ordre'];
                    $oInvoice->type_commission = \factures::TYPE_COMMISSION_REMBOURSEMENT;
                    $oInvoice->commission      = $projects->commission_rate_repayment;
                    $oInvoice->montant_ht      = $oBorrowerRepaymentSchedule->commission;
                    $oInvoice->tva             = $oBorrowerRepaymentSchedule->tva;
                    $oInvoice->montant_ttc     = $oBorrowerRepaymentSchedule->commission + $oBorrowerRepaymentSchedule->tva;
                    $oInvoice->create();
                }

                $lesRembEmprun = $bank_unilend->select('type = 1 AND status = 0 AND id_project = ' . $r['id_project']);

                foreach ($lesRembEmprun as $leR) {
                    $bank_unilend->get($leR['id_unilend'], 'id_unilend');
                    $bank_unilend->status = 1;
                    $bank_unilend->update();
                }

                $projectRepayment->get($r['id_project_remb'], 'id_project_remb');
                $projectRepayment->date_remb_preteurs_reel = date('Y-m-d H:i:s');
                $projectRepayment->status                  = \projects_remb::STATUS_REFUNDED;
                $projectRepayment->update();

                $repaymentLog->fin              = date('Y-m-d H:i:s');
                $repaymentLog->montant_remb_net = $rembNetTotal;
                $repaymentLog->etat             = $iTotalTaxAmount;
                $repaymentLog->nb_pret_remb     = $nb_pret_remb;
                $repaymentLog->update();

                if (0 == $echeanciers->counter('id_project = ' . $r['id_project'] . ' AND status = 0')) {
                    /** @var ProjectManager $projectManager */
                    $projectManager = $this->getContainer()->get('unilend.service.project_manager');
                    $projectManager->addProjectStatus(Users::USER_ID_CRON, ProjectsStatus::REMBOURSE, $projects);

                    /** @var MailerManager $mailerManager */
                    $mailerManager = $this->getContainer()->get('unilend.service.email_manager');
                    $mailerManager->setLogger($logger);
                    $mailerManager->sendInternalNotificationEndOfRepayment($projects);
                    $mailerManager->sendClientNotificationEndOfRepayment($projects);
                }

                $stopWatchEvent = $stopWatch->stop('autoRepayment');

                if ($this->getContainer()->getParameter('kernel.environment') === 'prod') {
                    $slackManager = $this->getContainer()->get('unilend.service.slack_manager');
                    $message      = $slackManager->getProjectName($projects) .
                        ' - Remboursement automatique effectué en '
                        . round($stopWatchEvent->getDuration() / 1000, 1) . ' secondes (' . $nb_pret_remb . ' prêts, échéance #' . $r['ordre'] . ').';
                    $slackManager->sendMessage($message);
                }
            } else {
                $projectRepayment->get($r['id_project_remb'], 'id_project_remb');
                $projectRepayment->status = \projects_remb::STATUS_ERROR;
                $projectRepayment->update();

                $repaymentLog->fin = date('Y-m-d H:i:s');
                $repaymentLog->update();

                $logger->error(
                    'The total repayment amount is zero for the project ' . $r['id_project'] . ' (order: ' . $r['ordre'] . '). Please see previous logs for more details.',
                    array('class' => __CLASS__, 'function' => __FUNCTION__)
                );

                continue;
            }
        }
    }
}
