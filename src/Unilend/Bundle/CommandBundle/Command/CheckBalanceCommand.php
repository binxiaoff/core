<?php

namespace Unilend\Bundle\CommandBundle\Command;


use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;

class CheckBalanceCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('unilend:dev_tools:balance:check')
            ->setDescription('Check if the historic balance matches the wallet_balance');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \transactions $transaction */
        $transaction          = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('transactions');
        $walletRepository     = $this->getContainer()->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Wallet');
        $walletTypeRepository = $this->getContainer()->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:WalletType');

        $dateLastCheck      = new \DateTime('NOW -1 DAY');
        $clientsToBeChecked = $transaction->getLenderWithTransactionsSinceDate($dateLastCheck);

        /** @var WalletType $walletType */
        $walletType = $walletTypeRepository->findOneByLabel(WalletType::LENDER);

        /** @var LoggerInterface $logger */
        $logger = $this->getContainer()->get('monolog.logger.console');

        foreach ($clientsToBeChecked as $client) {
            /** @var Wallet $wallet */
            $wallet           = $walletRepository->findOneBy(['idClient' => $client['id_client'], 'idType' => $walletType->getId()]);

            if (is_null($wallet)) {
                $logger->error('Client has no wallet : ' . $client['id_client']);
                continue;
            }

            $balanceOldMethod = $transaction->getSolde($client['id_client']);
            $walletBalance    = $wallet->getAvailableBalance();
            if (0 !== (bccomp($balanceOldMethod, $walletBalance, 2))) {
                $logger->error('Balance does not match for client : ' . $client['id_client']);
            }
        }
    }
}
