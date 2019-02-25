<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Validator;

use Doctrine\ORM\EntityManagerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{Bids, ProductAttributeType, ProductUnderlyingContract, UnderlyingContractAttributeType};
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Checker\BidChecker;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\ContractManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductAttributeManager;

class BidValidator
{
    use BidChecker;

    /** @var ProductAttributeManager */
    private $productAttributeManager;
    /** @var ContractManager */
    private $contractManager;
    /** @var EntityManagerInterface */
    private $entityManager;

    /**
     * @param ProductAttributeManager $productAttributeManager
     * @param ContractManager         $contractManager
     * @param EntityManagerInterface  $entityManager
     */
    public function __construct(ProductAttributeManager $productAttributeManager, ContractManager $contractManager, EntityManagerInterface $entityManager)
    {
        $this->productAttributeManager = $productAttributeManager;
        $this->contractManager         = $contractManager;
        $this->entityManager           = $entityManager;
    }

    /**
     * @param Bids $bid
     *
     * @return array
     */
    public function validate(Bids $bid)
    {
        $violations = [];

        $product = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Product')->find($bid->getProject()->getIdProduct());
        $client  = $bid->getIdLenderAccount()->getIdClient();

        if (false === $this->isEligibleForClientId($client, $product, $this->productAttributeManager)) {
            $violations[] = ProductAttributeType::ELIGIBLE_CLIENT_ID;
        }

        if (false === $this->isEligibleForClientType($client, $product, $this->productAttributeManager)) {
            $violations[] = ProductAttributeType::ELIGIBLE_CLIENT_TYPE;
        }

        // Return the contract level reason, but the check is done in product level, as the max amount is the total of all contracts attached to a product.
        if (false === $this->isEligibleForMaxTotalAmount($bid, $this->contractManager, $this->entityManager)) {
            $violations[] = UnderlyingContractAttributeType::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO;
        }

        $hasEligibleContract = false;
        $violationsContract  = [];
        /** @var ProductUnderlyingContract $productContract */
        foreach ($product->getProductContract() as $productContract) {
            $contractCheckResult = $this->contractManager->checkBidEligibility($bid, $productContract->getIdContract());
            if (0 < count($contractCheckResult)) {
                $violationsContract = array_merge($violationsContract, $contractCheckResult);
            } else {
                $hasEligibleContract = true;
            }
        }

        if (false === $hasEligibleContract) {
            $violations = array_merge($violations, $violationsContract);
        }

        return $violations;
    }
}
