<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract;

use Unilend\Bundle\CoreBusinessBundle\Service\Product\ContractAttributeManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class LenderValidator
{
    use Checker\LenderChecker;

    /** @var ContractAttributeManager */
    private $contractAttributeManager;
    /** @var EntityManager */
    private $entityManager;

    public function __construct(ContractAttributeManager $contractAttributeManager, EntityManager $entityManager)
    {
        $this->contractAttributeManager = $contractAttributeManager;
        $this->entityManager            = $entityManager;
    }

    /**
     * @param \lenders_accounts $lender
     * @param \underlying_contract $contract
     *
     * @return bool
     */
    public function isEligible(\lenders_accounts $lender, \underlying_contract $contract)
    {
        return $this->isEligibleForLenderType($lender, $contract, $this->entityManager, $this->contractAttributeManager);
    }
}
