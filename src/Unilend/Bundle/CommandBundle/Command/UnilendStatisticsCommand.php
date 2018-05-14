<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UnilendStatisticsCommand extends ContainerAwareCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('unilend:statistics')
            ->setDescription('Calculate all statistics and save them in DB');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     * @throws \Psr\Cache\InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $statisticsManager = $this->getContainer()->get('unilend.service.statistics_manager');

        try {
            $statisticsManager->saveFrontStatistics();
        } catch (\Exception $exception) {
            $this->logException('front statistics', $exception);
        }

        try {
            $statisticsManager->savePerformanceIndicators();
        } catch (\Exception $exception) {
            $this->logException('performance indicators', $exception);
        }

        try {
            $statisticsManager->saveIncidenceRate();

            if ($this->isEndOfQuarter()) {
                $statisticsManager->saveQuarterIncidenceRate();
            }
        } catch (\Exception $exception) {
            $this->logException('incidence rate (acpr)', $exception);
        }
    }

    /**
     * @return bool
     */
    private function isEndOfQuarter(): bool
    {
        $today              = new \DateTime('NOW');
        $startOfQuarterDays = [
            '01-04',
            '01-07',
            '01-10',
            '11-01'
        ];

        return in_array($today->format('d-m'), $startOfQuarterDays);
    }


    private function logException(string $statistic, \Exception $exception)
    {
        $this->getContainer()->get('logger')->error('Could not calculate ' . $statistic . '. Exception: ' . $exception->getMessage(), [
            'file'     => $exception->getFile(),
            'line'     => $exception->getLine(),
            'class'    => __CLASS__,
            'function' => __FUNCTION__
        ]);
    }
}
