<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Unilend\Bundle\CoreBusinessBundle\Entity\Notifications;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationSubType;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
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
        $entityManagerSimulator = $this->getContainer()->get('unilend.service.entity_manager');
        $entityManager          = $this->getContainer()->get('doctrine.orm.entity_manager');
        $numberFormatter        = $this->getContainer()->get('number_formatter');
        $currencyFormatter      = $this->getContainer()->get('currency_formatter');

        /** @var \projects $project */
        $project = $entityManagerSimulator->getRepository('projects');
        /** @var \echeanciers $lenderRepaymentSchedule */
        $lenderRepaymentSchedule = $entityManagerSimulator->getRepository('echeanciers');
        /** @var \echeanciers_emprunteur $borrowerRepaymentSchedule */
        $borrowerRepaymentSchedule = $entityManagerSimulator->getRepository('echeanciers_emprunteur');
        /** @var \receptions $sfpmeiFeedIncoming */
        $sfpmeiFeedIncoming = $entityManagerSimulator->getRepository('receptions');
        /** @var \clients_gestion_mails_notif $emailNotification */
        $emailNotification = $entityManagerSimulator->getRepository('clients_gestion_mails_notif');
        /** @var \clients_gestion_notifications $notificationSettings */
        $notificationSettings = $entityManagerSimulator->getRepository('clients_gestion_notifications');
        /** @var \notifications $notification */
        $notification = $entityManagerSimulator->getRepository('notifications');
        /** @var \companies $company */
        $company = $entityManagerSimulator->getRepository('companies');
        /** @var \loans $loan */
        $loan = $entityManagerSimulator->getRepository('loans');
        /** @var \remboursement_anticipe_mail_a_envoyer $earlyRepaymentEmail */
        $earlyRepaymentEmail = $entityManagerSimulator->getRepository('remboursement_anticipe_mail_a_envoyer');
        /** @var \clients $client */
        $client = $entityManagerSimulator->getRepository('clients');
        /** @var \settings $settings */
        $settings = $entityManagerSimulator->getRepository('settings');

        $operationRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation');

        $loanOperationType           = $entityManager->getRepository('UnilendCoreBusinessBundle:OperationType')->findOneBy(['label' => OperationType::LENDER_LOAN]);
        $earlyRepaymentOperationType = $entityManager->getRepository('UnilendCoreBusinessBundle:OperationSubType')->findOneBy(['label' => OperationSubType::CAPITAL_REPAYMENT_EARLY]);

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
            $financeOperation = $operationRepository->findOneBy(['idProject' => $project->id_project, 'idType' => $loanOperationType]);

            $projectLenders           = $lenderRepaymentSchedule->get_liste_preteur_on_project($project->id_project);
            $remainingRepaymentsCount = $borrowerRepaymentSchedule->counter('id_project = ' . $project->id_project . ' AND status_ra = 1');

            $keywords = [
                'surl'               => $staticUrl,
                'url'                => $frontUrl,
                'prenom'             => $client->prenom,
                'raison_sociale'     => $company->name,
                'montant'            => $numberFormatter->format($project->amount),
                'nb_preteurs'        => count($projectLenders),
                'duree'              => $project->period,
                'duree_financement'  => (new \DateTime($project->date_publication))->diff(new \DateTime($project->date_retrait))->d,
                'date_financement'   => $financeOperation->getAdded()->format('d/m/Y'),
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
                $wallet = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->find($projectLender['id_lender']);

                if (Clients::STATUS_ONLINE === $wallet->getIdClient()->getStatus()) {
                    $lenderRemainingCapital = $operationRepository->getTotalEarlyRepaymentByLoan($projectLender['id_loan']);

                    $notification->type       = Notifications::TYPE_REPAYMENT;
                    $notification->id_lender  = $projectLender['id_lender'];
                    $notification->id_project = $project->id_project;
                    $notification->amount     = bcmul($lenderRemainingCapital, 100);
                    $notification->create();

                    $earlyRepaymentOperation = $operationRepository->findOneBy(['idLoan' => $projectLender['id_loan'], 'idSubType' => $earlyRepaymentOperationType]);
                    $walletBalanceHistory    = $entityManager->getRepository('UnilendCoreBusinessBundle:WalletBalanceHistory')->findOneBy([
                        'idWallet'    => $wallet,
                        'idOperation' => $earlyRepaymentOperation
                    ]);

                    $emailNotification->unsetData();
                    $emailNotification->id_client                 = $wallet->getIdClient()->getIdClient();
                    $emailNotification->id_notif                  = \clients_gestion_type_notif::TYPE_REPAYMENT;
                    $emailNotification->date_notif                = $earlyRepaymentOperation->getAdded()->format('Y-m-d H:i:s');
                    $emailNotification->id_notification           = $notification->id_notification;
                    $emailNotification->id_wallet_balance_history = $walletBalanceHistory->getId();

                    if ($notificationSettings->getNotif($wallet->getIdClient()->getIdClient(), \clients_gestion_type_notif::TYPE_REPAYMENT, 'immediatement')) {
                        $emailNotification->immediatement = 1;

                        $loan->get($projectLender['id_loan'], 'id_loan');

                        $accountBalance = $wallet->getAvailableBalance();
                        $keywords       = [
                            'surl'                 => $staticUrl,
                            'url'                  => $frontUrl,
                            'prenom_p'             => $wallet->getIdClient()->getPrenom(),
                            'nomproject'           => $project->title,
                            'nom_entreprise'       => $company->name,
                            'taux_bid'             => $numberFormatter->format($loan->rate),
                            'nbecheancesrestantes' => $remainingRepaymentsCount,
                            'interetsdejaverses'   => $numberFormatter->format((float) $lenderRepaymentSchedule->getRepaidInterests([
                                'id_project' => $project->id_project,
                                'id_loan'    => $projectLender['id_loan'],
                                'id_lender'  => $projectLender['id_lender']
                            ])),
                            'crdpreteur'           => $currencyFormatter->formatCurrency($lenderRemainingCapital, 'EUR'),
                            'Datera'               => date('d/m/Y'),
                            'solde_p'              => $currencyFormatter->formatCurrency($accountBalance, 'EUR'),
                            'motif_virement'       => $wallet->getWireTransferPattern(),
                            'lien_fb'              => $facebookLink,
                            'lien_tw'              => $twitterLink,
                            'annee'                => date('Y')
                        ];

                        /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                        $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('preteur-remboursement-anticipe', $keywords);
                        $message->setTo($wallet->getIdClient()->getEmail());
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
