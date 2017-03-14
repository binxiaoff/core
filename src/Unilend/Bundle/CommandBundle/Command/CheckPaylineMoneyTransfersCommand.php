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
        $em             = $this->getContainer()->get('doctrine.orm.entity_manager');
        $paylineManager = $this->getContainer()->get('unilend.service.payline_manager');

        /** @var Backpayline[] $pendingPayline */
        $pendingPayline = $em->getRepository('UnilendCoreBusinessBundle:Backpayline')->findBy(['code' => null]);

        if ($pendingPayline) {
            foreach ($pendingPayline as $payline) {
                if (false === empty($payline->getSerializeDoPayment())) {
                    $paymentDetails = unserialize($payline->getSerializeDoPayment());
                    $token          = $paymentDetails['token'];
                    $paylineManager->handlePaylineReturn($token, Backpayline::WS_DEFAULT_VERSION);
                }
            }
        }
    }
}
