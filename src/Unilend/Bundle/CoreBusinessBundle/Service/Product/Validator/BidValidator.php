<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Validator;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Bids;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProductAttributeType;
use Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContractAttributeType;
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
    /** @var EntityManager */
    private $entityManager;

    public function __construct(
        ProductAttributeManager $productAttributeManager,
        ContractManager $contractManager,
        EntityManager $entityManager
    )
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

        if (false === $this->isEligibleForClientId($bid->getIdLenderAccount()->getIdClient(), $product, $this->productAttributeManager)) {
            $violations[] = ProductAttributeType::ELIGIBLE_LENDER_ID;
        }

        if (false === $this->isEligibleForClientType($bid->getIdLenderAccount()->getIdClient(), $product, $this->productAttributeManager)) {
            $violations[] = ProductAttributeType::ELIGIBLE_LENDER_TYPE;
        }

        // Return the contract level reason, but the check is done in product level, as the max amount is the total of all contracts attached to a product.
        if (false === $this->isEligibleForMaxTotalAmount($bid, $product, $this->contractManager)) {
            $violations[] = UnderlyingContractAttributeType::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO;
        }

        $hasEligibleContract = false;
        $violationsContract  = [];
        foreach ($product->getIdContract() as $contract) {
            $contractCheckResult = $this->contractManager->checkBidEligibility($bid, $contract);
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
