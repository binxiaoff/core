<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service\Product;

use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class BidValidator
{
    use Checker\LenderChecker;
    use Checker\BidChecker;

    /** @var ProductAttributeManager */
    private $productAttributeManager;
    /** @var EntityManager */
    private $entityManager;

    public function __construct(ProductAttributeManager $productAttributeManager, EntityManager $entityManager)
    {
        $this->productAttributeManager = $productAttributeManager;
        $this->entityManager = $entityManager;
    }

    public function isEligible(\bids $bid, \product $product)
    {
        /** @var \lenders_accounts $lender */
        $lender = $this->entityManager->getRepository('lenders_accounts');
        if (false === $lender->get($bid->id_lender_account)) {
            throw new \InvalidArgumentException('The lender account id ' . $bid->id_lender_account . ' does not exist');
        }

        foreach ($this->getAttributeTypeToCheck() as $attributeTypeToCheck) {
            switch ($attributeTypeToCheck) {
                case \product_attribute_type::ELIGIBLE_LENDER_NATIONALITY :
                    $eligibility = $this->isLenderEligibleForNationality($lender, $product, $this->productAttributeManager, $this->entityManager);
                    break;
                case \underlying_contract_attribute_type::ELIGIBLE_LENDER_TYPE :
                    $eligibility = $this->isLenderEligibleForType($lender, $product, $this->productAttributeManager, $this->entityManager);
                    break;
                case \underlying_contract_attribute_type::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO :
                    $eligibility = $this->isBidEligibleForMaxTotalAmount($bid, $product, $this->productAttributeManager);
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
            \product_attribute_type::ELIGIBLE_LENDER_NATIONALITY,
            \underlying_contract_attribute_type::ELIGIBLE_LENDER_TYPE,
            \underlying_contract_attribute_type::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO,
        ];
    }
}
