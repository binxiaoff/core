<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\Validator;

use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContract;
use Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContractAttributeType;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\Checker\CompanyChecker;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\Checker\ProjectChecker;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\ContractAttributeManager;
use Doctrine\ORM\EntityManager;

class ProjectValidator
{
    use ProjectChecker;
    use CompanyChecker;

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
     * @param Projects           $project
     * @param UnderlyingContract $contract
     *
     * @return array
     */
    public function validate(Projects $project, UnderlyingContract $contract)
    {
        $violations = [];

        if (false === $this->isEligibleForMaxDuration($project, $contract, $this->contractAttributeManager)) {
            $violations[] = UnderlyingContractAttributeType::MAX_LOAN_DURATION_IN_MONTH;
        }

        if (false === $this->isEligibleForCreationDays($project->getIdCompany(), $contract, $this->contractAttributeManager)) {
            $violations[] = UnderlyingContractAttributeType::MAX_LOAN_DURATION_IN_MONTH;
        }

        if (false === $this->isEligibleForRCS($project->getIdCompany(), $contract, $this->contractAttributeManager)) {
            $violations[] = UnderlyingContractAttributeType::MAX_LOAN_DURATION_IN_MONTH;
        }

        return $violations;
    }
}
