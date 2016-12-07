<?php

namespace Unilend\Bundle\CommandBundle\Command;


use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;

class CheckBalanceCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('check:balance')
            ->setDescription('Check if the historic balance matches the wallet_balance');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \transactions $transaction */
        $transaction      = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('transactions');
        $walletRepository = $this->getContainer()->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Wallet');
        $walletManager    = $this->getContainer()->get('unilend.service.wallet_manager');

        $dateLastCheck      = new \DateTime('NOW -1 DAY');
        $clientsToBeChecked = $transaction->getLenderWithTransactionsSinceDate($dateLastCheck);

        /** @var LoggerInterface $logger */
        $logger = $this->getContainer()->get('monolog.logger.console');

        foreach ($clientsToBeChecked as $client) {
            $wallet           = $walletRepository->findOneBy(['idClient' => $client['id_client'], 'idType' => 1]); //TODO use constant
            $balanceOldMethod = $transaction->getSolde($client['id_client']);
            $walletBalance    = $walletManager->getBalance($wallet);

            if (0 < abs(bccomp($balanceOldMethod, $walletBalance, 2))) {
                $logger->error('Balance does not match for client : ' . $client['id_client']);
            }
        }
    }
}
