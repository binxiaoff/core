<?php

declare(strict_types=1);

namespace Unilend\Core\Command\Hubspot;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Unilend\Core\Service\Hubspot\HubspotManager;

class SynchronizeMappingCommand extends Command
{
    protected static $defaultName = 'kls:hubspot:synchronize-contact-mapping';

    private HubspotManager $hubspotManager;
    private LoggerInterface $logger;

    public function __construct(HubspotManager $hubspotManager, LoggerInterface $logger)
    {
        parent::__construct();
        $this->hubspotManager = $hubspotManager;
        $this->logger         = $logger;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Synchronize users from our database and the hubspot database')
            ->setHelp('kls:hubspot:synchronize-mapping')
        ;
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $response = $this->hubspotManager->fetchContacts();

        if (!$response['results']) {
            $this->logger->info('No contacts found, try to add users from our database');

            return self::FAILURE;
        }

        $this->hubspotManager->handleContacts($response);

        $io->info('All users has been successfully eported to hubspot');

        return self::SUCCESS;
    }
}
