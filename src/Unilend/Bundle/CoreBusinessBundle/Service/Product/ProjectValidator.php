<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service\Product;

use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class ProjectValidator
{
    use Checker\ProjectChecker;

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
        /** @var \companies $company */
        $company = $this->entityManager->getRepository('companies');
        $company->get($projects->id_company);

        foreach ($this->getAttributeTypeToCheck() as $attributeTypeToCheck) {
            switch ($attributeTypeToCheck) {
                case \product_attribute_type::MIN_LOAN_DURATION_IN_MONTH :
                    $eligibility = $this->isProductEligibleForMinDuration($projects, $product, $this->productAttributeManager);
                    break;
                case \product_attribute_type::MAX_LOAN_DURATION_IN_MONTH :
                    $eligibility = $this->isProductEligibleForMaxDuration($projects, $product, $this->productAttributeManager);
                    break;
                case \product_attribute_type::ELIGIBLE_BORROWING_MOTIVE :
                    $eligibility = $this->isProductEligibleForMotive($projects, $product, $this->productAttributeManager);
                    break;
                case \underlying_contract_attribute_type::MAX_LOAN_DURATION_IN_MONTH :
                    $eligibility = $this->isProductEligibleForMaxContractDuration($projects, $product, $this->productAttributeManager);
                    break;
                case \product_attribute_type::MIN_CREATION_DAYS :
                    $eligibility = $this->isEligibleForCreationDays($company, $product, $this->productAttributeManager);
                    break;
                case \product_attribute_type::ELIGIBLE_BORROWER_COMPANY_RCS :
                    $eligibility = $this->isEligibleForRCS($company, $product, $this->productAttributeManager);
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
            \product_attribute_type::ELIGIBLE_BORROWING_MOTIVE,
            \product_attribute_type::MIN_LOAN_DURATION_IN_MONTH,
            \product_attribute_type::MAX_LOAN_DURATION_IN_MONTH,
            \underlying_contract_attribute_type::MAX_LOAN_DURATION_IN_MONTH,
            \product_attribute_type::MIN_CREATION_DAYS,
            \product_attribute_type::ELIGIBLE_BORROWER_COMPANY_RCS
        ];
    }
}
