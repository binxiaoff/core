<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\Checker;

use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContract;
use Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContractAttributeType;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ContractAttributeManager;

trait CompanyChecker
{
    public function isEligibleForCreationDays(Companies $company, UnderlyingContract $contract, ContractAttributeManager $contractAttributeManager)
    {
        $minDays = $contractAttributeManager->getContractAttributesByType($contract, UnderlyingContractAttributeType::MIN_CREATION_DAYS);

        if (empty($minDays)) {
            return true;
        }

        return $company->getDateCreation()->diff(new \DateTime())->days >= $minDays[0];
    }

    public function isEligibleForRCS(Companies $company, UnderlyingContract $contract, ContractAttributeManager $contractAttributeManager)
    {
        $beRCS = $contractAttributeManager->getContractAttributesByType($contract, UnderlyingContractAttributeType::ELIGIBLE_BORROWER_COMPANY_RCS);

        if (empty($beRCS)) {
            return true;
        }

        return (false === (bool) $beRCS[0] && true === empty($company->getRcs())) || (true === (bool) $beRCS[0] && false === empty($company->getRcs()));
    }
}
