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
        foreach ($this->getAttributeTypeToCheck() as $attributeTypeToCheck) {
            switch ($attributeTypeToCheck) {
                case \product_attribute_type::MIN_LOAN_DURATION_IN_MONTH :
                    $eligibility = $this->isProductEligibleForMinDuration($projects, $product, $this->productAttributeManager);
                    break;
                case \product_attribute_type::MAX_LOAN_DURATION_IN_MONTH :
                    $eligibility = $this->isProductEligibleForMaxDuration($projects, $product, $this->productAttributeManager);
                    break;
                case \product_attribute_type::ELIGIBLE_NEED :
                    $eligibility = $this->isProductEligibleForNeed($projects, $product, $this->productAttributeManager);
                    break;
                case \underlying_contract_attribute_type::MAX_LOAN_DURATION_IN_MONTH :
                    $eligibility = $this->isProductEligibleForMaxContractDuration($projects, $product, $this->productAttributeManager);
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
            \product_attribute_type::ELIGIBLE_NEED,
            \product_attribute_type::MIN_LOAN_DURATION_IN_MONTH,
            \product_attribute_type::MAX_LOAN_DURATION_IN_MONTH,
            \underlying_contract_attribute_type::MAX_LOAN_DURATION_IN_MONTH
        ];
    }
}
