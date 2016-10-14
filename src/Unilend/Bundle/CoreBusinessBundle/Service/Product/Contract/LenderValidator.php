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
    public function isContractEligible(\lenders_accounts $lender, \underlying_contract $contract)
    {
        return $this->isLenderTypeEligibleForContract($this->entityManager, $this->contractAttributeManager, $lender, $contract);
    }

    /**
     * @param \lenders_accounts $lender
     * @param \underlying_contract $contract
     *
     * @return bool
     */
    public function isAutobidEligible(\lenders_accounts $lender, \underlying_contract $contract)
    {
        return $this->isLenderEligibleForAutobid($this->entityManager, $this->contractAttributeManager, $lender, $contract);
    }

    /**
     * @param \lenders_accounts $lender
     * @param \product $product
     *
     * @return bool|int
     */
    public function getMaxEligibleAutobidAmountForLender(\lenders_accounts $lender, \product $product)
    {
        return $this->getMaxEligibleAutobidAmount($lender, $product, $this->entityManager, $this->contractAttributeManager);
    }
}
