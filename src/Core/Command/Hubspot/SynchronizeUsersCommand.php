<?php

declare(strict_types=1);

namespace KLS\Core\Command\Hubspot;

use KLS\Core\Service\Hubspot\HubspotManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SynchronizeUsersCommand extends Command
{
    public const DEFAULT_USERS_CHANGED_LIMIT = 100;

    protected static $defaultName = 'kls:core:hubspot:user:synchronize';

    private HubspotManager $hubspotManager;

    public function __construct(HubspotManager $hubspotManager)
    {
        parent::__construct();

        $this->hubspotManager = $hubspotManager;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Synchronize users from our database with Hubspot contacts')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'How many users we want to synchronize')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $usersCount   = 0;
        $usersCreated = 0;
        $usersUpdated = 0;
        $limit        = $input->getOption('limit') ?: self::DEFAULT_USERS_CHANGED_LIMIT;

        do {
            $data = $this->hubspotManager->synchronizeUsers((int) $limit);

            $usersCreated += $data['usersCreated'];
            $usersUpdated += $data['usersUpdated'];
            $usersCount   += $usersCreated + $usersUpdated;

            if (0 === $data['usersUpdated'] || 0 === $data['usersCreated']) {
                break; // all users have been created on hubspot
            }
        } while ($usersCount <= $limit);

        $io->info(\sprintf('%s contact(s) have been synchronized on hubspot', $usersCreated));
        $io->info(\sprintf('%s contact(s) have been updated on hubspot', $usersUpdated));

        return Command::SUCCESS;
    }
}
