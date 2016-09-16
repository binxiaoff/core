<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service\Product;

use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class LenderValidator
{
    use Checker\LenderChecker;

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
     * @param \lenders_accounts $lender
     * @param \projects         $project
     *
     * @return bool
     */
    public function isEligible(\lenders_accounts $lender, \projects $project)
    {
        /** @var \product $product */
        $product = $this->entityManager->getRepository('product');
        if (false === $product->get($project->id_product)) {
            throw new \InvalidArgumentException('The product id ' . $project->id_product . ' does not exist');
        }

        foreach ($this->getAttributeTypeToCheck() as $attributeTypeToCheck) {
            $eligibility = $this->checkAttribute($lender, $project, $product, $attributeTypeToCheck);

            if (false === $eligibility) {
                return $eligibility;
            }
        }

        return true;
    }

    public function getReasons(\lenders_accounts $lender, \projects $project)
    {
        $reason = [];
        /** @var \product $product */
        $product = $this->entityManager->getRepository('product');
        if (false === $product->get($project->id_product)) {
            throw new \InvalidArgumentException('The product id ' . $project->id_product . ' does not exist');
        }

        foreach ($this->getAttributeTypeToCheck() as $attributeTypeToCheck) {
            $eligibility = $this->checkAttribute($lender, $project, $product, $attributeTypeToCheck);

            if (false === $eligibility) {
                $reason[] = $attributeTypeToCheck;
            }
        }

        return $reason;
    }

    private function checkAttribute(\lenders_accounts $lender, \projects $project, \product $product, $attributeTypeToCheck)
    {
        switch ($attributeTypeToCheck) {
            case \product_attribute_type::ELIGIBLE_LENDER_NATIONALITY :
                $eligibility = $this->isLenderEligibleForNationality($lender, $product, $this->productAttributeManager, $this->entityManager);
                break;
            case \underlying_contract_attribute_type::ELIGIBLE_LENDER_TYPE :
                $eligibility = $this->isLenderEligibleForType($lender, $product, $this->productAttributeManager, $this->entityManager);
                break;
            case \underlying_contract_attribute_type::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO :
                $eligibility = $this->isLenderEligibleForMaxTotalAmount($lender, $project, $this->productAttributeManager, $this->entityManager);
                break;
            default :
                $eligibility = false;
        }

        return $eligibility;
    }

    private function getAttributeTypeToCheck()
    {
        return [
            \product_attribute_type::ELIGIBLE_LENDER_NATIONALITY,
            \underlying_contract_attribute_type::ELIGIBLE_LENDER_TYPE,
            \underlying_contract_attribute_type::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO,
        ];
    }
}
