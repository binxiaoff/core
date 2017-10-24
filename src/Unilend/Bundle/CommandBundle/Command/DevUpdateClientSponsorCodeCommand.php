<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;

class DevUpdateClientSponsorCodeCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('unilend:dev_tools:client_sponsor_code:repair')
            ->setDescription('Update de sponsor code')
            ->addArgument('quantity', InputArgument::REQUIRED, 'For how many clients per iteration du you want to repair the sponsorCode ?');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $limit         = $amountOfLenderAccounts = (int) $input->getArgument('quantity');;
        $count         = 0;

        $clients = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->findClientsWithSponsorCodeToRepair($limit);

        /** @var Clients $client */
        foreach ($clients as $client) {
            $client->setSponsorCodeValue();
            $count ++;

            if (0 === $count % 50) {
                $entityManager->flush();
            }
        }
        $entityManager->flush();
        $output->writeln($count . ' sponsor codes repaired.');
    }
}
