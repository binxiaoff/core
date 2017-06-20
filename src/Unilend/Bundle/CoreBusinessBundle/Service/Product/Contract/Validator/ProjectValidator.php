<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\Validator;

use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectContractAssessment;
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
        $contractAttributeTypes = [
            UnderlyingContractAttributeType::MAX_LOAN_DURATION_IN_MONTH,
            UnderlyingContractAttributeType::MIN_CREATION_DAYS,
            UnderlyingContractAttributeType::ELIGIBLE_BORROWER_COMPANY_RCS,
        ];

        foreach ($contractAttributeTypes as $contractAttributeType) {
            if (false === $this->check($project, $contract, $contractAttributeType)) {
                $this->entityManager->flush();

                return [$contractAttributeType];
            }
        }
        $this->entityManager->flush();

        return [];
    }

    private function check(Projects $project, UnderlyingContract $contract, $contractAttributeTypeLabel)
    {
        switch ($contractAttributeTypeLabel) {
            case UnderlyingContractAttributeType::MAX_LOAN_DURATION_IN_MONTH:
                $checkResult = $this->isEligibleForMaxDuration($project, $contract, $this->contractAttributeManager);
                break;
            case UnderlyingContractAttributeType::MIN_CREATION_DAYS:
                $checkResult = $this->isEligibleForCreationDays($project->getIdCompany(), $contract, $this->contractAttributeManager);
                break;
            case UnderlyingContractAttributeType::ELIGIBLE_BORROWER_COMPANY_RCS:
                $checkResult = $this->isEligibleForRCS($project->getIdCompany(), $contract, $this->contractAttributeManager);
                break;
            default;
                return true;
        }
        $contractAttributeType = $this->entityManager->getRepository('UnilendCoreBusinessBundle:UnderlyingContractAttributeType')->findOneBy(['label' => $contractAttributeTypeLabel]);

        if ($contractAttributeType) {
            $assessment = new ProjectContractAssessment();
            $assessment->setIdProject($project)
                ->setIdContract($contract)
                ->setIdContractAttributeType($contractAttributeType)
                ->setStatus($checkResult);

            $this->entityManager->persist($assessment);
        }

        return $checkResult;
    }
}
