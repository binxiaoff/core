<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Checker;

use Unilend\Bundle\CoreBusinessBundle\Entity\ProductAttributeType;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductAttributeManager;

trait ProjectChecker
{
    use CompanyChecker;

    /**
     * @param \projects               $project
     * @param \product                $product
     * @param ProductAttributeManager $productAttributeManager
     *
     * @return bool
     */
    public function isEligibleForMinDuration(\projects $project, \product $product, ProductAttributeManager $productAttributeManager)
    {
        $minDuration = $productAttributeManager->getProductAttributesByType($product, ProductAttributeType::MIN_LOAN_DURATION_IN_MONTH);

        if (empty($minDuration)) {
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
    public function isEligibleForMaxDuration(\projects $project, \product $product, ProductAttributeManager $productAttributeManager)
    {
        $maxDuration = $productAttributeManager->getProductAttributesByType($product, ProductAttributeType::MAX_LOAN_DURATION_IN_MONTH);

        if (empty($maxDuration)) {
            return $this->isEligibleForMaxContractDuration($project, $product, $productAttributeManager);
        }

        return ($project->period <= $maxDuration[0]) && $this->isEligibleForMaxContractDuration($project, $product, $productAttributeManager);
    }

    /**
     * @param \projects               $project
     * @param \product                $product
     * @param ProductAttributeManager $productAttributeManager
     *
     * @return bool
     */
    public function isEligibleForMotive(\projects $project, \product $product, ProductAttributeManager $productAttributeManager)
    {
        $eligibleMotives = $productAttributeManager->getProductAttributesByType($product, ProductAttributeType::ELIGIBLE_BORROWING_MOTIVE);
        if (empty($eligibleMotives)) {
            return true;
        }

        return in_array($project->id_borrowing_motive, $eligibleMotives);
    }

    /**
     * @param \projects               $project
     * @param \product                $product
     * @param ProductAttributeManager $productAttributeManager
     *
     * @return bool
     */
    private function isEligibleForMaxContractDuration(\projects $project, \product $product, ProductAttributeManager $productAttributeManager)
    {
        $attrVars = $productAttributeManager->getProductContractAttributesByType($product, \underlying_contract_attribute_type::MAX_LOAN_DURATION_IN_MONTH);
        foreach ($attrVars as $contractVars) {
            if (isset($contractVars[0]) && $contractVars[0] < $project->period) {
                return false;
            }
        }

        return true;
    }
}
