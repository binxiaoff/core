<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Backpayline;

class TemporaryBackPaylineRecoveryCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('backpyline:set_credit_card')
            ->setDescription('Use data serialized in `serialize` column to set the value in the new column `credit_card` on the table `backpayline');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $backPayline   = $entityManager->getRepository('UnilendCoreBusinessBundle:Backpayline');
        /** @var Backpayline[] $transactions */
        $transactions = $backPayline->findAll();

        foreach ($transactions as $transaction) {
            if (false === empty($transaction->getSerialize())) {
                $paylineResponse = unserialize($transaction->getSerialize());

                if (isset($paylineResponse['card']) && isset($paylineResponse['card']['number'])) {
                    $transaction->setCardNumber($paylineResponse['card']['number']);
                }
            }
        }
        $entityManager->flush();
    }
}