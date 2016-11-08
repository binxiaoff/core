<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract;

use Unilend\Bundle\CoreBusinessBundle\Service\Product\ContractAttributeManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class AutoBidSettingsValidator
{
    use Checker\AutoBidSettingsChecker;

    /** @var ContractAttributeManager */
    private $contractAttributeManager;
    /** @var EntityManager */
    private $entityManager;

    public function __construct(ContractAttributeManager $contractAttributeManager, EntityManager $entityManager)
    {
        $this->contractAttributeManager = $contractAttributeManager;
        $this->entityManager            = $entityManager;
    }

    public function isEligible($contract)
    {
        return $this->isEligibleForEligibility($contract, $this->contractAttributeManager);
    }
}
