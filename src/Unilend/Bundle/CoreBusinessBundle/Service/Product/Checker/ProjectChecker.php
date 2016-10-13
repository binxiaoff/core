<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Checker;

use Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductAttributeManager;

trait ProjectChecker
{
    /**
     * @param \projects               $project
     * @param \product                $product
     * @param ProductAttributeManager $productAttributeManager
     *
     * @return bool
     */
    public function isProductEligibleForMinDuration(\projects $project, \product $product, ProductAttributeManager $productAttributeManager)
    {
        $minDuration = $productAttributeManager->getProductAttributesByType($product, \product_attribute_type::MIN_LOAN_DURATION_IN_MONTH);

        if(empty($minDuration)) {
            return true;
        }

        return $project->period >= $minDuration[0];
    }

    /**
     * @param \projects               $project
     * @param \product                $product
     * @param ProductAttributeManager $productAttributeManager
     *
     * @return bool
     */
    public function isProductEligibleForMaxDuration(\projects $project, \product $product, ProductAttributeManager $productAttributeManager)
    {
        $maxDuration = $productAttributeManager->getProductAttributesByType($product, \product_attribute_type::MAX_LOAN_DURATION_IN_MONTH);

        if(empty($maxDuration)) {
            return true;
        }

        return $project->period <= $maxDuration[0];
    }

    /**
     * @param \projects               $project
     * @param \product                $product
     * @param ProductAttributeManager $productAttributeManager
     *
     * @return bool
     */
    public function isProductEligibleForMotive(\projects $project, \product $product, ProductAttributeManager $productAttributeManager)
    {
        $eligibleMotives = $productAttributeManager->getProductAttributesByType($product, \product_attribute_type::ELIGIBLE_BORROWING_MOTIVE);
        if (empty($eligibleMotives)) {
            return true;
        }

        return in_array($project->id_project_need, $eligibleMotives);
    }

    /**
     * @param \projects               $project
     * @param \product                $product
     * @param ProductAttributeManager $productAttributeManager
     *
     * @return bool
     */
    public function isProductEligibleForMaxContractDuration(\projects $project, \product $product, ProductAttributeManager $productAttributeManager)
    {
        $attrVars = $productAttributeManager->getProductContractAttributesByType($product, \underlying_contract_attribute_type::MAX_LOAN_DURATION_IN_MONTH);
        foreach($attrVars as $contractVars) {
            if (isset($contractVars[0]) && $contractVars[0] < $project->period) {
                return false;
            }
        }

        return true;
    }
}
