<?php

namespace Unilend\Service;

use Doctrine\ORM\EntityManagerInterface;
use Unilend\Entity\{OperationType, Wallet};

class IfuManager
{
    const FILE_NAME_BENEFICIARY = 'BENEFICI.csv';
    const FILE_NAME_INFOSBEN    = 'INFOSBEN.csv';
    const FILE_NAME_INCOME      = 'REVENUS.csv';

    const LOSS_PROJECT_IDS = [2017 => [32108, 28957]];

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var string */
    private $protectedDirectory;

    /**
     * @param EntityManagerInterface $entityManager
     * @param string                 $protectedDirectory
     */
    public function __construct(EntityManagerInterface $entityManager, $protectedDirectory)
    {
        $this->entityManager      = $entityManager;
        $this->protectedDirectory = $protectedDirectory;
    }

    /**
     * @param int $year
     *
     * @return array
     */
    public function getWallets(int $year) : array
    {
        $walletsWithMovements = $this->entityManager->getRepository(Wallet::class)->getLenderWalletsWithOperationsInYear([
            OperationType::LENDER_LOAN,
            OperationType::CAPITAL_REPAYMENT,
            OperationType::GROSS_INTEREST_REPAYMENT
        ], $year);

        $walletsWithMovements = array_merge($walletsWithMovements, $this->getWalletsHavingLoss($year));

        return array_unique($walletsWithMovements, SORT_REGULAR);
    }

    /**
     * @return string
     */
    public function getStorageRootPath()
    {
        $directory = $this->protectedDirectory . DIRECTORY_SEPARATOR . 'IFU' . DIRECTORY_SEPARATOR . 'extraction';

        if (false === is_dir($directory)) {
            mkdir($directory);
        }

        return $this->protectedDirectory . DIRECTORY_SEPARATOR . 'IFU' . DIRECTORY_SEPARATOR . 'extraction';
    }

    /**
     * @return string
     */
    public function getYear()
    {
        $now  = new \DateTime();
        $year = $now->format('Y');
        if (in_array($now->format('n'), [1, 2, 3])) {
            $now->modify('-1 year');
            $year = $now->format('Y');
        }

        return $year;
    }

    /**
     * @param int $year
     *
     * @return array
     */
    private function getWalletsHavingLoss(int $year) : array
    {
        $projects = empty(self::LOSS_PROJECT_IDS[$year]) ? [] : self::LOSS_PROJECT_IDS[$year];
        $wallets  = [];

        if ($projects) {
            $wallets = $this->entityManager->getRepository(Wallet::class)->getLenderWalletsByProjects(self::LOSS_PROJECT_IDS[$year]);
        }

        return $wallets;
    }
}
