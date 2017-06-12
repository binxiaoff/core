<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Checker;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Bids;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\Product;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\ContractManager;

trait LenderChecker
{
    /**
     * Get the amount that the lender can bid compare to the max limit loan amount.
     * For example, the max loan amount for IFP is 2000 €, and a lender has 1500 € pending bid(s), the method will return 500.
     *
     *
     * @param Clients         $client
     * @param Projects        $project
     * @param ContractManager $contractManager
     * @param EntityManager   $entityManager
     *
     * @return null|string
     *
     * @throws \Exception
     */
    public function getAmountLenderCanStillBid(Clients $client, Projects $project, ContractManager $contractManager, EntityManager $entityManager)
    {
        $wallet  = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($client, WalletType::LENDER);
        $product = $entityManager->getRepository('UnilendCoreBusinessBundle:Product')->find($project->getIdProduct());

        $totalAmount       = $entityManager->getRepository('UnilendCoreBusinessBundle:Bids')->getSumByWalletAndProjectAndStatus($wallet, $project->getIdProject(), Bids::STATUS_BID_PENDING);
        $maxAmountEligible = $this->getMaxEligibleAmount($client, $product, $contractManager, false);

        if (null === $maxAmountEligible) {
            return null;
        }

        return bcsub($maxAmountEligible, $totalAmount, 2);
    }

    /**
     * @param Clients|null    $client
     * @param Projects        $project
     * @param ContractManager $contractManager
     * @param EntityManager   $entityManager
     *
     * @return bool
     */
    public function canStillBid(Clients $client = null, Projects $project, ContractManager $contractManager, EntityManager $entityManager)
    {
        if (null === $client) {
            return false;
        }

        $amountRest = $this->getAmountLenderCanStillBid($client, $project, $contractManager, $entityManager);
        return $amountRest === null || $amountRest > 0;
    }

    /**
     * @param Clients         $client
     * @param Product         $product
     * @param ContractManager $contractManager
     * @param boolean         $isAutobid
     *
     * @return int|null
     */
    public function getMaxEligibleAmount(Clients $client, Product $product, ContractManager $contractManager, $isAutobid)
    {
        $maxAmountEligible = 0;

        foreach ($product->getIdContract() as $contract) {
            if (
                $contractManager->isClientEligible($client, $contract)
                && (false === $isAutobid || $contractManager->isAutobidSettingsEligible($contract))
            ) {
                $maxAmount = $contractManager->getMaxAmount($contract);

                if (null === $maxAmount) {
                    return null; // one of the contract has no limit, so no limit.
                }
                $maxAmountEligible += $maxAmount;
            }
        }

        return $maxAmountEligible;
    }
}
