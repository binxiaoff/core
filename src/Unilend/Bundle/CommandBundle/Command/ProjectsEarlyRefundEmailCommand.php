<?php
namespace Unilend\Bundle\CommandBundle\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputOption;
use Unilend\core\Loader;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProjectsEarlyRefundEmailCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('projects:early_refund_email')
            ->setDescription('Check projects that are in FUNDING status')
            ->addOption('limit-project', 'l', InputOption::VALUE_REQUIRED, 'Number of projects to process');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('unilend.service.entity_manager');

        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');

        /** @var \projects $project */
        $project = $entityManager->getRepository('projects');
        /** @var \echeanciers $lenderRepaymentSchedule */
        $lenderRepaymentSchedule = $entityManager->getRepository('echeanciers');
        /** @var \echeanciers_emprunteur $borrowerRepaymentSchedule */
        $borrowerRepaymentSchedule = $entityManager->getRepository('echeanciers_emprunteur');
        /** @var \receptions $sfpmeiFeedIncoming */
        $sfpmeiFeedIncoming = $entityManager->getRepository('receptions');
        /** @var \transactions $borrowerTransaction */
        $borrowerTransaction = $entityManager->getRepository('transactions');
        /** @var \transactions $lenderTransaction */
        $lenderTransaction = $entityManager->getRepository('transactions');
        /** @var \lenders_accounts $lender */
        $lender = $entityManager->getRepository('lenders_accounts');
        /** @var \clients $client */
        $client = $entityManager->getRepository('clients');
        /** @var \clients_gestion_mails_notif $emailNotification */
        $emailNotification = $entityManager->getRepository('clients_gestion_mails_notif');
        /** @var \clients_gestion_notifications $notificationSettings */
        $notificationSettings = $entityManager->getRepository('clients_gestion_notifications');
        /** @var \notifications $notification */
        $notification = $entityManager->getRepository('notifications');
        /** @var \companies $company */
        $company = $entityManager->getRepository('companies');
        /** @var \loans $loan */
        $loan = $entityManager->getRepository('loans');
        /** @var \remboursement_anticipe_mail_a_envoyer $earlyRepaymentEmail */
        $earlyRepaymentEmail = $entityManager->getRepository('remboursement_anticipe_mail_a_envoyer');
        /** @var \settings $settings */
        $settings = $entityManager->getRepository('settings');

        $staticUrl = $this->getContainer()->get('assets.packages')->getUrl('');
        $frontUrl  = $this->getContainer()->getParameter('router.request_context.scheme') . '://' . $this->getContainer()->getParameter('url.host_default');

        $settings->get('Facebook', 'type');
        $facebookLink = $settings->value;

        $settings->get('Twitter', 'type');
        $twitterLink = $settings->value;

        $limit = $input->getOption('limit-project');
        $limit = $limit ? $limit : 1;

        foreach ($earlyRepaymentEmail->select('statut = 0', 'added ASC', '', $limit) as $earlyRefundPendingEmail) {
            $sfpmeiFeedIncoming->get($earlyRefundPendingEmail['id_reception']);
            $project->get($sfpmeiFeedIncoming->id_project);
            $company->get($project->id_company, 'id_company');
            $client->get($company->id_client_owner);
            $borrowerTransaction->get($project->id_project . '" AND type_transaction = "' . \transactions_types::TYPE_BORROWER_BANK_TRANSFER_CREDIT, 'id_project');

            $projectLenders           = $lenderRepaymentSchedule->get_liste_preteur_on_project($project->id_project);
            $remainingRepaymentsCount = $borrowerRepaymentSchedule->counter('id_project = ' . $project->id_project . ' AND status_ra = 1');

            $keywords = [
                'surl'               => $staticUrl,
                'url'                => $frontUrl,
                'prenom'             => $client->prenom,
                'raison_sociale'     => $company->name,
                'montant'            => $ficelle->formatNumber($project->amount, 0),
                'nb_preteurs'        => count($projectLenders),
                'duree'              => $project->period,
                'duree_financement'  => (new \DateTime($project->date_publication))->diff(new \DateTime($project->date_retrait))->d,
                'date_financement'   => \DateTime::createFromFormat('Y-m-d H:i:s', $borrowerTransaction->added)->format('d/m/Y'),
                'date_remboursement' => \DateTime::createFromFormat('Y-m-d H:i:s', $sfpmeiFeedIncoming->added)->format('d/m/Y'),
                'lien_fb'            => $facebookLink,
                'lien_tw'            => $twitterLink,
                'annee'              => date('Y')
            ];

            /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
            $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('emprunteur-remboursement-anticipe', $keywords);
            $message->setTo($client->email);
            $mailer = $this->getContainer()->get('mailer');
            $mailer->send($message);

            foreach ($projectLenders as $projectLender) {
                $lender->get($projectLender['id_lender'], 'id_lender_account');
                $client->get($lender->id_client_owner, 'id_client');

                if ($client->status == 1) {
                    $lenderRemainingCapital = $borrowerTransaction->sum('type_transaction = ' . \transactions_types::TYPE_LENDER_ANTICIPATED_REPAYMENT .' AND id_client = ' . $client->id_client . ' AND id_loan_remb = ' . $projectLender['id_loan'] . ' AND id_project = ' . $project->id_project, 'montant');

                    $notification->type       = \notifications::TYPE_REPAYMENT;
                    $notification->id_lender  = $projectLender['id_lender'];
                    $notification->id_project = $project->id_project;
                    $notification->amount     = $lenderRemainingCapital;
                    $notification->create();

                    $lenderTransaction->get($projectLender['id_loan'], 'id_loan_remb');

                    $emailNotification->unsetData();
                    $emailNotification->id_client       = $client->id_client;
                    $emailNotification->id_notif        = \clients_gestion_type_notif::TYPE_REPAYMENT;
                    $emailNotification->date_notif      = $lenderTransaction->added;
                    $emailNotification->id_notification = $notification->id_notification;
                    $emailNotification->id_transaction  = $lenderTransaction->id_transaction;

                    if ($notificationSettings->getNotif($client->id_client, \clients_gestion_type_notif::TYPE_REPAYMENT, 'immediatement')) {
                        $emailNotification->immediatement = 1;

                        $loan->get($projectLender['id_loan'], 'id_loan');

                        $accountBalance = $lenderTransaction->getSolde($client->id_client);
                        $keywords       = [
                            'surl'                 => $staticUrl,
                            'url'                  => $frontUrl,
                            'prenom_p'             => $client->prenom,
                            'nomproject'           => $project->title,
                            'nom_entreprise'       => $company->name,
                            'taux_bid'             => $ficelle->formatNumber($loan->rate),
                            'nbecheancesrestantes' => $remainingRepaymentsCount,
                            'interetsdejaverses'   => $ficelle->formatNumber($lenderRepaymentSchedule->getRepaidInterests(array('id_project' => $project->id_project, 'id_loan' => $projectLender['id_loan'], 'id_lender' => $projectLender['id_lender']))),
                            'crdpreteur'           => $ficelle->formatNumber($lenderRemainingCapital / 100) . (($lenderRemainingCapital / 100) >= 2 ? ' euros' : ' euro'),
                            'Datera'               => date('d/m/Y'),
                            'solde_p'              => $ficelle->formatNumber($accountBalance) . ($accountBalance >= 2 ? ' euros' : ' euro'),
                            'motif_virement'       => $client->getLenderPattern($client->id_client),
                            'lien_fb'              => $facebookLink,
                            'lien_tw'              => $twitterLink,
                            'annee'                => date('Y')
                        ];

                        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                        $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('preteur-remboursement-anticipe', $keywords);
                        $message->setTo($client->email);
                        $mailer = $this->getContainer()->get('mailer');
                        $mailer->send($message);
                    }

                    $emailNotification->create();
                }
            }

            $earlyRepaymentEmail->get($earlyRefundPendingEmail['id_remboursement_anticipe_mail_a_envoyer']);
            $earlyRepaymentEmail->statut     = 1;
            $earlyRepaymentEmail->date_envoi = date('Y-m-d H:i:s');
            $earlyRepaymentEmail->update();
        }
    }
}
