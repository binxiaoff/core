<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\{
    Input\InputInterface, Output\OutputInterface
};
use Unilend\Bundle\CoreBusinessBundle\Entity\Echeanciers;

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
        $payment       = $entityManager->getRepository('UnilendCoreBusinessBundle:EcheanciersEmprunteur');

        $debitList     = '';
        $borrowerDebit = $entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers')->findTodayBorrowerDebit();
        $scheme        = $this->getContainer()->getParameter('router.request_context.scheme');
        $host          = $this->getContainer()->getParameter('url.host_admin');
        $projectUrl    = $scheme . '://' . $host . '/dossiers/edit/';

        foreach ($borrowerDebit as $repayment) {
            $project                      = $repayment->getIdLoan()->getProject();
            $borrowerPayment              = $payment->findOneBy(['ordre' => $repayment->getOrdre(), 'idProject' => $project]);
            $borrowerEffectivePaymentDate = '00-00-0000';

            if ($borrowerPayment->getDateEcheanceEmprunteurReel() instanceof \DateTime && $borrowerPayment->getDateEcheanceEmprunteurReel()->getTimestamp() > 0) {
                $borrowerEffectivePaymentDate = $borrowerPayment->getDateEcheanceEmprunteurReel()->format('d-m-Y');
            }
            $debitList .= '
                <tr>
                    <td><a href="' . $projectUrl . $project->getIdProject() . '">' . $project->getIdProject() . '</a></td>
                    <td>' . $project->getTitle() . '</td>
                    <td>' . $repayment->getOrdre() . '</td>
                    <td style="white-space: nowrap">' . $repayment->getDateEcheance()->format('d-m-Y') . '</td>
                    <td style="white-space: nowrap">' . $borrowerPayment->getDateEcheanceEmprunteur()->format('d-m-Y') . '</td>
                    <td style="white-space: nowrap">' . $borrowerEffectivePaymentDate . '</td>
                    <td>' . ($borrowerPayment->getStatusEmprunteur() === Echeanciers::STATUS_REPAID ? 'Oui' : 'Non') . '</td>
                </tr>';
        }

        try {
            $recipient = '';
            $setting   = $entityManager->getRepository('UnilendCoreBusinessBundle:Settings')->findOneBy(['type' => 'Adresse notification check remb preteurs']);
            if ($setting) {
                $recipient = $setting->getValue();
            }
            $message = $this->getContainer()->get('unilend.swiftmailer.message_provider')
                ->newMessage('notification-prelevement-emprunteur', ['debitList' => $debitList]);
            $message->setTo(explode(';', trim($recipient)));
            $mailer = $this->getContainer()->get('mailer');
            $mailer->send($message);
        } catch (\Exception $exception) {
            $this->getContainer()->get('monolog.logger.console')->warning('Could not send email : notification-prelevement-emprunteur - Exception: ' . $exception->getMessage(), [
                'id_mail_template' => $message->getTemplateId(),
                'email_address'    => explode(';', trim($recipient)),
                'class'            => __CLASS__,
                'function'         => __FUNCTION__,
                'file'             => $exception->getFile(),
                'line'             => $exception->getLine()
            ]);
        }
    }
}
