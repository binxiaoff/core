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
        try {
            $statisticsManager = $this->getContainer()->get('unilend.service.statistics_manager');

            $statisticsManager->saveFrontStatistics();
            $statisticsManager->savePerformanceIndicators();
            $statisticsManager->saveIncidenceRate();

            if ($this->isEndOfTrimester()) {
                $statisticsManager->saveTrimesterIncidenceRate();
            }

        } catch (\Exception $exception) {
            $this->getContainer()->get('logger')->error('Could not calculate unilend statistics. Exception: ' . $exception->getMessage(), [
                'exceptionFile' => $exception->getFile(),
                'exceptionLine' => $exception->getLine(),
                'class'         => __CLASS__,
                'function'      => __METHOD__
            ]);
        }
    }


    private function isEndOfTrimester()
    {
        $statisticsManager = $this->getContainer()->get('unilend.service.statistics_manager');
        $today             = new \DateTime('NOW');
        $trimesterEnd      = $statisticsManager->getTrimesterFromDate($today);
        $today->setTime(0, 0, 0);
        $trimesterEnd->setTime(0, 0, 0);

        return $today === $trimesterEnd;
    }
}
