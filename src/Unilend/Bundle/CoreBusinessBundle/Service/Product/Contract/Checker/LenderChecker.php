<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\Checker;

use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContract;
use Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContractAttributeType;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ContractAttributeManager;

trait LenderChecker
{
    /**
     * @param Clients                  $client
     * @param UnderlyingContract       $contract
     * @param ContractAttributeManager $contractAttributeManager
     *
     * @return bool
     * @throws \Exception
     */
    public function isEligibleForLenderType(Clients $client, UnderlyingContract $contract, ContractAttributeManager $contractAttributeManager)
    {
        $attrVars = $contractAttributeManager->getContractAttributesByType($contract, UnderlyingContractAttributeType::ELIGIBLE_LENDER_TYPE);

        if (empty($attrVars)) {
            return true; // No limitation found!
        }

        return in_array($client->getType(), $attrVars);
    }
}
