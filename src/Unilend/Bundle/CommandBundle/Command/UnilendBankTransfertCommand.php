<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;

class UnilendBankTransfertCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('unilend:bank_transfert')
            ->setDescription('Creates virtual transaction for bank transferts');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityManagerSimulator = $this->getContainer()->get('unilend.service.entity_manager');

        /** @var \platform_account_unilend $oAccountUnilend */
        $oAccountUnilend = $entityManagerSimulator->getRepository('platform_account_unilend');
        $total           = $oAccountUnilend->getBalance();

        if ($total > 0) {
            $wireTransferOutManager = $this->getContainer()->get('unilend.service.wire_transfer_out_manager');
            $entityManager          = $this->getContainer()->get('doctrine.orm.entity_manager');
            $walletType             = $entityManager->getRepository('UnilendCoreBusinessBundle:WalletType')->findOneBy(['label' => WalletType::UNILEND]);
            $unilendWallet          = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->findOneBy(['idType' => $walletType]);
            try {
                $wireTransferOutManager->createTransfer($unilendWallet, round(bcdiv($total, 100, 4), 2), null, null, null, null, 'UNILEND_' . date('dmY'));
            } catch (\Exception $exception) {
                $this->getContainer()
                     ->get('monolog.logger.console')
                     ->error('Failed to create Unilend wire transfer out. Error: ' . $exception->getMessage());
            }
        }
    }
}
