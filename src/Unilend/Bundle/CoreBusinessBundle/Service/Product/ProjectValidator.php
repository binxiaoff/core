<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service\Product;

use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class ProjectValidator
{
    /** @var ProductAttributeManager */
    private $productAttributeManager;
    /** @var EntityManager */
    private $entityManager;

    public function __construct(ProductAttributeManager $productAttributeManager, EntityManager $entityManager)
    {
        $this->productAttributeManager = $productAttributeManager;
        $this->entityManager = $entityManager;
    }

    /**
     * @param \projects $projects
     * @param \product  $product
     *
     * @return bool
     */
    public function isEligible(\projects $projects, \product $product)
    {
        foreach ($this->getAttributeTypeToCheck() as $attributeTypeToCheck) {
            switch ($attributeTypeToCheck) {
                case \product_attribute_type::MIN_LOAN_DURATION_IN_MONTH :
                    $eligibility = $this->isEligibleForMinDuration($projects, $product);
                    break;
                case \product_attribute_type::MAX_LOAN_DURATION_IN_MONTH :
                    $eligibility = $this->isEligibleForMaxDuration($projects, $product);
                    break;
                case \product_attribute_type::ELIGIBLE_BORROWER_COMPANY_TYPE :
                    $eligibility = $this->isEligibleForCompanyType($projects, $product);
                    break;
                case \product_attribute_type::ELIGIBLE_NEED :
                    $eligibility = $this->isEligibleForNeed($projects, $product);
                    break;
                case \underlying_contract_attribute_type::MAX_LOAN_DURATION_IN_MONTH :
                    $eligibility = $this->isEligibleForMaxContractDuration($projects, $product);
                    break;
                default :
                    $eligibility = false;
            }

            if (false === $eligibility) {
                return $eligibility;
            }
        }

        return true;
    }

    private function getAttributeTypeToCheck()
    {
        return [
            //\product_attribute_type::ELIGIBLE_BORROWER_COMPANY_COUNTRY,
            //\product_attribute_type::TARGET_COUNTRY,
            \product_attribute_type::ELIGIBLE_BORROWER_COMPANY_TYPE,
            \product_attribute_type::ELIGIBLE_NEED,
            \product_attribute_type::MIN_LOAN_DURATION_IN_MONTH,
            \product_attribute_type::MAX_LOAN_DURATION_IN_MONTH,
            \underlying_contract_attribute_type::MAX_LOAN_DURATION_IN_MONTH
        ];
    }

    /**
     * @param \projects $project
     * @param \product  $product
     *
     * @return bool
     */
    private function isEligibleForMinDuration(\projects $project, \product $product)
    {
        $minDuration = $this->productAttributeManager->getProductAttributesByType($product, \product_attribute_type::MIN_LOAN_DURATION_IN_MONTH);

        if(empty($minDuration)) {
            return true;
        }

        return $project->period >= $minDuration[0];
    }

    /**
     * @param \projects $project
     * @param \product  $product
     *
     * @return bool
     */
    private function isEligibleForMaxDuration(\projects $project, \product $product)
    {
        $maxDuration = $this->productAttributeManager->getProductAttributesByType($product, \product_attribute_type::MAX_LOAN_DURATION_IN_MONTH);

        if(empty($maxDuration)) {
            return true;
        }

        return $project->period <= $maxDuration[0];
    }

    /**
     * @param \projects $project
     * @param \product  $product
     *
     * @return bool
     */
    private function isEligibleForNeed(\projects $project, \product $product)
    {
        $eligibleNeeds = $this->productAttributeManager->getProductAttributesByType($product, \product_attribute_type::ELIGIBLE_NEED);
        if (empty($eligibleNeeds)) {
            return true;
        }

        return in_array($project->id_project_need, $eligibleNeeds);
    }

    private function isEligibleForCompanyType(\projects $project, \product $product)
    {
        $eligibleTypes = $this->productAttributeManager->getProductAttributesByType($product, \product_attribute_type::ELIGIBLE_BORROWER_COMPANY_TYPE);
        if (false === empty($eligibleTypes)) {
            // todo: need to define a list of available company type.
        }

        return true;
    }

    private function isEligibleForMaxContractDuration(\projects $project, \product $product)
    {
        $attrVars = $this->productAttributeManager->getProductContractAttributesByType($product, \underlying_contract_attribute_type::MAX_LOAN_DURATION_IN_MONTH);
        foreach($attrVars as $contractVars) {
            if ($contractVars[0] < $project->period) {
                return false;
            }
        }

        return true;
    }
}
