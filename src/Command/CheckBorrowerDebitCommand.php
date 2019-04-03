<?php

namespace Unilend\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\{Input\InputInterface, Output\OutputInterface};
use Unilend\Entity\Echeanciers;
use Unilend\Entity\EcheanciersEmprunteur;
use Unilend\Entity\Settings;

class CheckBorrowerDebitCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('check:borrower_debit')
            ->setDescription('Checks if the borrower has paid at the date of payment');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $payment       = $entityManager->getRepository(EcheanciersEmprunteur::class);

        $debitList  = '';
        $schedules  = $entityManager->getRepository(Echeanciers::class)->findScheduledToday();
        $scheme     = $this->getContainer()->getParameter('router.request_context.scheme');
        $host       = $this->getContainer()->getParameter('url.host_admin');
        $projectUrl = $scheme . '://' . $host . '/dossiers/edit/';

        foreach ($schedules as $repayment) {
            if ($repayment->getIdLoan() && $repayment->getIdLoan()->getProject()) {
                $project                      = $repayment->getIdLoan()->getProject();
                $borrowerPayment              = $payment->findOneBy(['ordre' => $repayment->getOrdre(), 'idProject' => $project]);
                $borrowerEffectivePaymentDate = 'N/A';

                if ($borrowerPayment->getDateEcheanceEmprunteurReel() instanceof \DateTime && $borrowerPayment->getDateEcheanceEmprunteurReel()->getTimestamp() > 0) {
                    $borrowerEffectivePaymentDate = $borrowerPayment->getDateEcheanceEmprunteurReel()->format('d/m/Y');
                }
                $debitList .= '
                <tr>
                    <td><a href="' . $projectUrl . $project->getIdProject() . '">' . $project->getIdProject() . '</a></td>
                    <td><a href="' . $projectUrl . $project->getIdProject() . '">' . $project->getTitle() . '</a></td>
                    <td>' . $repayment->getOrdre() . '</td>
                    <td style="white-space: nowrap">' . $repayment->getDateEcheance()->format('d/m/Y') . '</td>
                    <td style="white-space: nowrap">' . $borrowerPayment->getDateEcheanceEmprunteur()->format('d/m/Y') . '</td>
                    <td style="white-space: nowrap">' . $borrowerEffectivePaymentDate . '</td>
                    <td>' . ($borrowerPayment->getStatusEmprunteur() === Echeanciers::STATUS_REPAID ? 'Oui' : 'Non') . '</td>
                </tr>';
            }
        }

        try {
            $emailType   = 'notification-prelevement-emprunteur';
            $settingType = 'Adresse notification check remb preteurs';
            $setting     = $entityManager->getRepository(Settings::class)->findOneBy(['type' => $settingType]);

            if ($setting && false === empty($setting->getValue())) {
                $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')
                    ->newMessage($emailType, ['debitList' => $debitList]);
                $message->setTo(explode(';', trim($setting->getValue())));
                $mailer = $this->getContainer()->get('mailer');
                $mailer->send($message);
            } else {
                $this->getContainer()->get('monolog.logger.console')->error('The recipient list for the email: "' . $emailType . '" is empty. Check if the setting type: "' . $settingType . '" exists', [
                    'class'    => __CLASS__,
                    'function' => __FUNCTION__
                ]);
            }
        } catch (\Exception $exception) {
            $this->getContainer()->get('monolog.logger.console')->warning('Could not send email: "' . $emailType . '" - Exception: ' . $exception->getMessage(), [
                'email_address' => isset($setting) ? $setting->getValue() : '',
                'class'         => __CLASS__,
                'function'      => __FUNCTION__,
                'file'          => $exception->getFile(),
                'line'          => $exception->getLine()
            ]);
        }
    }
}
