<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\Prelevements;

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
        $entityManger    = $this->getContainer()->get('doctrine.orm.entity_manager');
        $numberFormatter = $this->getContainer()->get('number_formatter');

        $loanRepository     = $entityManger->getRepository('UnilendCoreBusinessBundle:Loans');
        $settingsRepository = $entityManger->getRepository('UnilendCoreBusinessBundle:Settings');

        /** @var Prelevements[] $upcomingDirectDebits */
        $upcomingDirectDebits = $entityManger->getRepository('UnilendCoreBusinessBundle:Prelevements')->getUpcomingDirectDebits(7);

        $borrowerServicePhoneNumber = $settingsRepository->findOneBy(['type' => 'Téléphone emprunteur'])->getValue();
        $borrowerServiceEmail       = $settingsRepository->findOneBy(['type' => 'Adresse emprunteur'])->getValue();

        foreach ($upcomingDirectDebits as $directDebit) {
            $project = $directDebit->getIdProject();
            $company = $project->getIdCompany();

            $firstName   = $company->getPrenomDirigeant();
            $clientEmail = $company->getEmailDirigeant();
            if ((empty($firstName) || empty($clientEmail))) {
                if ($company->getIdClientOwner() instanceof Clients) {
                    $firstName   = $company->getIdClientOwner()->getPrenom();
                    $clientEmail = $company->getIdClientOwner()->getEmail();
                } else {
                    $this->getContainer()->get('monolog.logger.console')
                        ->error('Could not send email "mail-echeance-emprunteur". Company manager email is empty, and cannot not find client owner', [
                            'id_company' => $company->getIdCompany(),
                            'id_project' => $project->getIdProject(),
                            'function'   => __FUNCTION__,
                            'class'      => __CLASS__,
                        ]);

                    continue;
                }
            }

            $amount = round(bcdiv($directDebit->getMontant(), 100, 4), 2);

            $keywords = [
                'firstName'                  => $firstName,
                'companyName'                => $company->getName(),
                'projectAmount'              => $numberFormatter->format($project->getAmount()),
                'nextPaymentAmount'          => $numberFormatter->format($amount),
                'nextPaymentDate'            => $directDebit->getDateEcheanceEmprunteur()->format('d/m/Y'),
                'lendersCount'               => $loanRepository->getLenderNumber($project),
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
                    ['id_mail_template' => $message->getTemplateId(), 'id_client' => $company->getIdClientOwner()->getIdClient(), 'class' => __CLASS__, 'function' => __FUNCTION__]
                );
            }
        }
    }
}
