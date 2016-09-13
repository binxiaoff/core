<?php
/**
 * Created by PhpStorm.
 * User: binxiao
 * Date: 13/09/2016
 * Time: 12:37
 */

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Checker;


trait BidChecker
{
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

        $attrVars    = $productAttributeManager->getProductContractAttributesByType($product, \underlying_contract_attribute_type::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO);

        if (empty($attrVars)) {
            return true; // No limitation found!
        }

        $maxAmountEligible = 0;
        foreach ($attrVars as $contractAttr) {
            if (empty($contractAttr)) {
                return true; // No limitation found for one of the underlying contract!
            } else {
                $maxAmountEligible = bccomp($contractAttr[0], $maxAmountEligible, 2) === 1 ? $contractAttr[0] : $maxAmountEligible;
            }
        }

        return bccomp($maxAmountEligible, $totalAmount, 2) >= 0;
    }
}