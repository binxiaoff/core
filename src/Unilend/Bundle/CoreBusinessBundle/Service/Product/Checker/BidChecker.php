<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Checker;

use Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\ContractManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductAttributeManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

trait BidChecker
{
    use LenderChecker;

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
     * @param \bids             $bid
     * @param \lenders_accounts $lender
     * @param \product          $product
     * @param EntityManager     $entityManager
     * @param ContractManager   $contractManager
     *
     * @return bool
     */
    public function isAutobidEligibleForMaxTotalAmount(\bids $bid, \lenders_accounts $lender, \product $product, EntityManager $entityManager, ContractManager $contractManager)
    {
        /** @var \product_underlying_contract $productContract */
        $productContract  = $entityManager->getRepository('product_underlying_contract');
        $productContracts = $productContract->getUnderlyingContractsByProduct($product->id_product);
        /** @var \underlying_contract $contract */
        $contract = $entityManager->getRepository('underlying_contract');

        $bidMaxAmount = 0;

        foreach ($productContracts as $underlyingContract) {
            $contract->get($underlyingContract['id_contract']);
            if ($contractManager->isAutobidSettingsEligible($contract) && $contractManager->isLenderEligible($lender, $contract)) {
                $maxAmount = $contractManager->getMaxAmount($contract);
                if (empty($maxAmount)) {
                    return true; // one of the contract has no limit, so no limit.
                }
                $bidMaxAmount += $maxAmount;
            }
        }

        return bcdiv($bid->amount, 100, 2) <= $bidMaxAmount;
    }
}
