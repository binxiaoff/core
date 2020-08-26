<?php

declare(strict_types=1);

namespace Unilend\Command;

use Doctrine\Bundle\FixturesBundle\Command\LoadDataFixturesDoctrineCommand;
use Symfony\Component\Console\{Input\InputInterface, Output\OutputInterface};

/**
 * We need to wrap the original command to disable foreign key checks for MySQL
 */
class FixtureCommand extends LoadDataFixturesDoctrineCommand
{

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $em = $this->getDoctrine()->getManager($input->getOption('em'));
        $connection = $em->getConnection();
        $connection->exec('SET FOREIGN_KEY_CHECKS=0;');
        parent::execute($input, $output);
        $connection->exec('SET FOREIGN_KEY_CHECKS=1;');

        return 0;
    }
}
