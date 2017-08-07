<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Wallet;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;

class DebtCollectorTransferCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('debt_collector:bank_transfer')
            ->setDescription('Creates virtual transaction for Debt Collector bank transfer with his whole available balance amount');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger                  = $this->getContainer()->get('monolog.logger.console');
        $entityManager           = $this->getContainer()->get('doctrine.orm.entity_manager');
        $wireTransferOutManager  = $this->getContainer()->get('unilend.service.wire_transfer_out_manager');
        $bankAccountRepository   = $entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount');
        $debtCollectorWalletType = $entityManager->getRepository('UnilendCoreBusinessBundle:WalletType')->findOneBy(['label' => WalletType::DEBT_COLLECTOR]);
        /** @var Wallet[] $debtCollectorWallets */
        $debtCollectorWallets = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->findBy(['idType' => $debtCollectorWalletType]);

        foreach ($debtCollectorWallets as $collectorWallet) {
            $total       = $collectorWallet->getAvailableBalance();
            $bankAccount = $bankAccountRepository->getClientValidatedBankAccount($collectorWallet->getIdClient());

            if ($total > 0 && null !== $bankAccount) {
                try {
                    $wireTransferOutManager->createTransfer($collectorWallet, $total, $bankAccount);
                } catch (\Exception $exception) {
                    $logger->error(
                        'Failed to create debt collector wire transfer out for wallet: ' . $collectorWallet->getId() . '. Error: ' . $exception->getMessage(),
                        ['method' => __METHOD__, 'id_client' => $collectorWallet->getIdClient()->getIdClient(), 'id_wallet' => $collectorWallet->getId()]
                    );
                }
            }
        }
    }
}