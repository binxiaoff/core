<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Checker;


use Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductAttributeManager;

trait CompanyChecker
{
    public function isEligibleForCreationDays(\companies $company, \product $product, ProductAttributeManager $productAttributeManager)
    {
        $minDays = $productAttributeManager->getProductAttributesByType($product, \product_attribute_type::MIN_CREATION_DAYS_PROSPECT);

        if(empty($minDays)) {
            return true;
        }

        $companyCreationDate = new \DateTime($company->date_creation);
        if ($companyCreationDate->diff(new \DateTime())->days < $minDays[0]) {
            return false;
        }

        return true;
    }

    public function isEligibleForRCS(\companies $company, \product $product, ProductAttributeManager $productAttributeManager)
    {
        $beRCS = $productAttributeManager->getProductAttributesByType($product, \product_attribute_type::ELIGIBLE_BORROWER_COMPANY_RCS);

        if(empty($beRCS)) {
            return true;
        }

        if ($beRCS[0] && empty($company->rcs)) {
            return false;
        }

        return true;
    }
}