<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service\Product;

use Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\ContractManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class BidValidator
{
    use Checker\BidChecker;

    /** @var ProductAttributeManager */
    private $productAttributeManager;
    /** @var EntityManager */
    private $entityManager;
    /** @var ContractManager */
    private $contractManager;

    public function __construct(
        ProductAttributeManager $productAttributeManager,
        EntityManager $entityManager,
        ContractManager $contractManager
    ) {
        $this->productAttributeManager  = $productAttributeManager;
        $this->entityManager            = $entityManager;
        $this->contractManager          = $contractManager;
    }

    public function isEligible(\bids $bid, \product $product)
    {
        /** @var \lenders_accounts $lender */
        $lender = $this->entityManager->getRepository('lenders_accounts');
        if (false === $lender->get($bid->id_lender_account)) {
            throw new \InvalidArgumentException('The lender account id ' . $bid->id_lender_account . ' does not exist');
        }

        foreach ($this->getAttributeTypeToCheck() as $attributeTypeToCheck) {
           $eligibility = $this->checkAttribute($bid, $lender, $product, $attributeTypeToCheck);
            if (false === $eligibility) {
                return $eligibility;
            }
        }

        if (false === empty($bid->id_autobid)) {
            $autobidEligibility = $this->isAutobidEligible($bid, $product, $lender);
            return $autobidEligibility;
        }

        return true;
    }

    private function isAutobidEligible(\bids $bid, \product $product, \lenders_accounts $lender)
    {
        $this->isAutobidEligibleForMaxTotalAmount($bid, $lender, $product, $this->entityManager, $this->contractManager);
    }

    public function getReasons(\bids $bid, \product $product)
    {
        $reason = [];
        /** @var \lenders_accounts $lender */
        $lender = $this->entityManager->getRepository('lenders_accounts');
        if (false === $lender->get($bid->id_lender_account)) {
            throw new \InvalidArgumentException('The lender account id ' . $bid->id_lender_account . ' does not exist');
        }

        foreach ($this->getAttributeTypeToCheck() as $attributeTypeToCheck) {
            $eligibility = $this->checkAttribute($bid, $lender, $product, $attributeTypeToCheck);

            if (false === $eligibility) {
                $reason[] = $attributeTypeToCheck;
            }
        }

        return $reason;
    }

    private function checkAttribute(\bids $bid, \lenders_accounts $lender, \product $product, $attributeTypeToCheck)
    {
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
