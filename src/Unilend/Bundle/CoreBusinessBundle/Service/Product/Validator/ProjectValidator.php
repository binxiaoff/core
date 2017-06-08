<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Validator;

use Unilend\Bundle\CoreBusinessBundle\Entity\Product;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProductAttributeType;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Checker\CompanyChecker;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Checker\ProjectChecker;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\ContractManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductAttributeManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class ProjectValidator
{
    use ProjectChecker;
    use CompanyChecker;

    /** @var ProductAttributeManager */
    private $productAttributeManager;

    /** @var EntityManager */
    private $entityManager;

    /** @var ContractManager */
    private $contractManager;

    public function __construct(ProductAttributeManager $productAttributeManager, EntityManager $entityManager, ContractManager $contractManager)
    {
        $this->productAttributeManager = $productAttributeManager;
        $this->entityManager           = $entityManager;
        $this->contractManager         = $contractManager;
    }

    /**
     * @param Projects $project
     * @param Product  $product
     *
     * @return array
     */
    public function validate(Projects $project, Product $product)
    {
        $violations = [];

        if (false === $this->isEligibleForMinDuration($project, $product, $this->productAttributeManager)) {
            $violations[] = ProductAttributeType::MIN_LOAN_DURATION_IN_MONTH;
        }

        if (false === $this->isEligibleForMaxDuration($project, $product, $this->productAttributeManager)) {
            $violations[] = ProductAttributeType::MAX_LOAN_DURATION_IN_MONTH;
        }

        if (false === $this->isEligibleForMotive($project, $product, $this->productAttributeManager)) {
            $violations[] = ProductAttributeType::ELIGIBLE_BORROWING_MOTIVE;
        }

        if (false === $this->isEligibleForCreationDays($project->getIdCompany(), $product, $this->productAttributeManager)) {
            $violations[] = ProductAttributeType::MIN_CREATION_DAYS;
        }

        if (false === $this->isEligibleForRCS($project->getIdCompany(), $product, $this->productAttributeManager)) {
            $violations[] = ProductAttributeType::ELIGIBLE_BORROWER_COMPANY_RCS;
        }

        if (false === $this->isEligibleForNafCode($project->getIdCompany(), $product, $this->productAttributeManager)) {
            $violations[] = ProductAttributeType::ELIGIBLE_BORROWER_COMPANY_NAF_CODE;
        }

        $hasEligibleContract = false;
        $violationsContract  = [];
        foreach ($product->getIdContract() as $contract) {
            $contractCheckResult = $this->contractManager->checkProjectEligibility($project, $contract);
            if (0 < count($contractCheckResult)) {
                $violationsContract = array_merge($violationsContract, $contractCheckResult);
            } else {
                $hasEligibleContract = true;
            }
        }

        if (false === $hasEligibleContract) {
            $violations = array_merge($violations, $violationsContract);
        }

        return $violations;
    }
}
