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

            if ($this->isEndOfQuarter()) {
                $statisticsManager->saveQuarterIncidenceRate();
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

    /**
     * @return bool
     */
    private function isEndOfQuarter(): bool
    {
        $today      = new \DateTime('NOW');
        $quarterEnd = $this->getQuarterFromDate($today);
        $today->setTime(0, 0, 0);
        $quarterEnd->setTime(0, 0, 0);

        return $today === $quarterEnd;
    }

    /**
     * @param \DateTime $date
     *
     * @return \DateTime
     */
    private function getQuarterFromDate(\DateTime $date): \DateTime
    {
        switch ($date->format('n')) {
            case 1:
            case 2:
            case 3:
                $quarter = new \DateTime('Last day of March ' . $date->format('Y'));
                break;
            case 4:
            case 5:
            case 6:
                $quarter = new \DateTime('Last day of June ' . $date->format('Y'));
                break;
            case 7:
            case 8:
            case 9:
                $quarter = new \DateTime('Last day of September ' . $date->format('Y'));
                break;
            default:
                $quarter = new \DateTime('Last day of ' . $date->format('Y'));
                break;
        }

        return $quarter;
    }
}
