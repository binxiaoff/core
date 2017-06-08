<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\Validator;

use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContract;
use Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContractAttributeType;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\Checker\LenderChecker;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ContractAttributeManager;

class ClientValidator
{
    use LenderChecker;

    /** @var ContractAttributeManager */
    private $contractAttributeManager;

    public function __construct(ContractAttributeManager $contractAttributeManager)
    {
        $this->contractAttributeManager = $contractAttributeManager;
    }

    /**
     * @param Clients            $client
     * @param UnderlyingContract $contract
     *
     * @return array
     */
    public function validate(Clients $client, UnderlyingContract $contract)
    {
        $violations = [];

        if (false === $this->isEligibleForLenderType($client, $contract, $this->contractAttributeManager)) {
            $violations[] = UnderlyingContractAttributeType::ELIGIBLE_LENDER_TYPE;
        }

        return $violations;
    }
}
