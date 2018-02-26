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
        $today             = new \DateTime('2017-12-31');
        $trimesterEnd      = $this->getTrimesterFromDate($today);
        $today->setTime(0, 0, 0);
        $trimesterEnd->setTime(0, 0, 0);

        return $today === $trimesterEnd;
    }

    /**
     * @param \DateTime $date
     *
     * @return \DateTime
     */
    private function getTrimesterFromDate(\DateTime $date): \DateTime
    {
        switch ($date->format('n')) {
            case 1:
            case 2:
            case 3:
                $trimester = new \DateTime('Last day of March ' . $date->format('Y'));
                break;
            case 4:
            case 5:
            case 6:
                $trimester = new \DateTime('Last day of June ' . $date->format('Y'));
                break;
            case 7:
            case 8:
            case 9:
                $trimester = new \DateTime('Last day of September ' . $date->format('Y'));
                break;
            default:
                $trimester = new \DateTime('Last day of ' . $date->format('Y'));
                break;
        }

        return $trimester;
    }
}
