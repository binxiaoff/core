<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Checker;

use Doctrine\ORM\EntityManagerInterface;
use Unilend\Entity\{Bids, Clients, Product, Projects, Wallet, WalletType};
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\ContractManager;

trait LenderChecker
{
    /**
     * Get the amount that the lender can bid compare to the max limit loan amount.
     * For example, the max loan amount for IFP is 2000 €, and a lender has 1500 € pending bid(s), the method will return 500.
     *
     *
     * @param Clients                $client
     * @param Projects               $project
     * @param ContractManager        $contractManager
     * @param EntityManagerInterface $entityManager
     * @param bool                   $isAutoBid
     *
     * @return null|string
     *
     * @throws \Exception
     */
    public function getAmountLenderCanStillBid(Clients $client, Projects $project, ContractManager $contractManager, EntityManagerInterface $entityManager, $isAutoBid = false)
    {
        $wallet  = $entityManager->getRepository(Wallet::class)->getWalletByType($client, WalletType::LENDER);
        $product = $entityManager->getRepository(Product::class)->find($project->getIdProduct());

        $totalAmount       = $entityManager->getRepository(Bids::class)
            ->getSumByWalletAndProjectAndStatus($wallet, $project->getIdProject(), [Bids::STATUS_PENDING]);
        $maxAmountEligible = $this->getMaxEligibleAmount($client, $product, $contractManager, $isAutoBid);

        if (null === $maxAmountEligible) {
            return null;
        }

        return bcsub($maxAmountEligible, $totalAmount, 2);
    }

    /**
     * @param Clients|null           $client
     * @param Projects               $project
     * @param ContractManager        $contractManager
     * @param EntityManagerInterface $entityManager
     *
     * @return bool
     */
    public function canStillBid(?Clients $client, Projects $project, ContractManager $contractManager, EntityManagerInterface $entityManager)
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

        foreach ($product->getProductContract() as $productContract) {
            $contract = $productContract->getIdContract();
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
