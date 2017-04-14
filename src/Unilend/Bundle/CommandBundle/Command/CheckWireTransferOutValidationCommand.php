<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Virements;

class CheckWireTransferOutValidationCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('unilend:borrower:wire_transfer_out_validation:check')
            ->setDescription('Find all wire transfer out to a third party in pending (not validated by borrower) since at least 2 days and notify the borrower.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $wireTransferOutManager  = $this->getContainer()->get('unilend.service.wire_transfer_out_manager');
        $pendingWireTransferOuts = $this->getContainer()
                                        ->get('doctrine.orm.entity_manager')
                                        ->getRepository('UnilendCoreBusinessBundle:Virements')
                                        ->findWireTransferBefore(Virements::STATUS_PENDING, new \DateTime('2 days ago'));
        foreach ($pendingWireTransferOuts as $wireTransferOut) {
            $wireTransferOutManager->sendWireTransferOutNotificationToBorrower($wireTransferOut);
        }
    }
}
