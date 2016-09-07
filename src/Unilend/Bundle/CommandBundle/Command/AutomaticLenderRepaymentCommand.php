<?php
namespace Unilend\Bundle\CommandBundle\Command;


use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
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

        //Load for constant Use only
        $entityManager->getRepository('transactions_types');

        /** @var \dates $dates */
        $dates = Loader::loadLib('dates');

        /** @var LoggerInterface $logger */
        $logger = $this->getContainer()->get('monolog.logger.console');

        foreach ($projectRepayment->getProjectsToRepay(new \DateTime(), 1) as $r) {
            $repaymentLog->id_project       = $r['id_project'];
            $repaymentLog->ordre            = $r['ordre'];
            $repaymentLog->debut            = date('Y-m-d H:i:s');
            $repaymentLog->fin              = '0000-00-00 00:00:00';
            $repaymentLog->montant_remb_net = 0;
            $repaymentLog->etat             = 0;
            $repaymentLog->nb_pret_remb     = 0;
            $repaymentLog->create();

            $dernierStatut     = $projects_status_history->select('id_project = ' . $r['id_project'], 'id_project_status_history DESC', 0, 1);
            $dateDernierStatut = $dernierStatut[0]['added'];
            $timeAdd           = strtotime($dateDernierStatut);
            $day               = date('d', $timeAdd);
            $month             = $dates->tableauMois['fr'][date('n', $timeAdd)];
            $year              = date('Y', $timeAdd);
            $Total_rembNet     = 0;
            $lEcheances        = $echeanciers->selectEcheances_a_remb('id_project = ' . $r['id_project'] . ' AND status_emprunteur = 1 AND ordre = ' . $r['ordre'] . ' AND status = 0');
            $Total_etat        = 0;
            $nb_pret_remb      = 0;

            if ($lEcheances != false) {
                foreach ($lEcheances as $e) {
                    if ($transactions->get($e['id_echeancier'], 'id_echeancier') == false) {
                        $rembNet = $e['rembNet'];
                        $etat    = $e['etat'];

                        $Total_rembNet = bcadd($rembNet, $Total_rembNet, 2);
                        $Total_etat    = bcadd($etat, $Total_etat, 2);
                        $nb_pret_remb  = ($nb_pret_remb + 1);

                        $lenders->get($e['id_lender'], 'id_lender_account');

                        $echeanciers->get($e['id_echeancier'], 'id_echeancier');
                        $echeanciers->status             = 1;
                        $echeanciers->date_echeance_reel = date('Y-m-d H:i:s');
                        $echeanciers->update();

                        $transactions->id_client        = $lenders->id_client_owner;
                        $transactions->montant          = bcmul($rembNet, 100);
                        $transactions->id_echeancier    = $e['id_echeancier'];
                        $transactions->id_langue        = 'fr';
                        $transactions->date_transaction = date('Y-m-d H:i:s');
                        $transactions->status           = \transactions::PAYMENT_STATUS_OK;
                        $transactions->etat             = \transactions::STATUS_VALID;
                        $transactions->type_transaction = \transactions_types::TYPE_LENDER_REPAYMENT;
                        $transactions->transaction      = \transactions::VIRTUAL;
                        $transactions->create();

                        $wallets_lines->id_lender                = $e['id_lender'];
                        $wallets_lines->type_financial_operation = \wallets_lines::TYPE_REPAYMENT;
                        $wallets_lines->id_transaction           = $transactions->id_transaction;
                        $wallets_lines->status                   = 1;
                        $wallets_lines->type                     = \wallets_lines::VIRTUAL;
                        $wallets_lines->amount                   = bcmul($rembNet, 100);
                        $wallets_lines->create();
                    } else {
                        $logger->error(
                            'The transaction has already been created for the repayment (id_echeancier: ' . $e['id_echeancier'] . '). The repayment may have been repaid manually.',
                            array('class' => __CLASS__, 'function' => __FUNCTION__)
                        );
                    }
                }
            } else {
                $projectRepayment->get($r['id_project_remb'], 'id_project_remb');
                $projectRepayment->status = \projects_remb::STATUS_ERROR;
                $projectRepayment->update();

                $repaymentLog->fin = date('Y-m-d H:i:s');
                $repaymentLog->update();

                $logger->error(
                    'Cannot find pending lenders\'s repayment schedule to repay for project ' . $r['id_project'] . ' (order: ' . $r['ordre'] . '). The repayment may have been repaid manually.',
                    array('class' => __CLASS__, 'function' => __FUNCTION__)
                );

                continue;
            }

            if ($Total_rembNet > 0) {
                /** @var \clients $emprunteur */
                $emprunteur = $entityManager->getRepository('clients');

                $projects->get($r['id_project'], 'id_project');
                $companies->get($projects->id_company, 'id_company');
                $emprunteur->get($companies->id_client_owner, 'id_client');
                $echeanciers_emprunteur->get($r['id_project'], ' ordre = ' . $r['ordre'] . ' AND id_project');

                $transactions->unsetData();

                $transactions->montant_unilend          = -bcmul($Total_rembNet, 100);
                $transactions->montant_etat             = bcmul($Total_etat, 100);
                $transactions->id_echeancier_emprunteur = $echeanciers_emprunteur->id_echeancier_emprunteur;
                $transactions->id_langue                = 'fr';
                $transactions->date_transaction         = date('Y-m-d H:i:s');
                $transactions->status                   = \transactions::PAYMENT_STATUS_OK;
                $transactions->etat                     = \transactions::STATUS_VALID;
                $transactions->type_transaction         = \transactions_types::TYPE_UNILEND_REPAYMENT;
                $transactions->transaction              = \transactions::VIRTUAL;
                $transactions->create();

                $bank_unilend->id_transaction         = $transactions->id_transaction;
                $bank_unilend->id_project             = $r['id_project'];
                $bank_unilend->montant                = -bcmul($Total_rembNet, 100);
                $bank_unilend->etat                   = bcmul($Total_etat, 100);
                $bank_unilend->type                   = \bank_unilend::TYPE_REPAYMENT_LENDER;
                $bank_unilend->id_echeance_emprunteur = $echeanciers_emprunteur->id_echeancier_emprunteur;
                $bank_unilend->status                 = 1;
                $bank_unilend->create();

                $oAccountUnilend->addDueDateCommssion($echeanciers_emprunteur->id_echeancier_emprunteur);

                /** @var \settings $settings */
                $settings = $entityManager->getRepository('settings');
                $settings->get('Facebook', 'type');
                $sFB = $settings->value;
                $settings->get('Twitter', 'type');
                $sTwitter = $settings->value;
                $sUrl     = $this->getContainer()->getParameter('router.request_context.scheme') . '://' . $this->getContainer()->getParameter('url.host_default');

                /** @var \ficelle $ficelle */
                $ficelle = Loader::loadLib('ficelle');

                $varMail = array(
                    'surl'            => $sUrl,
                    'url'             => $sUrl,
                    'prenom'          => $emprunteur->prenom,
                    'pret'            => $ficelle->formatNumber($projects->amount),
                    'entreprise'      => stripslashes(trim($companies->name)),
                    'projet-title'    => $projects->title,
                    'compte-p'        => $sUrl,
                    'projet-p'        => $sUrl . '/projects/detail/' . $projects->slug,
                    'link_facture'    => $sUrl . '/pdf/facture_ER/' . $emprunteur->hash . '/' . $r['id_project'] . '/' . $r['ordre'],
                    'datedelafacture' => $day . ' ' . $month . ' ' . $year,
                    'mois'            => strtolower($dates->tableauMois['fr'][date('n')]),
                    'annee'           => date('Y'),
                    'lien_fb'         => $sFB,
                    'lien_tw'         => $sTwitter,
                    'montantRemb'     => $ficelle->formatNumber($Total_rembNet)
                );

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

                $settings->get('Commission remboursement', 'type');
                $fCommissionRate = $settings->value;

                $aLenderRepayment = $oLenderRepaymentSchedule->select('id_project = ' . $projects->id_project . ' AND ordre = ' . $r['ordre'], '', 0, 1);

                if ($oBorrowerRepaymentSchedule->get($projects->id_project, 'ordre = ' . $r['ordre'] . '  AND id_project')) {
                    $oInvoice->num_facture     = 'FR-E' . date('Ymd', strtotime($aLenderRepayment[0]['date_echeance_reel'])) . str_pad($oInvoiceCounter->compteurJournalier($projects->id_project,
                            $aLenderRepayment[0]['date_echeance_reel']), 5, '0', STR_PAD_LEFT);
                    $oInvoice->date            = $aLenderRepayment[0]['date_echeance_reel'];
                    $oInvoice->id_company      = $companies->id_company;
                    $oInvoice->id_project      = $projects->id_project;
                    $oInvoice->ordre           = $r['ordre'];
                    $oInvoice->type_commission = \factures::TYPE_COMMISSION_REMBOURSEMENT;
                    $oInvoice->commission      = bcmul($fCommissionRate, 100);
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
                $repaymentLog->montant_remb_net = bcmul($Total_rembNet, 100);
                $repaymentLog->etat             = bcmul($Total_etat, 100);
                $repaymentLog->nb_pret_remb     = $nb_pret_remb;
                $repaymentLog->update();

                if (0 == $echeanciers->counter('id_project = ' . $r['id_project'] . ' AND status = 0')) {
                    $settings->get('Adresse controle interne', 'type');
                    $mailBO = $settings->value;

                    $varMail = array(
                        'surl'           => $sUrl,
                        'url'            => $sUrl,
                        'nom_entreprise' => $companies->name,
                        'nom_projet'     => $projects->title,
                        'id_projet'      => $projects->id_project,
                        'annee'          => date('Y')
                    );

                    /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                    $messageBO = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('preteur-dernier-remboursement-controle', $varMail);
                    $messageBO->setTo($mailBO);

                    $mailer = $this->getContainer()->get('mailer');
                    $mailer->send($messageBO);
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
