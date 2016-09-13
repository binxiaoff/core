<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Checker;

use Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductAttributeManager;

trait LenderChecker
{

    public function isLenderEligibleForNationality(\clients $client, \product $product, ProductAttributeManager $productAttributeManager)
    {
        $eligibleNationality = $productAttributeManager->getProductAttributesByType($product, \product_attribute_type::ELIGIBLE_LENDER_NATIONALITY);

        if (empty($eligibleNationality)) {
            return true;
        }

        return $client->id_nationalite == 0 || in_array($client->id_nationalite, $eligibleNationality);
    }

    public function isLenderEligibleForType(\clients $client, \product $product, ProductAttributeManager $productAttributeManager)
    {
        $attrVars = $productAttributeManager->getProductContractAttributesByType($product, \underlying_contract_attribute_type::ELIGIBLE_LENDER_TYPE);

        if (empty($attrVars)) {
            return true; // No limitation found!
        }

        $eligibleType = [];
        foreach ($attrVars as $contractAttr) {
            if (empty($contractAttr)) {
                return true; // No limitation found for one of the underlying contract!
            } else {
                $eligibleType = array_merge($eligibleType, $contractAttr);
            }
        }

        return in_array($client->type, $eligibleType);
    }
}
