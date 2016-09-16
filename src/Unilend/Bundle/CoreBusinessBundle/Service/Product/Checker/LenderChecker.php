<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Checker;

use Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductAttributeManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

trait LenderChecker
{

    public function isLenderEligibleForNationality(\lenders_accounts $lender, \product $product, ProductAttributeManager $productAttributeManager, EntityManager $entityManager)
    {
        /** @var \clients $client */
        $client = $entityManager->getRepository('clients');
        if (false === $client->get($lender->id_client_owner)) {
            throw new \InvalidArgumentException('The client id ' . $lender->id_client_owner . ' does not exist');
        }

        $eligibleNationality = $productAttributeManager->getProductAttributesByType($product, \product_attribute_type::ELIGIBLE_LENDER_NATIONALITY);

        if (empty($eligibleNationality)) {
            return true;
        }

        return $client->id_nationalite == 0 || in_array($client->id_nationalite, $eligibleNationality);
    }

    public function isLenderEligibleForType(\lenders_accounts $lender, \product $product, ProductAttributeManager $productAttributeManager, EntityManager $entityManager)
    {
        /** @var \clients $client */
        $client = $entityManager->getRepository('clients');
        if (false === $client->get($lender->id_client_owner)) {
            throw new \InvalidArgumentException('The client id ' . $lender->id_client_owner . ' does not exist');
        }

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

    /**
     * Get the amount that the lender can bid compare to the max limit loan amount.
     * For example, the max loan amount for IFP is 2000 €, and a lender has 1500 € pending bid(s), the method will return 500.
     *
     * @param \lenders_accounts       $lender
     * @param \projects               $project
     * @param ProductAttributeManager $productAttributeManager
     * @param EntityManager           $entityManager
     *
     * @return null if no limitation, float if
     */
    public function getAmountLenderCanStillBid(\lenders_accounts $lender, \projects $project, ProductAttributeManager $productAttributeManager, EntityManager $entityManager)
    {
        /** @var \bids $bid */
        $bid = $entityManager->getRepository('bids');
        /** @var \product $product */
        $product = $entityManager->getRepository('product');
        $product->get($project->id_product);

        $totalAmount = $bid->getBidsEncours($project->id_project, $lender->id_lender_account)['solde'];

        $attrVars    = $productAttributeManager->getProductContractAttributesByType($product, \underlying_contract_attribute_type::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO);

        if (empty($attrVars)) {
            return null; // No limitation found!
        }

        $maxAmountEligible = 0;
        foreach ($attrVars as $contractAttr) {
            if (empty($contractAttr)) {
                return null; // No limitation found for one of the underlying contract!
            } else {
                $maxAmountEligible = bccomp($contractAttr[0], $maxAmountEligible, 2) === 1 ? $contractAttr[0] : $maxAmountEligible;
            }
        }

        return bcsub($maxAmountEligible, $totalAmount, 2);
    }

    /**
     * @param \lenders_accounts       $lender
     * @param \projects               $project
     * @param ProductAttributeManager $productAttributeManager
     * @param EntityManager           $entityManager
     *
     * @return bool
     */
    public function isLenderEligibleForMaxTotalAmount(\lenders_accounts $lender, \projects $project, ProductAttributeManager $productAttributeManager, EntityManager $entityManager)
    {
        return $this->getAmountLenderCanStillBid($lender, $project, $productAttributeManager, $entityManager) > 0;
    }
}
