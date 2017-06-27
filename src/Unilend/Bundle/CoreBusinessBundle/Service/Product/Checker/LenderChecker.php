<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Checker;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Bids;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProductAttributeType;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\ContractManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductAttributeManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

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
     * @param Clients                 $client
     * @param \product                $product
     * @param ProductAttributeManager $productAttributeManager
     * @param EntityManager           $entityManager
     *
     * @return bool
     */
    public function isContractEligibleForLenderType(Clients $client, \product $product, ProductAttributeManager $productAttributeManager, EntityManager $entityManager)
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

        return in_array($client->getType(), $eligibleType);
    }

    /**
     * Get the amount that the lender can bid compare to the max limit loan amount.
     * For example, the max loan amount for IFP is 2000 €, and a lender has 1500 € pending bid(s), the method will return 500.
     *
     *
     * @param Clients                 $client
     * @param \projects               $project
     * @param ProductAttributeManager $productAttributeManager
     * @param EntityManagerSimulator  $entityManagerSimulator
     * @param EntityManager           $entityManager;
     *
     * @return null|string
     *
     * @throws \Exception
     */
    public function getAmountLenderCanStillBid(
        Clients $client,
        \projects $project,
        ProductAttributeManager $productAttributeManager,
        EntityManagerSimulator $entityManagerSimulator,
        EntityManager $entityManager
    )
    {
        if (false === $client->isLender()) {
            throw new \Exception('Client ' . $client->getIdClient() . ' is not a Lender');
        }
        $wallet = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);

        /** @var \bids $bid */
        $bid = $entityManagerSimulator->getRepository('bids');
        /** @var \product $product */
        $product = $entityManagerSimulator->getRepository('product');
        $product->get($project->id_product);

        $totalAmount       = $entityManager->getRepository('UnilendCoreBusinessBundle:Bids')->getSumByWalletAndProjectAndStatus($wallet, $bid->id_project, Bids::STATUS_BID_PENDING);
        $maxAmountEligible = $this->getMaxEligibleAmount($product, $productAttributeManager);

        if (null === $maxAmountEligible) {
            return null;
        }

        return bcsub($maxAmountEligible, $totalAmount, 2);
    }

    /**
     * @param Clients                 $client
     * @param \projects               $project
     * @param ProductAttributeManager $productAttributeManager
     * @param EntityManagerSimulator  $entityManagerSimulator
     * @param EntityManager           $entityManager;
     *
     * @return bool
     */
    public function isLenderEligibleForMaxTotalAmount(Clients $client, \projects $project, ProductAttributeManager $productAttributeManager, EntityManagerSimulator $entityManagerSimulator, EntityManager $entityManager)
    {
        $amountRest = $this->getAmountLenderCanStillBid($client, $project, $productAttributeManager, $entityManagerSimulator, $entityManager);
        return $amountRest === null || $amountRest > 0;
    }

    /**
     * @param                         \product $product
     * @param ProductAttributeManager $productAttributeManager
     *
     * @return int|null
     */
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

    /**
     * @param Clients                  $client
     * @param \product                 $product
     * @param EntityManagerSimulator   $entityManagerSimulator
     * @param ContractManager          $contractManager
     *
     * @return int|null
     */
    public function getAutobidMaxEligibleAmount(Clients $client, \product $product, EntityManagerSimulator $entityManagerSimulator, ContractManager $contractManager)
    {
        /** @var \product_underlying_contract $productContract */
        $productContract  = $entityManagerSimulator->getRepository('product_underlying_contract');
        $productContracts = $productContract->getUnderlyingContractsByProduct($product->id_product);
        /** @var \underlying_contract $contract */
        $contract = $entityManagerSimulator->getRepository('underlying_contract');

        $bidMaxAmount = 0;

        foreach ($productContracts as $underlyingContract) {
            $contract->get($underlyingContract['id_contract']);
            if ($contractManager->isAutobidSettingsEligible($contract) && $contractManager->isLenderEligible($client, $contract)) {
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
