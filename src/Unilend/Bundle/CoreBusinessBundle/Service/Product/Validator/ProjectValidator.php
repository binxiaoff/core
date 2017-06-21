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
        $productAttributeTypes = [
            ProductAttributeType::MIN_LOAN_DURATION_IN_MONTH,
            ProductAttributeType::MAX_LOAN_DURATION_IN_MONTH,
            ProductAttributeType::ELIGIBLE_BORROWING_MOTIVE,
            ProductAttributeType::MIN_CREATION_DAYS,
            ProductAttributeType::ELIGIBLE_BORROWER_COMPANY_RCS,
            ProductAttributeType::ELIGIBLE_BORROWER_COMPANY_NAF_CODE,
            ProductAttributeType::VERIFICATION_REQUESTER_IS_ONE_OF_THE_DIRECTOR,
            ProductAttributeType::ELIGIBLE_HEADQUARTERS_LOCATION_EXCLUSIVE,
            ProductAttributeType::MAX_XERFI_SCORE,
            ProductAttributeType::MIN_NO_IN_PROGRESS_BLEND_PROJECT_DAYS,
            ProductAttributeType::MIN_NO_INCIDENT_BLEND_PROJECT_DAYS,
            ProductAttributeType::MIN_NO_INCIDENT_UNILEND_PROJECT_DAYS,
        ];

        foreach ($productAttributeTypes as $productAttributeType) {
            if (false === $this->check($project, $product, $productAttributeType)) {
                $this->entityManager->flush();

                return [$productAttributeType];
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
            $this->entityManager->flush();

            return $violationsContract;
        }
        $this->entityManager->flush();

        return [];
    }

    /**
     * @param Projects $project
     * @param Product  $product
     * @param string   $productAttributeTypeLabel
     *
     * @return bool
     */
    private function check(Projects $project, Product $product, $productAttributeTypeLabel)
    {
        switch ($productAttributeTypeLabel) {
            case ProductAttributeType::MIN_LOAN_DURATION_IN_MONTH:
                $checkResult = $this->isEligibleForMinDuration($project, $product, $this->productAttributeManager);
                break;
            case ProductAttributeType::MAX_LOAN_DURATION_IN_MONTH:
                $checkResult = $this->isEligibleForMaxDuration($project, $product, $this->productAttributeManager);
                break;
            case ProductAttributeType::ELIGIBLE_BORROWING_MOTIVE:
                $checkResult = $this->isEligibleForMotive($project, $product, $this->productAttributeManager);
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
            case ProductAttributeType::VERIFICATION_REQUESTER_IS_ONE_OF_THE_DIRECTOR:
                $checkResult = $this->isEligibleForRequesterName($project, $product, $this->productAttributeManager, $this->infolegaleManager, $this->entityManager);
                break;
            case ProductAttributeType::ELIGIBLE_HEADQUARTERS_LOCATION_EXCLUSIVE:
                $checkResult = $this->isEligibleForHeadquartersLocation($project->getIdCompany(), $product, $this->productAttributeManager);
                break;
            case ProductAttributeType::MAX_XERFI_SCORE:
                $checkResult = $this->isEligibleForMaxXerfiScore($project->getIdCompany(), $product, $this->productAttributeManager, $this->entityManager);
                break;
            case ProductAttributeType::MIN_NO_IN_PROGRESS_BLEND_PROJECT_DAYS:
                $checkResult = $this->isEligibleForNoBlendProject($project->getIdCompany(), $product, $this->productAttributeManager, $this->entityManager);
                break;
            case ProductAttributeType::MIN_NO_INCIDENT_UNILEND_PROJECT_DAYS:
                $checkResult = $this->isEligibleForNoUnilendProjectIncident($project->getIdCompany(), $product, $this->productAttributeManager, $this->entityManager);
                break;
            case ProductAttributeType::MIN_NO_INCIDENT_BLEND_PROJECT_DAYS:
                $checkResult = $this->isEligibleForNoBlendProjectIncident($project->getIdCompany(), $product, $this->productAttributeManager, $this->entityManager);
                break;
            default;
                return true;
        }

        $productAttributeType = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProductAttributeType')->findOneBy(['label' => $productAttributeTypeLabel]);

        if ($productAttributeType) {
            $assessment = new ProjectProductAssessment();
            $assessment->setIdProject($project)
                ->setIdProduct($product)
                ->setIdProductAttributeType($productAttributeType)
                ->setStatus($checkResult);

            $this->entityManager->persist($assessment);
        }

        return $checkResult;
    }
}
