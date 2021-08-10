<?php

declare(strict_types=1);

namespace KLS\Core\Command\Hubspot;

use KLS\Core\Service\Hubspot\HubspotCompanyManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SynchronizeCompanyMappingCommand extends Command
{
    private const DEFAULT_COMPANIES_LIMIT = 100;

    protected static $defaultName = 'kls:hubspot:synchronize-companies';

    private HubspotCompanyManager $hubspotCompanyManager;

    public function __construct(HubspotCompanyManager $hubspotCompanyManager)
    {
        parent::__construct();

        $this->hubspotCompanyManager = $hubspotCompanyManager;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Synchronize companies from our database and the hubspot database')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'How many companies we want to synchronize')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $companyAdded  = 0;
        $limit         = $input->getOption('limit') ?: self::DEFAULT_COMPANIES_LIMIT;
        $lastCompanyId = 0;

        do {
            $data = $this->hubspotCompanyManager->synchronizeCompanies($lastCompanyId);
            $companyAdded += $data['companyAddedNb'];
            $lastCompanyId = $data['lastCompanyId'];
        } while ($companyAdded <= $limit && $lastCompanyId);

        $output->writeln(\sprintf('%s companies has been linked to our existing companies', $companyAdded));

        return self::SUCCESS;
    }
}
