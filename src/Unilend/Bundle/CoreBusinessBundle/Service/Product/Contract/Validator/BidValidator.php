<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\Validator;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Bids;
use Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContract;
use Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContractAttributeType;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\Checker\LenderChecker;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ContractAttributeManager;

class BidValidator
{
    use LenderChecker;

    /** @var ContractAttributeManager */
    private $contractAttributeManager;

    /** @var EntityManager */
    private $entityManager;

    public function __construct(ContractAttributeManager $contractAttributeManager, EntityManager $entityManager)
    {
        $this->contractAttributeManager = $contractAttributeManager;
        $this->entityManager            = $entityManager;
    }

    /**
     * @param Bids               $bid
     * @param UnderlyingContract $contract
     *
     * @return array
     */
    public function valid(Bids $bid, UnderlyingContract $contract)
    {
        $violations = [];

        if (false === $this->isEligibleForLenderType($bid->getIdLenderAccount()->getIdClient(), $contract, $this->contractAttributeManager)) {
            $violations[] = UnderlyingContractAttributeType::ELIGIBLE_LENDER_TYPE;
        }

        return $violations;
    }
}
