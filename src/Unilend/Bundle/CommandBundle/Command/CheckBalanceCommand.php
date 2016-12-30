<?php

namespace Unilend\Bundle\CommandBundle\Command;


use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bridge\Doctrine\DBAL\Connection;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Repository\WalletRepository;

class CheckBalanceCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('unilend:dev_tools:balance:check')
            ->setDescription('Check if the historic balance matches the wallet_balance and no balance is negative');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var LoggerInterface $logger */
        $logger = $this->getContainer()->get('monolog.logger.console');
        $this->checkPositiveBalance($logger);
        $this->checkLenderBalance($logger);
    }

    private function checkLenderBalance(LoggerInterface $logger)
    {
        /** @var \transactions $transaction */
        $transaction          = $this->getContainer()->get('unilend.service.entity_manager')->getRepository('transactions');
        $walletRepository     = $this->getContainer()->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Wallet');
        $walletTypeRepository = $this->getContainer()->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:WalletType');

        $dateLastCheck      = new \DateTime('NOW -1 DAY');
        $clientsToBeChecked = $transaction->getLenderWithTransactionsSinceDate($dateLastCheck);

        /** @var WalletType $walletType */
        $walletType = $walletTypeRepository->findOneByLabel(WalletType::LENDER);

        foreach ($clientsToBeChecked as $client) {
            /** @var Wallet $wallet */
            $wallet  = $walletRepository->findOneBy(['idClient' => $client['id_client'], 'idType' => $walletType->getId()]);

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

    private function checkPositiveBalance(LoggerInterface $logger)
    {
        /** @var Connection $dataBaseConnection */
        $dataBaseConnection = $this->getContainer()->get('database_connection');
        $query = 'SELECT * FROM wallet WHERE available_balance < 0 OR committed_balance < 0';
        $negativeWallets = $dataBaseConnection->executeQuery($query)->fetchAll(\PDO::FETCH_ASSOC);

        if (false === empty($negativeWallets)) {
            foreach ($negativeWallets as $wallet) {
                $logger->error('Walletbalance is negative for client : ' . $wallet['id_client']);
            }
        }
    }
}
