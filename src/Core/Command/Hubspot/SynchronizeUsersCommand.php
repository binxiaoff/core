<?php

declare(strict_types=1);

namespace KLS\Core\Command\Hubspot;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use JsonException;
use KLS\Core\Service\Hubspot\HubspotContactManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class SynchronizeUsersCommand extends Command
{
    public const DEFAULT_USERS_CHANGED_LIMIT = 100;

    protected static $defaultName = 'kls:core:hubspot:user:synchronize';

    private HubspotContactManager $hubspotManager;

    public function __construct(HubspotContactManager $hubspotManager)
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

    /**
     * @throws JsonException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $usersCreated = 0;
        $usersUpdated = 0;
        $limit        = (int) $input->getOption('limit') ?: self::DEFAULT_USERS_CHANGED_LIMIT;

        do {
            $data = $this->hubspotManager->synchronizeUsers($limit);

            $usersCreated += $data['usersCreated'];
            $usersUpdated += $data['usersUpdated'];
            $usersCount = $usersCreated + $usersUpdated;

            if (0 === $data['usersUpdated'] && 0 === $data['usersCreated']) {
                break; // all users have been created on hubspot
            }
        } while ($usersCount <= $limit);

        $io->info(\sprintf('%s contact(s) have been synchronized on hubspot', $usersCreated));
        $io->info(\sprintf('%s contact(s) have been updated on hubspot', $usersUpdated));

        return Command::SUCCESS;
    }
}
