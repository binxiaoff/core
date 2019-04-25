<?php

namespace Unilend\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Entity\{Clients, Loans, Prelevements, Settings};

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
        $entityManager   = $this->getContainer()->get('doctrine.orm.entity_manager');
        $numberFormatter = $this->getContainer()->get('number_formatter');

        $loanRepository     = $entityManager->getRepository(Loans::class);
        $settingsRepository = $entityManager->getRepository(Settings::class);

        /** @var Prelevements[] $upcomingDirectDebits */
        $upcomingDirectDebits = $entityManager->getRepository(Prelevements::class)->getUpcomingDirectDebits(7);

        $borrowerServicePhoneNumber = $settingsRepository->findOneBy(['type' => 'Téléphone emprunteur'])->getValue();
        $borrowerServiceEmail       = $settingsRepository->findOneBy(['type' => 'Adresse emprunteur'])->getValue();

        foreach ($upcomingDirectDebits as $directDebit) {
            $project = $directDebit->getIdProject();
            $company = $project->getIdCompany();

            $firstName   = $company->getPrenomDirigeant();
            $clientEmail = $company->getEmailDirigeant();
            if ((empty($firstName) || empty($clientEmail))) {
                if ($company->getIdClientOwner() instanceof Clients && false === empty($company->getIdClientOwner()->getEmail())) {
                    $firstName   = $company->getIdClientOwner()->getFirstName();
                    $clientEmail = $company->getIdClientOwner()->getEmail();
                } else {
                    $this->getContainer()->get('monolog.logger.console')
                        ->error('Could not send email "mail-echeance-emprunteur". Company manager email or first name is empty, and no client owner email found', [
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

            /** @var \Unilend\SwiftMailer\TemplateMessage $message */
            $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')->newMessage('mail-echeance-emprunteur', $keywords);

            try {
                $message->setTo($clientEmail);
                $mailer = $this->getContainer()->get('mailer');
                $mailer->send($message);
            } catch (\Exception $exception) {
                $this->getContainer()->get('monolog.logger.console')->warning('Could not send email: mail-echeance-emprunteur - Exception: ' . $exception->getMessage(), [
                    'id_mail_template' => $message->getTemplateId(),
                    'id_client'        => $company->getIdClientOwner()->getIdClient(),
                    'class'            => __CLASS__,
                    'function'         => __FUNCTION__,
                    'file'             => $exception->getFile(),
                    'line'             => $exception->getLine()
                ]);
            }
        }
    }
}
