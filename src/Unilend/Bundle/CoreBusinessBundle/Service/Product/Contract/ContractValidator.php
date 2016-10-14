<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract;


use Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\Checker\ContractChecker;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ContractAttributeManager;

class ContractValidator
{
    use ContractChecker;

    /** @var  ContractAttributeManager */
    private $contractAttributeManager;

    public function __construct(ContractAttributeManager $contractAttributeManager)
    {
        $this->contractAttributeManager = $contractAttributeManager;
    }

    /**
     * @param \underlying_contract $contract
     *
     * @return bool
     */
    public function isAutobidEligible(\underlying_contract $contract)
    {
        return $this->isContractAutobidEligible($contract, $this->contractAttributeManager);
    }

    /**
     * @param \underlying_contract $contract
     *
     * @return null|int
     */
    public function getMaximumAmount(\underlying_contract $contract)
    {
        return $this->getMaxEligibleAmount($contract, $this->contractAttributeManager);
    }

}
