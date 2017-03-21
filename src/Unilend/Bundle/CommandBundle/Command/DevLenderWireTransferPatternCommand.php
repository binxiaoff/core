<?php
namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;

class DevLenderWireTransferPatternCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('unilend:dev_tools:lender_wire_transfer_pattern:repair')
            ->setDescription('Repair the lender wire transfer pattern');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityManager   = $this->getContainer()->get('doctrine.orm.entity_manager');
        $walletType      = $entityManager->getRepository('UnilendCoreBusinessBundle:WalletType')->findOneBy(['label' => WalletType::LENDER]);
        $walletsToRepair = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->findBy(['idType' => $walletType, 'wireTransferPattern' => null]);

        $count = 0;
        foreach ($walletsToRepair as $wallet) {
            $wallet->setWireTransferPattern();
            $count ++;

            if (0 === $count % 100) {
                $entityManager->flush();
            }
        }
        $entityManager->flush();

        $output->writeln($count . ' wallets repaired.');
    }
}
