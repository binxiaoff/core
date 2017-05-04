<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Unilend\Bundle\CoreBusinessBundle\Entity\Virements;
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
        $entityManager     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $unilendWalletType = $entityManager->getRepository('UnilendCoreBusinessBundle:WalletType')->findOneBy(['label' => WalletType::UNILEND]);
        $unilendWallet     = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->findOneBy(['idType' => $unilendWalletType]);
        $total             = $unilendWallet->getAvailableBalance();

        if ($total > 0) {
            $wireTransferOut = new Virements();
            $wireTransferOut->setMontant(bcmul($total, 100));
            $wireTransferOut->setMotif('UNILEND_' . date('dmY'));
            $wireTransferOut->setType(Virements::TYPE_UNILEND);
            $wireTransferOut->setStatus(Virements::STATUS_PENDING);
            $entityManager->persist($wireTransferOut);

            $this->getContainer()->get('unilend.service.operation_manager')->withdrawUnilendWallet($wireTransferOut);
        }
    }
}
