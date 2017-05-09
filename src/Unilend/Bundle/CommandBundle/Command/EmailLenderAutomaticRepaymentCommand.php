<?php
namespace Unilend\Bundle\CommandBundle\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
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
        /** @var EntityManagerSimulator $entityManagerSimulator */
        $entityManagerSimulator = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        /** @var \echeanciers $echeanciers */
        $echeanciers = $entityManagerSimulator->getRepository('echeanciers');
        /** @var \transactions $transactions */
        $transactions = $entityManagerSimulator->getRepository('transactions');
        /** @var \companies $companies */
        $companies = $entityManagerSimulator->getRepository('companies');
        /** @var \notifications $notifications */
        $notifications = $entityManagerSimulator->getRepository('notifications');
        /** @var \loans $loans */
        $loans = $entityManagerSimulator->getRepository('loans');
        /** @var \projects_status_history $projects_status_history */
        $projects_status_history = $entityManagerSimulator->getRepository('projects_status_history');
        /** @var \projects $projects */
        $projects = $entityManagerSimulator->getRepository('projects');
        /** @var \clients_gestion_notifications $clients_gestion_notifications */
        $clients_gestion_notifications = $entityManagerSimulator->getRepository('clients_gestion_notifications');
        /** @var \clients_gestion_mails_notif $clients_gestion_mails_notif */
        $clients_gestion_mails_notif = $entityManagerSimulator->getRepository('clients_gestion_mails_notif');
        /** @var \settings $settings */
        $settings = $entityManagerSimulator->getRepository('settings');
        /** @var \dates $dates */
        $dates = Loader::loadLib('dates');
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');

        $lEcheances = $echeanciers->getRepaidRepaymentToNotify(0, 300);
        $settings->get('Facebook', 'type');
        $sFB      = $settings->value;

        $settings->get('Twitter', 'type');
        $sTwitter = $settings->value;

        foreach ($lEcheances as $e) {
            if (
                $echeanciers->get($e['id_echeancier'], 'id_echeancier')
                && $loans->get($echeanciers->id_loan)
            ) {
                $rembNet = bcdiv($transactions->sum(' id_echeancier = ' . $echeanciers->id_echeancier, 'montant'), 100, 2);
                $transactions->get($echeanciers->id_echeancier, 'type_transaction = ' . \transactions_types::TYPE_LENDER_REPAYMENT_CAPITAL . ' AND id_echeancier');

                $wallet = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->find($loans->id_lender);
                if (null !== $wallet && Clients::STATUS_ONLINE == $wallet->getIdClient()->getStatus()) {
                    $projects->get($echeanciers->id_project, 'id_project');
                    $companies->get($projects->id_company, 'id_company');

                    $lastRepaymentLender = (0 == $echeanciers->counter('id_project = ' . $projects->id_project . ' AND id_loan = ' . $loans->id_loan . ' AND status = 0 AND id_lender = ' . $echeanciers->id_lender));

                    $dernierStatut     = $projects_status_history->select('id_project = ' . $projects->id_project, 'added DESC, id_project_status_history DESC', 0, 1);
                    $dateDernierStatut = $dernierStatut[0]['added'];
                    $timeAdd           = strtotime($dateDernierStatut);
                    $day               = date('d', $timeAdd);
                    $month             = $dates->tableauMois['fr'][date('n', $timeAdd)];
                    $year              = date('Y', $timeAdd);
                    $euros             = ($rembNet >= 2) ? ' euros' : ' euro';
                    $rembNetEmail      = $ficelle->formatNumber($rembNet) . $euros;
                    $availableBalance  = $wallet->getAvailableBalance();
                    $euros             = ($availableBalance >= 2) ? ' euros' : ' euro';
                    $solde             = $ficelle->formatNumber($availableBalance) . $euros;

                    $notifications->type       = \notifications::TYPE_REPAYMENT;
                    $notifications->id_lender  = $echeanciers->id_lender;
                    $notifications->id_project = $echeanciers->id_project;
                    $notifications->amount     = bcmul($rembNet, 100);
                    $notifications->create();

                    $clients_gestion_mails_notif->id_client       = $wallet->getIdClient()->getIdClient();
                    $clients_gestion_mails_notif->id_notif        = \clients_gestion_type_notif::TYPE_REPAYMENT;
                    $clients_gestion_mails_notif->date_notif      = $echeanciers->date_echeance_reel;
                    $clients_gestion_mails_notif->id_notification = $notifications->id_notification;
                    $clients_gestion_mails_notif->id_transaction  = $transactions->id_transaction;
                    $clients_gestion_mails_notif->create();

                    if (true === $clients_gestion_notifications->getNotif($wallet->getIdClient()->getIdClient(), \clients_gestion_type_notif::TYPE_REPAYMENT, 'immediatement')) {
                        $clients_gestion_mails_notif->get($clients_gestion_mails_notif->id_clients_gestion_mails_notif, 'id_clients_gestion_mails_notif');
                        $clients_gestion_mails_notif->immediatement = 1;
                        $clients_gestion_mails_notif->update();

                        $sUrl    = $this->getContainer()->getParameter('router.request_context.scheme') . '://' . $this->getContainer()->getParameter('url.host_default');
                        $varMail = [
                            'surl'                  => $sUrl,
                            'url'                   => $sUrl,
                            'prenom_p'              => $wallet->getIdClient()->getPrenom(),
                            'mensualite_p'          => $rembNetEmail,
                            'mensualite_avantfisca' => bcdiv($echeanciers->montant, 100, 2),
                            'nom_entreprise'        => $companies->name,
                            'date_bid_accepte'      => $day . ' ' . $month . ' ' . $year,
                            'solde_p'               => $solde,
                            'motif_virement'        => $wallet->getWireTransferPattern(),
                            'lien_fb'               => $sFB,
                            'lien_tw'               => $sTwitter,
                            'annee'                 => date('Y'),
                            'date_pret'             => $dates->formatDateComplete($loans->added)
                        ];

                        if ($lastRepaymentLender) {
                            /** @var TemplateMessage $message */
                            $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('preteur-dernier-remboursement', $varMail);
                        } else {
                            /** @var TemplateMessage $message */
                            $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('preteur-remboursement', $varMail);
                        }

                        $message->setTo($wallet->getIdClient()->getEmail());
                        $mailer = $this->getContainer()->get('mailer');
                        $mailer->send($message);
                    }
                }
                $echeanciers->status_email_remb = 1;
                $echeanciers->update();
            }
        }
    }
}
