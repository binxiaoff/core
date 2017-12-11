<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;

class IfuManager
{
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
    public function getWallets($year)
    {
        $walletsWithMovements = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getLenderWalletsWithOperationsInYear([
            OperationType::LENDER_LOAN,
            OperationType::CAPITAL_REPAYMENT,
            OperationType::GROSS_INTEREST_REPAYMENT
        ], $year);

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
}
