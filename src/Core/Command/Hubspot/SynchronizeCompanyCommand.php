<?php

declare(strict_types=1);

namespace KLS\Core\Command\Hubspot;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use JsonException;
use KLS\Core\Service\Hubspot\HubspotCompanyManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class SynchronizeCompanyCommand extends Command
{
    public const DEFAULT_COMPANIES_LIMIT = 100;

    protected static $defaultName = 'kls:core:hubspot:company:synchronize';

    private HubspotCompanyManager $hubspotCompanyManager;

    public function __construct(HubspotCompanyManager $hubspotCompanyManager)
    {
        parent::__construct();

        $this->hubspotCompanyManager = $hubspotCompanyManager;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Synchronize companies from our database to hubspot ')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'How many companies we want to synchronize')
        ;
    }

    /**
     * @throws ClientExceptionInterface
     * @throws JsonException
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $companiesCreated = 0;
        $companiesUpdated = 0;
        $limit            = (int) $input->getOption('limit') ?: self::DEFAULT_COMPANIES_LIMIT;

        do {
            $data = $this->hubspotCompanyManager->exportCompaniesToHubspot($limit);

            $companiesCreated += $data['companiesCreated'];
            $companiesUpdated += $data['companiesUpdated'];
            $companiesCount = $companiesCreated + $companiesUpdated;

            if (0 === $data['companiesUpdated'] && 0 === $data['companiesCreated']) {
                break; // all companies have been created on hubspot
            }
        } while ($companiesCount <= $limit);

        $io->info(\sprintf('%s company have been created on hubspot', $companiesCreated));
        $io->info(\sprintf('%s company have been updated on hubspot', $companiesUpdated));

        return Command::SUCCESS;
    }
}
