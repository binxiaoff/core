<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Checker;

use Unilend\Bundle\CoreBusinessBundle\Service\Product\ContractAttributeManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductAttributeManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

trait BidChecker
{
    use LenderChecker;
    use \Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\Checker\BidChecker;
    use \Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\Checker\LenderChecker;
    /**
     * @param \bids                   $bid
     * @param \product                $product
     * @param ProductAttributeManager $productAttributeManager
     *
     * @return bool
     */
    public function isBidEligibleForMaxTotalAmount(\bids $bid, \product $product, ProductAttributeManager $productAttributeManager)
    {
        $totalAmount = $bid->getBidsEncours($bid->id_project, $bid->id_lender_account)['solde'];
        $bidAmount   = bcdiv($bid->amount, 100, 2);
        $totalAmount = bcadd($totalAmount, $bidAmount, 2);

        $maxAmountEligible = $this->getMaxEligibleAmount($product, $productAttributeManager);
        if (null === $maxAmountEligible) {
            return null;
        }

        return bccomp($maxAmountEligible, $totalAmount, 2) >= 0;
    }

    /**
     * @param \bids $bid
     * @param \lenders_accounts $lender
     * @param \product $product
     * @param EntityManager $entityManager
     * @param ContractAttributeManager $contractAttributeManager
     *
     * @return bool
     */
    public function isAutobidEligibleForMaxTotalAmount(\bids $bid, \lenders_accounts $lender, \product $product, EntityManager $entityManager, ContractAttributeManager $contractAttributeManager)
    {
        /** @var \product_underlying_contract $productContract */
        $productContract  = $entityManager->getRepository('product_underlying_contract');
        $productContracts = $productContract->getUnderlyingContractsByProduct($product->id_product);
        /** @var \underlying_contract $contract */
        $contract = $entityManager->getRepository('underlying_contract');

        $bidMaxAmount = 0;

        foreach ($productContracts as $underlyingContract) {
            $contract->get($underlyingContract['id_contract']);
            if ($this->isAutobidEligibleForAutobid($contract, $contractAttributeManager)) {
                if ($this->isEligibleForLenderType($lender, $contract, $entityManager, $contractAttributeManager)) {
                    $maxAmount = $contractAttributeManager->getContractAttributesByType($contract, \underlying_contract_attribute_type::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO);
                    if (empty($maxAmount)) {
                        continue;
                    }
                    $bidMaxAmount += $maxAmount[0];
                }
            }
        }

        return bcdiv($bid->amount, 100, 2) <= $bidMaxAmount;
    }
}
