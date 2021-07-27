<?php

declare(strict_types=1);

namespace Unilend\Core\Command\Hubspot;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Unilend\Core\Service\Hubspot\HubspotManager;

class GetDailyUsageApiCommand extends Command
{
    protected static $defaultName = 'kls:hubspot:get-api-usage';

    private HubspotManager $hubspotManager;

    public function __construct(HubspotManager $hubspotManager)
    {
        parent::__construct();

        $this->hubspotManager = $hubspotManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io         = new SymfonyStyle($input, $output);
        $dailyUsage = $this->hubspotManager->getDailyApiUsage();

        if (!$dailyUsage) {
            $io->error('There is an error to get data');

            return self::FAILURE;
        }

        $table = new Table($output);
        $table->setStyle('box-double');
        $table->setHeaders(['Usage Limit', 'Current Usage']);
        $table->setRows([
            [$dailyUsage[0]['usageLimit'], $dailyUsage[0]['currentUsage']],
        ]);
        $table->render();

        return self::SUCCESS;
    }
}
