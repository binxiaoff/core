<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Checker;


use Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductAttributeManager;

trait CompanyChecker
{
    public function isEligibleForCreationDays(\companies $company, \product $product, ProductAttributeManager $productAttributeManager)
    {
        $minDays = $productAttributeManager->getProductAttributesByType($product, \product_attribute_type::MIN_CREATION_DAYS);

        if (empty($minDays)) {
            return $this->isEligibleForContractCreationDays($company, $product, $productAttributeManager);
        }

        $companyCreationDate = new \DateTime($company->date_creation);
        if ($companyCreationDate->diff(new \DateTime())->days < $minDays[0]) {
            return false;
        }

        return $this->isEligibleForContractCreationDays($company, $product, $productAttributeManager);
    }

    public function isEligibleForRCS(\companies $company, \product $product, ProductAttributeManager $productAttributeManager)
    {
        $beRCS = $productAttributeManager->getProductAttributesByType($product, \product_attribute_type::ELIGIBLE_BORROWER_COMPANY_RCS);

        if (empty($beRCS)) {
            return $this->isEligibleForContractRCS($company, $product, $productAttributeManager);
        }

        if ($beRCS[0] && empty($company->rcs)) {
            return false;
        }

        return $this->isEligibleForContractRCS($company, $product, $productAttributeManager);
    }

    public function isEligibleForNafCode(\companies $company, \product $product, ProductAttributeManager $productAttributeManager)
    {
        $nafCode = $productAttributeManager->getProductAttributesByType($product, \product_attribute_type::ELIGIBLE_BORROWER_COMPANY_NAF_CODE);

        if (empty($nafCode)) {
            return true;
        }

        return in_array($company->code_naf, $nafCode);
    }

    public function isEligibleForContractCreationDays(\companies $company, \product $product, ProductAttributeManager $productAttributeManager)
    {
        $companyCreationDate = new \DateTime($company->date_creation);
        $today               = new \DateTime();

        $attrVars = $productAttributeManager->getProductContractAttributesByType($product, \underlying_contract_attribute_type::MIN_CREATION_DAYS);
        foreach ($attrVars as $contractVars) {
            if (isset($contractVars[0]) && $companyCreationDate->diff($today)->days < $contractVars[0]) {
                return false;
            }
        }

        return true;
    }

    public function isEligibleForContractRCS(\companies $company, \product $product, ProductAttributeManager $productAttributeManager)
    {
        $attrVars = $productAttributeManager->getProductContractAttributesByType($product, \underlying_contract_attribute_type::ELIGIBLE_BORROWER_COMPANY_RCS);
        foreach ($attrVars as $contractVars) {
            if (isset($contractVars[0]) && $contractVars[0] && empty($company->rcs)) {
                return false;
            }
        }

        return true;
    }
}
