<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract;


use Unilend\Bundle\CoreBusinessBundle\Service\Product\ContractAttributeManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class ContractManager
{
    /** @var  LenderValidator*/
    private $lenderValidator;
    /** @var  ContractAttributeManager */
    private $contractAttributeManager;

    public function __construct(
        LenderValidator $lenderValidator,
        ContractAttributeManager $contractAttributeManager
    ) {
        $this->lenderValidator          = $lenderValidator;
        $this->contractAttributeManager = $contractAttributeManager;
    }

    /**
     * @param \lenders_accounts $lender
     * @param \underlying_contract $contract
     *
     * @return bool
     */
    public function isLenderEligible(\lenders_accounts $lender, \underlying_contract $contract)
    {
        return $this->lenderValidator->isEligible($lender, $contract);
    }

    public function getMaxAmount(\underlying_contract $contract)
    {
        $maxAmount = $this->contractAttributeManager->getContractAttributesByType($contract, \underlying_contract_attribute_type::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO);
        if (empty($maxAmount)){
            return null;
        }

        return $maxAmount[0];
    }

}
