<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Checker;

use Doctrine\ORM\EntityManager;
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

        if (empty($minDays) || empty($company->getDateCreation())) {
            return true;
        }

        return $company->getDateCreation()->diff(new \DateTime())->days >= $minDays[0];
    }

    /**
     * @param Companies               $company
     * @param Product                 $product
     * @param ProductAttributeManager $productAttributeManager
     *
     * @return bool
     */
    public function isEligibleForRCS(Companies $company, Product $product, ProductAttributeManager $productAttributeManager)
    {
        $beRCS = $productAttributeManager->getProductAttributesByType($product, ProductAttributeType::ELIGIBLE_BORROWER_COMPANY_RCS);

        if (empty($beRCS)) {
            return true;
        }

        return (false === (bool) $beRCS[0] && true === empty($company->getRcs())) || (true === (bool) $beRCS[0] && false === empty($company->getRcs()));
    }

    /**
     * @param Companies               $company
     * @param Product                 $product
     * @param ProductAttributeManager $productAttributeManager
     *
     * @return bool
     */
    public function isEligibleForNafCode(Companies $company, Product $product, ProductAttributeManager $productAttributeManager)
    {
        $nafCode = $productAttributeManager->getProductAttributesByType($product, ProductAttributeType::ELIGIBLE_BORROWER_COMPANY_NAF_CODE);

        if (empty($nafCode)) {
            return true;
        }

        return in_array($company->getCodeNaf(), $nafCode);
    }

    public function isEligibleForHeadquartersLocation(Companies $company, Product $product, ProductAttributeManager $productAttributeManager)
    {
        $exclusiveLocations = $productAttributeManager->getProductAttributesByType($product, ProductAttributeType::HEADQUARTERS_LOCATION_EXCLUSIVE);

        if (empty($exclusiveLocations)) {
            return true;
        }
        $departement = in_array(substr($company->getZip(), 0, 2), ['97', '98']) ? substr($company->getZip(), 0, 3) : substr($company->getZip(), 0, 2);

        return false === in_array($departement, $exclusiveLocations);
    }

    public function isEligibleForMaxXerfiScore(Companies $company, Product $product, ProductAttributeManager $productAttributeManager, EntityManager $entityManager)
    {
        $maxXerfiScore = $productAttributeManager->getProductAttributesByType($product, ProductAttributeType::MAX_XERFI_SCORE);

        if (empty($maxXerfiScore)) {
            return true;
        }

        $xerfiScore = $entityManager->getRepository('UnilendCoreBusinessBundle:Xerfi')->find($company->getCodeNaf());

        return $xerfiScore <= $maxXerfiScore;
    }
}
