<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\{Input\InputInterface, Output\OutputInterface};
use Unilend\Bundle\CoreBusinessBundle\Entity\Backpayline;

class CheckPaylineMoneyTransfersCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('check:payline')
            ->setDescription('Loops over transactions and compares them to payline feed');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger         = $this->getContainer()->get('monolog.logger.console');
        $entityManager  = $this->getContainer()->get('doctrine.orm.entity_manager');
        $paylineManager = $this->getContainer()->get('unilend.service.payline_manager');
        /** @var Backpayline[] $pendingTransactions */
        $pendingTransactions = $entityManager->getRepository('UnilendCoreBusinessBundle:Backpayline')->findTransactionsToApprove();

        foreach ($pendingTransactions as $payline) {
            try {
                $paylineManager->handleResponse($payline->getToken(), Backpayline::WS_DEFAULT_VERSION);
            } catch (\Exception $exception) {
                $logger->error('Exception while processing Payline order: ' . $payline->getIdBackpayline() . '. Error: ' . $exception->getMessage(), [
                    'id_backpayline' => $payline->getIdBackpayline(),
                    'class'          => __CLASS__,
                    'function'       => __FUNCTION__,
                    'file'           => $exception->getFile(),
                    'line'           => $exception->getLine()
                ]);
            }
        }
    }
}
