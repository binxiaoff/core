<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DevMongoDBTestCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('unilend:dev_tools:mongodb:test')
            ->setDescription('Mongodb connection test');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $documentManager = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $connection      = $this->getContainer()->get('doctrine_mongodb')->getConnection();

        $documentManager->getRepository('UnilendStoreBundle:WsCall')->findOneBy(['provider' => 'altares']);
        $output->writeln('MongoDB data retrieved');
        $connection->close();
        $output->writeln('MongoDB connection closed');
    }
}
