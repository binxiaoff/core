<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\UnilendStats;

class DevUnilendIncidenceRateCommand extends ContainerAwareCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('unilend:dev_tools:statistics:incidence_rate')
            ->setDescription('Separate incidence rate from front statistics');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityManager     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $unilendStatistics = $entityManager->getRepository('UnilendCoreBusinessBundle:UnilendStats')->findBy(['typeStat' => UnilendStats::TYPE_STAT_FRONT_STATISTIC]);

        /** @var UnilendStats $statistic */
        foreach ($unilendStatistics as $statistic) {
            $this->separateIncidenceRateFromUnilendFrontStatistic($statistic);
        }
    }

    /**
     * @param UnilendStats $frontStatistic
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function separateIncidenceRateFromUnilendFrontStatistic(UnilendStats $frontStatistic)
    {
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
        $content       = json_decode($frontStatistic->getValue(), true);
        $incidenceRate = $content['incidenceRate'];
        unset ($content['incidenceRate']);

        $frontStatistic->setValue(json_encode($content));
        $entityManager->flush($frontStatistic);

        $incidenceRateStat = new UnilendStats();
        $incidenceRateStat
            ->setTypeStat(UnilendStats::TYPE_INCIDENCE_RATE)
            ->setValue(json_encode($incidenceRate))
            ->setAdded($frontStatistic->getAdded());

        $entityManager->persist($incidenceRateStat);
        $entityManager->flush($incidenceRateStat);
    }
}
