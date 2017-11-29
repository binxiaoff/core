<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\core\Loader;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class EmailBorrowerReminderBeforeRecoveryCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('email:borrower:reminder_before_recovery')
            ->setDescription('Send email to borrower with unpaid repayments before recovery process');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $entityManger */
        $entityManger = $this->getContainer()->get('unilend.service.entity_manager');

        /** @var \projects $projectsRepository */
        $projectsRepository = $entityManger->getRepository('projects');
        $projects           = $projectsRepository->getProblematicProjectsWithUpcomingRepayment();

        if (false === empty($projects)) {
            /** @var \ficelle $ficelle */
            $ficelle = Loader::loadLib('ficelle');

            /** @var \clients $client */
            $client = $entityManger->getRepository('clients');
            /** @var \companies $company */
            $company = $entityManger->getRepository('companies');
            /** @var \echeanciers $lenderRepaymentSchedule */
            $lenderRepaymentSchedule = $entityManger->getRepository('echeanciers');
            /** @var \echeanciers_emprunteur $borrowerRepaymentSchedule */
            $borrowerRepaymentSchedule = $entityManger->getRepository('echeanciers_emprunteur');
            /** @var \loans $loans */
            $loans = $entityManger->getRepository('loans');
            /** @var \settings $settings */
            $settings = $entityManger->getRepository('settings');

            $settings->get('Virement - BIC', 'type');
            $bic = $settings->value;

            $settings->get('Virement - IBAN', 'type');
            $iban = $settings->value;

            $settings->get('Téléphone emprunteur', 'type');
            $borrowerServicePhoneNumber = $settings->value;

            $settings->get('Adresse emprunteur', 'type');
            $borrowerServiceEmail = $settings->value;

            foreach ($projects as $project) {
                $company->get($project['id_company']);
                $client->get($company->id_client_owner);

                $nextRepayment = $borrowerRepaymentSchedule->select('id_project = ' . $project['id_project'] . ' AND date_echeance_emprunteur > DATE(NOW())', 'date_echeance_emprunteur ASC', 0, 1);
                $keywords  = [
                    'directorName'               => (empty($client->civilite) ? 'M.' : $client->civilite) . ' ' . $client->nom,
                    'companyName'                => $company->name,
                    'latePaymentAmount'          => $ficelle->formatNumber(($nextRepayment[0]['montant'] + $nextRepayment[0]['commission'] + $nextRepayment[0]['tva']) / 100),
                    'owedCapitalAmount'          => $ficelle->formatNumber($lenderRepaymentSchedule->getOwedCapital(['id_project' => $project['id_project']])),
                    'lendersCount'               => $loans->getNbPreteurs($project['id_project']),
                    'nextPaymentDate'            => \DateTime::createFromFormat('Y-m-d H:i:s', $nextRepayment[0]['date_echeance_emprunteur'])->format('d/m/Y'), // @todo Intl
                    'projectId'                  => $project['id_project'],
                    'borrowerServicePhoneNumber' => $borrowerServicePhoneNumber,
                    'borrowerServiceEmail'       => $borrowerServiceEmail,
                    'bic'                        => $bic,
                    'iban'                       => $iban
                ];

                $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('emprunteur-relance-avant-recouvrement', $keywords);

                try {
                    $message->setTo(trim($client->email));
                    $mailer = $this->getContainer()->get('mailer');
                    $mailer->send($message);
                } catch (\Exception $exception) {
                    $logger = $this->getContainer()->get('monolog.logger.console');
                    $logger->warning(
                        'Could not send email: emprunteur-relance-avant-recouvrement - Exception: ' . $exception->getMessage(),
                        ['id_mail_template' => $message->getTemplateId(), 'id_client' => $client->id_client, 'class' => __CLASS__, 'function' => __FUNCTION__]
                    );
                }
            }
        }
    }
}
