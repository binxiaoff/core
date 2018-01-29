<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;

class IfuManager
{
    const FILE_NAME_BENEFICIARY = 'BENEFICI.csv';
    const FILE_NAME_INFOSBEN    = 'INFOSBEN.csv';
    const FILE_NAME_INCOME      = 'REVENUS.csv';

    const LOSS_PROJECT_IDS = [2017 => [32108, 28957]];

    /** @var EntityManager */
    private $entityManager;

    /** @var string */
    private $protectedPath;

    /**
     * @param EntityManager $entityManager
     * @param string        $protectedPath
     */
    public function __construct(EntityManager $entityManager, $protectedPath)
    {
        $this->entityManager = $entityManager;
        $this->protectedPath = $protectedPath;
    }

    /**
     * @param int $year
     *
     * @return array
     */
    public function getWallets(int $year) : array
    {
        $walletsWithMovements = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getLenderWalletsWithOperationsInYear([
            OperationType::LENDER_LOAN,
            OperationType::CAPITAL_REPAYMENT,
            OperationType::GROSS_INTEREST_REPAYMENT
        ], $year);

        $walletsWithMovements = array_merge($walletsWithMovements, $this->getWalletsHavingLoss($year));

        return $walletsWithMovements;
    }

    /**
     * @return string
     */
    public function getStorageRootPath()
    {
        $directory = $this->protectedPath . DIRECTORY_SEPARATOR . 'IFU' . DIRECTORY_SEPARATOR . 'extraction';

        if (false === is_dir($directory)) {
            mkdir($directory);
        }

        return $this->protectedPath . DIRECTORY_SEPARATOR . 'IFU' . DIRECTORY_SEPARATOR . 'extraction';
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
            $wallets = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getLenderWalletsByProjects(self::LOSS_PROJECT_IDS[$year]);
        }

        return $wallets;
    }
}
