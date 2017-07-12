<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Validator;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Product;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectProductAssessment;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProductAttributeType;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Checker\CompanyChecker;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Checker\ProjectChecker;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\ContractManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductAttributeManager;
use Unilend\Bundle\WSClientBundle\Service\InfolegaleManager;

class ProjectValidator
{
    use ProjectChecker;
    use CompanyChecker;

    /** @var ProductAttributeManager */
    private $productAttributeManager;

    /** @var ContractManager */
    private $contractManager;

    /** @var EntityManager */
    private $entityManager;

    /** @var InfolegaleManager */
    private $infolegaleManager;

    public function __construct(ProductAttributeManager $productAttributeManager, ContractManager $contractManager, EntityManager $entityManager, InfolegaleManager $infolegaleManager)
    {
        $this->productAttributeManager = $productAttributeManager;
        $this->contractManager         = $contractManager;
        $this->entityManager           = $entityManager;
        $this->infolegaleManager       = $infolegaleManager;
    }

    /**
     * @param Projects $project
     * @param Product  $product
     *
     * @return array
     */
    public function validate(Projects $project, Product $product)
    {
        $productAttributeTypes = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProductAttributeType')->findAll();
        foreach ($productAttributeTypes as $productAttributeType) {
            if (false === $this->check($project, $product, $productAttributeType)) {
                return [$productAttributeType->getLabel()];
            }
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
            return $violationsContract;
        }

        return [];
    }

    /**
     * @param Projects             $project
     * @param Product              $product
     * @param ProductAttributeType $productAttributeType
     *
     * @return bool|null
     */
    private function check(Projects $project, Product $product, ProductAttributeType $productAttributeType)
    {
        switch ($productAttributeType->getLabel()) {
            case ProductAttributeType::MIN_LOAN_DURATION_IN_MONTH:
                $checkResult = $this->isEligibleForMinDuration($project, $product, $this->productAttributeManager);
                break;
            case ProductAttributeType::MAX_LOAN_DURATION_IN_MONTH:
                $checkResult = $this->isEligibleForMaxDuration($project, $product, $this->productAttributeManager);
                break;
            case ProductAttributeType::ELIGIBLE_BORROWING_MOTIVE:
                $checkResult = $this->isEligibleForMotive($project, $product, $this->productAttributeManager);
                break;
            case ProductAttributeType::ELIGIBLE_EXCLUDED_BORROWING_MOTIVE:
                $checkResult = $this->isEligibleForExcludedMotive($project, $product, $this->productAttributeManager);
                break;
            case ProductAttributeType::MIN_CREATION_DAYS:
                $checkResult = $this->isEligibleForCreationDays($project->getIdCompany(), $product, $this->productAttributeManager);
                break;
            case ProductAttributeType::ELIGIBLE_BORROWER_COMPANY_RCS:
                $checkResult = $this->isEligibleForRCS($project->getIdCompany(), $product, $this->productAttributeManager);
                break;
            case ProductAttributeType::ELIGIBLE_BORROWER_COMPANY_NAF_CODE:
                $checkResult = $this->isEligibleForNafCode($project->getIdCompany(), $product, $this->productAttributeManager);
                break;
            case ProductAttributeType::MIN_PRE_SCORE:
                $checkResult = $this->isEligibleForMinPreScore($project, $product, $this->productAttributeManager, $this->entityManager);
                break;
            case ProductAttributeType::MAX_PRE_SCORE:
                $checkResult = $this->isEligibleForMaxPreScore($project, $product, $this->productAttributeManager, $this->entityManager);
                break;
            case ProductAttributeType::ELIGIBLE_EXCLUDED_HEADQUARTERS_LOCATION:
                $checkResult = $this->isEligibleForExcludedHeadquartersLocation($project->getIdCompany(), $product, $this->productAttributeManager);
                break;
            case ProductAttributeType::MAX_XERFI_SCORE:
                $checkResult = $this->isEligibleForMaxXerfiScore($project->getIdCompany(), $product, $this->productAttributeManager, $this->entityManager);
                break;
            case ProductAttributeType::NO_IN_PROGRESS_BLEND_PROJECT_DAYS:
                $checkResult = $this->isEligibleForNoBlendProject($project->getIdCompany(), $product, $this->productAttributeManager, $this->entityManager);
                break;
            case ProductAttributeType::NO_INCIDENT_UNILEND_PROJECT_DAYS:
                $checkResult = $this->isEligibleForNoUnilendProjectIncident($project->getIdCompany(), $product, $this->productAttributeManager, $this->entityManager);
                break;
            case ProductAttributeType::NO_INCIDENT_BLEND_PROJECT_DAYS:
                $checkResult = $this->isEligibleForNoBlendProjectIncident($project->getIdCompany(), $product, $this->productAttributeManager, $this->entityManager);
                break;
            default;
                return true;
        }

        $this->logCheck($project, $product, $productAttributeType, $checkResult);

        return $checkResult;
    }

    /**
     * @param Projects             $project
     * @param Product              $product
     * @param ProductAttributeType $productAttributeType
     * @param bool|null            $checkResult
     */
    private function logCheck(Projects $project, Product $product, $productAttributeType, $checkResult)
    {
        $assessment = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectProductAssessment')->findOneBy([
            'idProject'              => $project,
            'idProduct'              => $product,
            'idProductAttributeType' => $productAttributeType
        ], ['added' => 'DESC']);

        if (null === $assessment || $checkResult !== $assessment->getStatus()) {
            $assessment = new ProjectProductAssessment();

            $assessment->setIdProject($project)
                ->setIdProduct($product)
                ->setIdProductAttributeType($productAttributeType)
                ->setStatus($checkResult);

            $this->entityManager->persist($assessment);
            $this->entityManager->flush($assessment);
        }
    }
}
