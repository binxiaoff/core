<?php
namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage;
use Unilend\core\Loader;

class EmailLenderAutomaticRepaymentCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('email:lender:repayment_email')
            ->setDescription('For automatic repayments: creates repayment notifications and sends email if settings is on immediate');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var \echeanciers $echeanciers */
        $echeanciers = $entityManager->getRepository('echeanciers');
        /** @var \transactions $transactions */
        $transactions = $entityManager->getRepository('transactions');
        /** @var \lenders_accounts $lenders */
        $lenders = $entityManager->getRepository('lenders_accounts');
        /** @var \clients $clients */
        $clients = $entityManager->getRepository('clients');
        /** @var \companies $companies */
        $companies = $entityManager->getRepository('companies');
        /** @var \notifications $notifications */
        $notifications = $entityManager->getRepository('notifications');
        /** @var \loans $loans */
        $loans = $entityManager->getRepository('loans');
        /** @var \projects_status_history $projects_status_history */
        $projects_status_history = $entityManager->getRepository('projects_status_history');
        /** @var \projects $projects */
        $projects = $entityManager->getRepository('projects');
        /** @var \clients_gestion_notifications $clients_gestion_notifications */
        $clients_gestion_notifications = $entityManager->getRepository('clients_gestion_notifications');
        /** @var \clients_gestion_mails_notif $clients_gestion_mails_notif */
        $clients_gestion_mails_notif = $entityManager->getRepository('clients_gestion_mails_notif');
        /** @var \settings $settings */
        $settings = $entityManager->getRepository('settings');
        /** @var \dates $dates */
        $dates = Loader::loadLib('dates');
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');

        $lEcheances           = $echeanciers->selectEcheances_a_remb('status = 1 AND status_email_remb = 0 AND status_emprunteur = 1', '', 0, 300);
        $sUrl                 = $this->getContainer()->getParameter('router.request_context.scheme') . '://' . $this->getContainer()->getParameter('url.host_default');

        $settings->get('Facebook', 'type');
        $sFB      = $settings->value;

        $settings->get('Twitter', 'type');
        $sTwitter = $settings->value;

        $lastRepaymentProject = [];

        foreach ($lEcheances as $e) {
            if (
                $transactions->get($e['id_echeancier'], 'id_echeancier')
                && $lenders->get($e['id_lender'], 'id_lender_account')
                && $clients->get($lenders->id_client_owner, 'id_client')
            ) {
                $echeanciers->get($e['id_echeancier'], 'id_echeancier');

                if (1 == $clients->status) {
                    $projects->get($e['id_project'], 'id_project');
                    $companies->get($projects->id_company, 'id_company');

                    $loans->get($e['id_loan']);
                    $lastRepaymentLender = (0 == $echeanciers->counter('id_project = ' . $projects->id_project . ' AND id_loan = ' . $loans->id_loan . ' AND status = 0 AND id_lender = ' . $e['id_lender']));

                    if (false === isset($lastRepaymentProject[$projects->id_project])) {
                        $lastRepaymentProject[$projects->id_project] = $lastRepaymentLender;
                    }

                    $dernierStatut     = $projects_status_history->select('id_project = ' . $projects->id_project, 'id_project_status_history DESC', 0, 1);
                    $dateDernierStatut = $dernierStatut[0]['added'];
                    $timeAdd           = strtotime($dateDernierStatut);
                    $day               = date('d', $timeAdd);
                    $month             = $dates->tableauMois['fr'][date('n', $timeAdd)];
                    $year              = date('Y', $timeAdd);
                    $rembNet           = $e['rembNet'];
                    $euros             = ($rembNet >= 2) ? ' euros' : ' euro';
                    $rembNetEmail      = $ficelle->formatNumber($rembNet) . $euros;
                    $getsolde          = $transactions->getSolde($clients->id_client);
                    $euros             = ($getsolde >= 2) ? ' euros' : ' euro';
                    $solde             = $ficelle->formatNumber($getsolde) . $euros;

                    $notifications->type       = \notifications::TYPE_REPAYMENT;
                    $notifications->id_lender  = $e['id_lender'];
                    $notifications->id_project = $e['id_project'];
                    $notifications->amount     = bcmul($rembNet, 100);
                    $notifications->create();

                    $clients_gestion_mails_notif->id_client       = $lenders->id_client_owner;
                    $clients_gestion_mails_notif->id_notif        = \clients_gestion_type_notif::TYPE_REPAYMENT;
                    $clients_gestion_mails_notif->date_notif      = $echeanciers->date_echeance_reel;
                    $clients_gestion_mails_notif->id_notification = $notifications->id_notification;
                    $clients_gestion_mails_notif->id_transaction  = $transactions->id_transaction;
                    $clients_gestion_mails_notif->create();

                    if (true === $clients_gestion_notifications->getNotif($clients->id_client, \clients_gestion_type_notif::TYPE_REPAYMENT, 'immediatement')) {
                        $clients_gestion_mails_notif->get($clients_gestion_mails_notif->id_clients_gestion_mails_notif, 'id_clients_gestion_mails_notif');
                        $clients_gestion_mails_notif->immediatement = 1;
                        $clients_gestion_mails_notif->update();

                        $varMail = array(
                            'surl'                  => $sUrl,
                            'url'                   => $sUrl,
                            'prenom_p'              => $clients->prenom,
                            'mensualite_p'          => $rembNetEmail,
                            'mensualite_avantfisca' => bcdiv($e['montant'], 100),
                            'nom_entreprise'        => $companies->name,
                            'date_bid_accepte'      => $day . ' ' . $month . ' ' . $year,
                            'solde_p'               => $solde,
                            'motif_virement'        => $clients->getLenderPattern($clients->id_client),
                            'lien_fb'               => $sFB,
                            'lien_tw'               => $sTwitter,
                            'annee'                 => date('Y'),
                            'date_pret'             => $dates->formatDateComplete($loans->added)
                        );

                        if ($lastRepaymentLender) {
                            /** @var TemplateMessage $message */
                            $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('preteur-dernier-remboursement', $varMail);
                        } else {
                            /** @var TemplateMessage $message */
                            $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('preteur-remboursement', $varMail);
                        }

                        $message->setTo($clients->email);
                        $mailer = $this->getContainer()->get('mailer');
                        $mailer->send($message);
                    }
                }
                $echeanciers->status_email_remb = 1;
                $echeanciers->update();
            }
        }

        $settings->get('Adresse controle interne', 'type');
        $mailBO = $settings->value;

        foreach ($lastRepaymentProject as $idProject => $isLastRepayment) {
            if ($isLastRepayment) {
                $projects->get($idProject);
                $companies->get($projects->id_company);

                $varMail = array(
                    'surl'           => $sUrl,
                    'url'            => $sUrl,
                    'nom_entreprise' => $companies->name,
                    'nom_projet'     => $projects->title,
                    'id_projet'      => $projects->id_project,
                    'annee'          => date('Y')
                );

                /** @var TemplateMessage $messageBO */
                $messageBO = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('preteur-dernier-remboursement-controle', $varMail);
                $messageBO->setTo($mailBO);

                $mailer = $this->getContainer()->get('mailer');
                $mailer->send($messageBO);
            }
        }
    }
}
