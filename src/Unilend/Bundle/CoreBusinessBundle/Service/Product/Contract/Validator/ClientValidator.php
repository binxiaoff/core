<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\Validator;

use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContract;
use Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContractAttributeType;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\Checker\ClientChecker;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\ContractAttributeManager;

class ClientValidator
{
    use ClientChecker;

    /** @var ContractAttributeManager */
    private $contractAttributeManager;

    public function __construct(ContractAttributeManager $contractAttributeManager)
    {
        $this->contractAttributeManager = $contractAttributeManager;
    }

    /**
     * @param Clients|null            $client
     * @param UnderlyingContract $contract
     *
     * @return array
     */
    public function validate(Clients $client = null, UnderlyingContract $contract)
    {
        $violations = [];

        if (false === $this->isEligibleForClientType($client, $contract, $this->contractAttributeManager)) {
            $violations[] = UnderlyingContractAttributeType::ELIGIBLE_CLIENT_TYPE;
        }

        return $violations;
    }
}
