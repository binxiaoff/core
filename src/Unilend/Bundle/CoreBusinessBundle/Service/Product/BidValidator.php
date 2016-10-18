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
        $this->productAttributeManager = $productAttributeManager;
        $this->entityManager           = $entityManager;
        $this->contractManager         = $contractManager;
    }

    public function isEligible(\bids $bid, \product $product)
    {
        $reason = [];
        $eligible = true;

        /** @var \lenders_accounts $lender */
        $lender = $this->entityManager->getRepository('lenders_accounts');
        if (false === $lender->get($bid->id_lender_account)) {
            throw new \InvalidArgumentException('The lender account id ' . $bid->id_lender_account . ' does not exist');
        }

        if (false === $this->isLenderEligibleForType($lender, $product, $this->productAttributeManager, $this->entityManager)) {
            $reason[] = \underlying_contract_attribute_type::ELIGIBLE_LENDER_TYPE;
            $eligible = false;
        }

        if (false === $this->isBidEligibleForMaxTotalAmount($bid, $product, $this->productAttributeManager)) {
            $reason[] = \underlying_contract_attribute_type::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO;
            $eligible = false;
        }

        if (false === empty($bid->id_autobid)) {
            if (false === $this->isAutobidEligibleForMaxTotalAmount($bid, $lender, $product, $this->entityManager, $this->contractManager)) {
                $reason[] = \underlying_contract_attribute_type::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO;
                $eligible = false;
            }
        }

        return [
            'reason' => $reason,
            'eligible' => $eligible
        ];
    }
}
