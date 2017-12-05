<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;

class IfuManager
{
    /** @var EntityManager */
    private $entityManager;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param int $year
     *
     * @return array
     */
    public function getWallets($year)
    {
        $operationTypes = [
            OperationType::LENDER_LOAN,
            OperationType::CAPITAL_REPAYMENT,
            OperationType::GROSS_INTEREST_REPAYMENT
        ];
        $walletRepository     = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Wallet');
        $walletsWithMovements = $walletRepository->getLenderWalletsWithOperationsInYear($operationTypes, $year);

        return $walletsWithMovements;
    }
}
