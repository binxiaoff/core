<?php

declare(strict_types=1);

namespace KLS\Core\Command\Hubspot;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use KLS\Core\Service\Hubspot\HubspotCompanyManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class ImportCompanyCommand extends Command
{
    // numbers of companies we want to import from hubspot
    private const DEFAULT_COMPANY_IMPORT_LIMIT = 100;

    protected static $defaultName = 'kls:core:hubspot:company:import';

    private HubspotCompanyManager $hubspotCompanyManager;

    public function __construct(HubspotCompanyManager $hubspotCompanyManager)
    {
        parent::__construct();

        $this->hubspotCompanyManager = $hubspotCompanyManager;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Import companies from hubspot to our database')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'How many companies we want to import')
        ;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws \JsonException
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $companyAdded  = 0;
        $limit         = $input->getOption('limit') ?: self::DEFAULT_COMPANY_IMPORT_LIMIT;
        $lastCompanyId = 0;

        do {
            $data = $this->hubspotCompanyManager->importCompaniesFromHubspot($lastCompanyId);
            $companyAdded += $data['companyAddedNb'];
            $lastCompanyId = $data['lastCompanyId'];
        } while ($companyAdded <= $limit && $lastCompanyId);

        $output->writeln(\sprintf('%s companies has been linked to our existing companies', $companyAdded));

        return self::SUCCESS;
    }
}
