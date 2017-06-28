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
        $contractAttributeTypes = $this->entityManager->getRepository('UnilendCoreBusinessBundle:UnderlyingContractAttributeType')->findAll();

        foreach ($contractAttributeTypes as $contractAttributeType) {
            if (false === $this->check($project, $contract, $contractAttributeType)) {
                return [$contractAttributeType->getLabel()];
            }
        }

        return [];
    }

    /**
     * @param Projects                        $project
     * @param UnderlyingContract              $contract
     * @param UnderlyingContractAttributeType $contractAttributeType
     *
     * @return bool
     */
    private function check(Projects $project, UnderlyingContract $contract, UnderlyingContractAttributeType $contractAttributeType)
    {
        switch ($contractAttributeType->getLabel()) {
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

        $this->logCheck($project, $contract, $contractAttributeType, $checkResult);

        return $checkResult;
    }

    /**
     * @param Projects                        $project
     * @param UnderlyingContract              $contract
     * @param UnderlyingContractAttributeType $contractAttributeType
     * @param bool|null                       $checkResult
     */
    private function logCheck(Projects $project, UnderlyingContract $contract, UnderlyingContractAttributeType $contractAttributeType, $checkResult)
    {
        $assessment = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectContractAssessment')->findOneBy([
            'idProject'               => $project,
            'idContract'              => $contract,
            'idContractAttributeType' => $contractAttributeType
        ], ['added' => 'DESC']);

        if (null === $assessment || $checkResult !== $assessment->getStatus()) {
            $assessment = new ProjectContractAssessment();

            $assessment->setIdProject($project)
                ->setidContract($contract)
                ->setIdContractAttributeType($contractAttributeType)
                ->setStatus($checkResult);

            $this->entityManager->persist($assessment);
            $this->entityManager->flush();
        }
    }
}
