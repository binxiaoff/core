<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Checker;

use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\Product;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProductAttributeType;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductAttributeManager;

trait CompanyChecker
{
    /**
     * @param Companies               $company
     * @param Product                 $product
     * @param ProductAttributeManager $productAttributeManager
     *
     * @return bool
     */
    public function isEligibleForCreationDays(Companies $company, Product $product, ProductAttributeManager $productAttributeManager)
    {
        $minDays = $productAttributeManager->getProductAttributesByType($product, ProductAttributeType::MIN_CREATION_DAYS);

        if (empty($minDays)) {
            return true;
        }

        return $company->getDateCreation()->diff(new \DateTime())->days >= $minDays[0];
    }

    public function isEligibleForRCS(Companies $company, Product $product, ProductAttributeManager $productAttributeManager)
    {
        $beRCS = $productAttributeManager->getProductAttributesByType($product, ProductAttributeType::ELIGIBLE_BORROWER_COMPANY_RCS);

        if (empty($beRCS)) {
            return true;
        }

        return (false === (bool) $beRCS[0] && true === empty($company->getRcs())) || (true === (bool) $beRCS[0] && false === empty($company->getRcs()));
    }

    public function isEligibleForNafCode(Companies $company, Product $product, ProductAttributeManager $productAttributeManager)
    {
        $nafCode = $productAttributeManager->getProductAttributesByType($product, ProductAttributeType::ELIGIBLE_BORROWER_COMPANY_NAF_CODE);

        if (empty($nafCode)) {
            return true;
        }

        return in_array($company->getCodeNaf(), $nafCode);
    }
}
