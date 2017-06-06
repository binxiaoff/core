<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Checker;

use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProductAttributeType;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\ContractManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductAttributeManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

trait LenderChecker
{
    /**
     * @param Clients|int             $client
     * @param \product                $product
     * @param ProductAttributeManager $productAttributeManager
     *
     * @return bool
     */
    public function isEligibleForLenderId($client, \product $product, ProductAttributeManager $productAttributeManager)
    {
        $clientId   = $client instanceof Clients ? $client->getIdClient() : $client;
        $attributes = $productAttributeManager->getProductAttributesByType($product, ProductAttributeType::ELIGIBLE_LENDER_ID);

        if (empty($attributes)) {
            return true; // No limitation found
        }

        return in_array($clientId, $attributes);
    }

    /**
     * @param Clients|int             $client
     * @param \product                $product
     * @param ProductAttributeManager $productAttributeManager
     * @param EntityManager           $entityManager
     *
     * @return bool
     */
    public function isEligibleForLenderType($client, \product $product, ProductAttributeManager $productAttributeManager, EntityManager $entityManager)
    {
        $attributes = $productAttributeManager->getProductAttributesByType($product, ProductAttributeType::ELIGIBLE_LENDER_TYPE);

        if (empty($attributes)) {
            return true; // No limitation found
        }

        if ($client instanceof Clients) {
            $clientType = $client->getType();
        } else {
            /** @var \clients $clientData */
            $clientData = $entityManager->getRepository('clients');
            if (false === $clientData->get($client)) {
                throw new \InvalidArgumentException('The client id ' . $client . ' does not exist');
            }

            $clientType = $clientData->type;
        }

        return in_array($clientType, $attributes);
    }

    /**
     * @param \lenders_accounts       $lender
     * @param \product                $product
     * @param ProductAttributeManager $productAttributeManager
     * @param EntityManager           $entityManager
     *
     * @return bool
     */
    public function isContractEligibleForLenderType(\lenders_accounts $lender, \product $product, ProductAttributeManager $productAttributeManager, EntityManager $entityManager)
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

        $totalAmount       = $bid->getBidsEncours($project->id_project, $lender->id_lender_account)['solde'];
        $maxAmountEligible = $this->getMaxEligibleAmount($product, $productAttributeManager);

        if (null === $maxAmountEligible) {
            return null;
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
        $amountRest = $this->getAmountLenderCanStillBid($lender, $project, $productAttributeManager, $entityManager);
        return $amountRest === null || $amountRest > 0;
    }

    public function getMaxEligibleAmount(\product $product, ProductAttributeManager $productAttributeManager)
    {
        $attrVars = $productAttributeManager->getProductContractAttributesByType($product, \underlying_contract_attribute_type::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO);

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

        return $maxAmountEligible;
    }

    public function getAutobidMaxEligibleAmount(\lenders_accounts $lender, \product $product, EntityManager $entityManager, ContractManager $contractManager)
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
                if (null === $maxAmount) {
                    return null; // one of the contract has no limit, so no limit.
                }
                $bidMaxAmount += $maxAmount;
            }
        }

        return $bidMaxAmount;
    }
}
