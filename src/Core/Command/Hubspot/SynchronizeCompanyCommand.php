<?php

declare(strict_types=1);

namespace KLS\Core\Command\Hubspot;

use KLS\Core\Service\Hubspot\HubspotCompanyManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

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
            ->addOption('companyId', null, InputOption::VALUE_OPTIONAL, 'Company Id you want to start (ordered by id asc)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $companiesCount   = 0;
        $companiesCreated = 0;
        $companiesUpdated = 0;
        $limit            = $input->getOption('limit') ?: self::DEFAULT_COMPANIES_LIMIT;

        do {
            $data = $this->hubspotCompanyManager->exportCompaniesToHubspot((int) $limit);

            $companiesCreated += $data['companiesCreated'];
            $companiesUpdated += $data['companiesUpdated'];
            $companiesCount   += $companiesCreated + $companiesUpdated;

            if (0 === $data['companiesUpdated'] || 0 === $data['companiesCreated']) {
                break; // all companies have been created on hubspot
            }
        } while ($companiesCount <= $limit);

        $io->info(\sprintf('%s company have been created on hubspot', $companiesCreated));
        $io->info(\sprintf('%s company have been updated on hubspot', $companiesUpdated));

        return Command::SUCCESS;
    }
}
