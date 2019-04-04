<?php

namespace Unilend\Service\Product\Contract\Checker;

use Unilend\Entity\{Clients, UnderlyingContract, UnderlyingContractAttributeType};
use Unilend\Service\Product\Contract\ContractAttributeManager;

trait ClientChecker
{
    /**
     * @param Clients|null             $client
     * @param UnderlyingContract       $contract
     * @param ContractAttributeManager $contractAttributeManager
     *
     * @return bool
     * @throws \Exception
     */
    public function isEligibleForClientType(Clients $client = null, UnderlyingContract $contract, ContractAttributeManager $contractAttributeManager)
    {
        if (null === $client) {
            return true; // Impossible to check
        }

        $attrVars = $contractAttributeManager->getContractAttributesByType($contract, UnderlyingContractAttributeType::ELIGIBLE_CLIENT_TYPE);

        if (empty($attrVars)) {
            return true; // No limitation found!
        }

        return $client !== null && in_array($client->getType(), $attrVars);
    }
}
