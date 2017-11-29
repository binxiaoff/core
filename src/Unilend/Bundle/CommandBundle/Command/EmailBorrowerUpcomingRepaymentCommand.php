<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\core\Loader;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class EmailBorrowerUpcomingRepaymentCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('email:borrower:upcoming_repayment')
            ->setDescription('Send emails to borrower when a repayment is coming');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $entityManger */
        $entityManger = $this->getContainer()->get('unilend.service.entity_manager');
        /** @var \prelevements $directDebit */
        $directDebit = $entityManger->getRepository('prelevements');
        /** @var \projects $project */
        $project = $entityManger->getRepository('projects');
        /** @var \companies $company */
        $company = $entityManger->getRepository('companies');
        /** @var \clients $client */
        $client = $entityManger->getRepository('clients');
        /** @var \loans $loans */
        $loans = $entityManger->getRepository('loans');
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');

        $upcomingRepayments = $directDebit->getUpcomingRepayments(7);

        /** @var \settings $settings */
        $settings = $entityManger->getRepository('settings');
        $settings->get('Téléphone emprunteur', 'type');
        $borrowerServicePhoneNumber = $settings->value;

        $settings->get('Adresse emprunteur', 'type');
        $borrowerServiceEmail = $settings->value;

        foreach ($upcomingRepayments as $repayment) {
            $project->get($repayment['id_project']);
            $company->get($project->id_company);

            if (false === empty($company->prenom_dirigeant) && false === empty($company->email_dirigeant)) {
                $firstName   = $company->prenom_dirigeant;
                $clientEmail = $company->email_dirigeant;
            } else {
                $client->get($company->id_client_owner);
                $firstName   = $client->prenom;
                $clientEmail = $client->email;
            }

            $keywords = [
                'firstName'                  => $firstName,
                'companyName'                => $company->name,
                'projectAmount'              => $ficelle->formatNumber($project->amount, 0),
                'nextPaymentAmount'          => $ficelle->formatNumber($repayment['montant'] / 100),
                'nextPaymentDate'            => date('d/m/Y', strtotime($repayment['date_echeance_emprunteur'])),
                'lendersCount'               => $loans->getNbPreteurs($repayment['id_project']),
                'borrowerServicePhoneNumber' => $borrowerServicePhoneNumber,
                'borrowerServiceEmail'       => $borrowerServiceEmail
            ];

            /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
            $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('mail-echeance-emprunteur', $keywords);

            try {
                $message->setTo($clientEmail);
                $mailer = $this->getContainer()->get('mailer');
                $mailer->send($message);
            } catch (\Exception $exception) {
                $this->getContainer()->get('monolog.logger.console')->warning(
                    'Could not send email: mail-echeance-emprunteur - Exception: ' . $exception->getMessage(),
                    ['id_mail_template' => $message->getTemplateId(), 'id_client' => $company->id_client_owner, 'class' => __CLASS__, 'function' => __FUNCTION__]
                );
            }
        }
    }
}
