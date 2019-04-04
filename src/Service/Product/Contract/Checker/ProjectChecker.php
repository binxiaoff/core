<?php

namespace Unilend\Service\Product\Contract\Checker;

use Unilend\Entity\{Projects, UnderlyingContract, UnderlyingContractAttributeType};
use Unilend\Service\Product\Contract\ContractAttributeManager;

trait ProjectChecker
{
    /**
     * @param Projects                 $project
     * @param UnderlyingContract       $contract
     * @param ContractAttributeManager $contractAttributeManager
     *
     * @return bool
     * @throws \Exception
     */
    public function isEligibleForMaxDuration(Projects $project, UnderlyingContract $contract, ContractAttributeManager $contractAttributeManager)
    {
        if (empty($project->getPeriod())) {
            return true;
        }

        $maxDuration = $contractAttributeManager->getContractAttributesByType($contract, UnderlyingContractAttributeType::MAX_LOAN_DURATION_IN_MONTH);

        if (empty($maxDuration)) {
            return true; // No limitation found!
        }

        return $project->getPeriod() <= $maxDuration[0];
    }
}
