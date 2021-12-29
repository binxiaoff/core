<?php

declare(strict_types=1);

namespace KLS\Core\Command\Hubspot;

use JsonException;
use KLS\Core\Service\Hubspot\HubspotContactManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class GetDailyUsageApiCommand extends Command
{
    protected static $defaultName = 'kls:core:hubspot:api-usage:show';

    private HubspotContactManager $hubspotManager;

    public function __construct(HubspotContactManager $hubspotManager)
    {
        parent::__construct();

        $this->hubspotManager = $hubspotManager;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws JsonException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io         = new SymfonyStyle($input, $output);
        $dailyUsage = $this->hubspotManager->getDailyApiUsage();

        if (!$dailyUsage) {
            $io->error('There is an error to get data');

            return Command::FAILURE;
        }

        $table = new Table($output);
        $table->setStyle('box-double');
        $table->setHeaders(['Usage Limit', 'Current Usage']);
        $table->setRows([
            [$dailyUsage[0]['usageLimit'], $dailyUsage[0]['currentUsage']],
        ]);
        $table->render();

        return Command::SUCCESS;
    }
}
