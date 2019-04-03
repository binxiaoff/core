<?php

namespace Unilend\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Entity\EcheanciersEmprunteur;

class BorrowerInvoiceCreationCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('unilend:borrower:repayment_invoice:create')
            ->setDescription('Create the invoice line in database when the payment schedule date is arrived.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $invoiceManager   = $this->getContainer()->get('unilend.service.invoice_manager');
        $paymentSchedules = $this->getContainer()->get('doctrine.orm.entity_manager')->getRepository(EcheanciersEmprunteur::class)->findPaymentSchedulesToInvoice(50);

        foreach ($paymentSchedules as $paymentSchedule) {
            try {
                $invoiceManager->createPaymentScheduleInvoice($paymentSchedule);
            } catch (\Exception $exception) {
                $this->getContainer()->get('monolog.logger.console')
                    ->error('Errors occur during repayment invoice creation command. Error message : ' . $exception->getMessage(), ['file' => $exception->getFile(), 'line' => $exception->getLine()]);
                continue;
            }
        }
    }
}
