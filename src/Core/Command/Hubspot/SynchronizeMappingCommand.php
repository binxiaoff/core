<?php

declare(strict_types=1);

namespace Unilend\Core\Command\Hubspot;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use JsonException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Unilend\Core\Service\Hubspot\HubspotManager;

class SynchronizeMappingCommand extends Command
{
    private const DEFAULT_CONTACTS_LIMIT = 100;
    protected static $defaultName        = 'kls:hubspot:synchronize-contact';

    private HubspotManager $hubspotManager;

    public function __construct(HubspotManager $hubspotManager)
    {
        parent::__construct();
        $this->hubspotManager = $hubspotManager;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Synchronize users from our database and the hubspot database')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'How many users we want to synchronize')
        ;
    }

    /**
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws JsonException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $contactAdded  = 0;
        $limit         = $input->getOption('limit') ?: self::DEFAULT_CONTACTS_LIMIT;
        $lastContactId = null;

        do {
            $data = $this->hubspotManager->synchronizeContacts((int) $lastContactId);
            $contactAdded += $data['contactAddedNb'];
            $lastContactId = $data['lastContactId'];
        } while ($contactAdded <= $limit && $lastContactId);

        $output->writeln(\sprintf('%s contacts has been linked to our existing users', $contactAdded));

        return self::SUCCESS;
    }
}
