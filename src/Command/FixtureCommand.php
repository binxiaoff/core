<?php

declare(strict_types=1);

namespace Unilend\Command;

use Doctrine\Bundle\FixturesBundle\Command\LoadDataFixturesDoctrineCommand;
use Doctrine\Bundle\FixturesBundle\Loader\SymfonyFixturesLoader;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Component\Console\{Command\Command,
    Input\InputInterface,
    Input\InputOption,
    Output\OutputInterface,
    Style\SymfonyStyle};
use Unilend\DataFixtures\AppFixtures;

/**
 * Custom command to load fixtures
 *
 * This command is necessary since we need to disable foreignKey Constraints to truncate the data without restriction.
 */
class FixtureCommand extends Command
{

    private ManagerRegistry $doctrine;
    private AppFixtures $appFixtures;

    /**
     * @param ManagerRegistry $doctrine
     * @param AppFixtures     $appFixtures
     */
    public function __construct(ManagerRegistry $doctrine = null, AppFixtures $appFixtures)
    {
        parent::__construct();
        $this->doctrine = $doctrine;
        $this->appFixtures = $appFixtures;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('unilend:fixtures:load')
            ->addOption('quiet', 'q', InputOption::VALUE_NONE, 'Do not ask for permission before runing the command')
            ->setDescription('This command empty the database and fill it with fixtures data');
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $ui = new SymfonyStyle($input, $output);
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManager(null);

        // Bail out if the user doesn't confirm
        $answer = $input->getOption('quiet') ?: $ui->confirm('This operation will empty the database, are you sure ?');
        if (false === $answer) {
            return 0;
        }

        // Purge the database
        $purger = new ORMPurger($em);
        $connection = $em->getConnection();
        $connection->exec('SET FOREIGN_KEY_CHECKS=0;');
        $executor = new ORMExecutor($em, $purger);
        $executor->setLogger(static function ($message) use ($ui): void {
            $ui->text(sprintf('  <comment>></comment> <info>%s</info>', $message));
        });
        $executor->execute([$this->appFixtures], false);
        $connection->exec('SET FOREIGN_KEY_CHECKS=1;');

        return 0;
    }
}
