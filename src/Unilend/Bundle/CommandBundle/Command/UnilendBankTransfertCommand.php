<?php
namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Unilend\Bundle\CoreBusinessBundle\Entity\Virements;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

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
        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('unilend.service.entity_manager');

        /** @var \platform_account_unilend $oAccountUnilend */
        $oAccountUnilend = $entityManager->getRepository('platform_account_unilend');
        $total           = $oAccountUnilend->getBalance();

        if ($total > 0) {
            $wireTransferOut = new Virements();
            $wireTransferOut->setMontant($total);
            $wireTransferOut->setMotif('UNILEND_' . date('dmY'));
            $wireTransferOut->setType(Virements::TYPE_UNILEND);
            $wireTransferOut->setStatus(Virements::STATUS_PENDING);
            $this->getContainer()->get('doctrine.orm.entity_manager')->persist($wireTransferOut);

            $this->getContainer()->get('unilend.service.operation_manager')->withdrawUnilendWallet($wireTransferOut);
        }
    }
}
