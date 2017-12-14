<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
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
        /** @var Backpayline[] $pendingPayline */
        $pendingPayline = $entityManager->getRepository('UnilendCoreBusinessBundle:Backpayline')->findPaylineTransactionsToApprove();

        foreach ($pendingPayline as $payline) {
            try {
                $paylineManager->handlePaylineReturn($payline->getToken(), Backpayline::WS_DEFAULT_VERSION);
            } catch (\Exception $exception) {
                $logger->error(
                    'Exception while processing payline order : id_backpayline: ' . $payline->getIdBackpayline() . ' Error: ' . $exception->getMessage(),
                    ['method' => __METHOD__, 'file' => $exception->getFile(), 'line' => $exception->getLine()]
                );
            }
        }
    }
}
