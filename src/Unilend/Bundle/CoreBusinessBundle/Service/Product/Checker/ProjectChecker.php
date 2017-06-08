<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Checker;

use Unilend\Bundle\CoreBusinessBundle\Entity\Product;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProductAttributeType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductAttributeManager;

trait ProjectChecker
{
    /**
     * @param Projects                $project
     * @param Product                 $product
     * @param ProductAttributeManager $productAttributeManager
     *
     * @return bool
     */
    public function isEligibleForMinDuration(Projects $project, Product $product, ProductAttributeManager $productAttributeManager)
    {
        if (empty($project->getPeriod())) {
            return true;
        }

        $minDuration = $productAttributeManager->getProductAttributesByType($product, ProductAttributeType::MIN_LOAN_DURATION_IN_MONTH);

        if (empty($minDuration)) {
            return true;
        }

        return $project->getPeriod() >= $minDuration[0];
    }

    /**
     * @param Projects                $project
     * @param Product                 $product
     * @param ProductAttributeManager $productAttributeManager
     *
     * @return bool
     */
    public function isEligibleForMaxDuration(Projects $project, Product $product, ProductAttributeManager $productAttributeManager)
    {
        if (empty($project->getPeriod())) {
            return true;
        }

        $maxDuration = $productAttributeManager->getProductAttributesByType($product, ProductAttributeType::MAX_LOAN_DURATION_IN_MONTH);

        if (empty($maxDuration)) {
            return true;
        }

        return $project->getPeriod() <= $maxDuration[0];
    }

    /**
     * @param Projects                $project
     * @param Product                 $product
     * @param ProductAttributeManager $productAttributeManager
     *
     * @return bool
     */
    public function isEligibleForMotive(Projects $project, Product $product, ProductAttributeManager $productAttributeManager)
    {
        $eligibleMotives = $productAttributeManager->getProductAttributesByType($product, ProductAttributeType::ELIGIBLE_BORROWING_MOTIVE);
        if (empty($eligibleMotives)) {
            return true;
        }

        return in_array($project->getIdBorrowingMotive(), $eligibleMotives);
    }
}
