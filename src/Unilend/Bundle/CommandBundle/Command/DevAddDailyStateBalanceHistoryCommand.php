<?php

namespace Unilend\Bundle\CommandBundle\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Bridge\Doctrine\DBAL\Connection;
use Unilend\Bundle\CoreBusinessBundle\Entity\DailyStateBalanceHistory;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;

class DevAddDailyStateBalanceHistoryCommand extends ContainerAwareCommand
{

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('dev:add_daily_balance_history')
            ->setDescription('fills the daily state balance history');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

        $dayInterval = \DateInterval::createFromDateString('1 day');
        $start       = new \DateTime('last day of january 2017');
        $end         = new \DateTime('NOW');
        $end->sub($dayInterval);
        $days        = new \DatePeriod($start, $dayInterval, $end);

        /** @var \DateTime $date */
        foreach ($days as $date) {
            if (null === $entityManager->getRepository('UnilendCoreBusinessBundle:DailyStateBalanceHistory')->findOneBy(['date' => $date->format('Y-m-d')])) {
                $this->saveBalance($date);
            }
        }
    }

    private function getBalance(\DateTime $date, array $walletTypes)
    {
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

        $date->setTime(23, 59, 59);

        $query = 'SELECT
                    IF(
                          SUM(wbh_line.committed_balance) IS NOT NULL,
                          (SUM(wbh_line.available_balance) + SUM(wbh_line.committed_balance)),
                          SUM(wbh_line.available_balance)
                      ) AS balance
                  FROM wallet_balance_history wbh_line
                    INNER JOIN (
                                   SELECT MAX(wbh_max.id) AS id FROM wallet_balance_history wbh_max
                                     INNER JOIN wallet w ON wbh_max.id_wallet = w.id
                                     INNER JOIN wallet_type wt ON w.id_type = wt.id
                                   WHERE wbh_max.added <= :end
                                         AND wt.label IN (:walletLabels)
                                   GROUP BY wbh_max.id_wallet
                                 ) wbh_max ON wbh_line.id = wbh_max.id';

        $result = $entityManager->getConnection()
            ->executeQuery($query,
                ['end' => $date->format('Y-m-d H:i:s'), 'walletLabels' => $walletTypes],
                ['end' => \PDO::PARAM_STR, 'walletLabels' => Connection::PARAM_STR_ARRAY]
            )->fetchColumn(0);

        if ($result === null) {
            return '0.00';
        }

        return $result;

    }

    private function saveBalance(\DateTime $date)
    {
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

        $dailyStateBalanceHistory = new DailyStateBalanceHistory();
        $dailyStateBalanceHistory->setLenderBorrowerBalance($this->getBalance($date, [WalletType::LENDER, WalletType::BORROWER]));
        $dailyStateBalanceHistory->setUnilendBalance($this->getBalance($date, [WalletType::UNILEND]));
        $dailyStateBalanceHistory->setUnilendPromotionalBalance($this->getBalance($date, [WalletType::UNILEND_PROMOTIONAL_OPERATION]));
        $dailyStateBalanceHistory->setTaxBalance($this->getBalance($date, WalletType::TAX_FR_WALLETS));
        $dailyStateBalanceHistory->setDate($date->format('Y-m-d'));

        $entityManager->persist($dailyStateBalanceHistory);
        $entityManager->flush($dailyStateBalanceHistory);
    }
}
