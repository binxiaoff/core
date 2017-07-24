<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\DetailedDailyStateBalanceHistory;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;

class DevAddDetailedDailyStateBalanceHistoryCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('dev:add_detailed_daily_balance_history')
            ->setDescription('fills the detailed daily state balance history');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

        $end                 = new \DateTime('NOW');
        $firstDayOfThisYear  = new \DateTime('First day of January 2017');
        $firstDayOfThisMonth = new \DateTime('First day of ' . $end->format('F Y'));
        $monthInterval       = \DateInterval::createFromDateString('1 month');
        $dayInterval         = \DateInterval::createFromDateString('1 day');
        $days                = new \DatePeriod($firstDayOfThisMonth, $dayInterval, $end);

        /** @var \DateTime $date */
        foreach ($days as $date) {
            if (null === $entityManager->getRepository('UnilendCoreBusinessBundle:DetailedDailyStateBalanceHistory')->findOneBy(['date' => $date->format('Y-m-d')])) {
                $this->saveBalance($date);
            }
        }

        /** @var \DateTime $month */
        foreach (new \DatePeriod($firstDayOfThisYear, $monthInterval, $end->sub($monthInterval)) as $month) {
            if (null === $entityManager->getRepository('UnilendCoreBusinessBundle:DetailedDailyStateBalanceHistory')->findOneBy(['date' => $month->format('Y-m-t')])) {
                $this->saveBalance($month);
            }
        }
    }

    /**
     * @param \DateTime $date
     */
    private function saveBalance(\DateTime $date)
    {
        $entityManager                  = $this->getContainer()->get('doctrine.orm.entity_manager');
        $walletBalanceHistoryRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:WalletBalanceHistory');

        $dailyStateBalanceHistory = new DetailedDailyStateBalanceHistory();
        $dailyStateBalanceHistory->setLenderBalance($walletBalanceHistoryRepository->sumBalanceForDailyState($date, [WalletType::LENDER]));
        $dailyStateBalanceHistory->setBorrowerBalance($walletBalanceHistoryRepository->sumBalanceForDailyState($date, [WalletType::BORROWER]));
        $dailyStateBalanceHistory->setDebtCollectorBalance($walletBalanceHistoryRepository->sumBalanceForDailyState($date, [WalletType::DEBT_COLLECTOR]));
        $dailyStateBalanceHistory->setUnilendBalance($walletBalanceHistoryRepository->sumBalanceForDailyState($date, [WalletType::UNILEND]));
        $dailyStateBalanceHistory->setUnilendPromotionalBalance($walletBalanceHistoryRepository->sumBalanceForDailyState($date, [WalletType::UNILEND_PROMOTIONAL_OPERATION]));
        $dailyStateBalanceHistory->setTaxBalance($walletBalanceHistoryRepository->sumBalanceForDailyState($date, WalletType::TAX_FR_WALLETS));
        $dailyStateBalanceHistory->setDate($date->format('Y-m-d'));

        $entityManager->persist($dailyStateBalanceHistory);
        $entityManager->flush($dailyStateBalanceHistory);
    }
}