<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\Validator;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Bids;
use Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContract;
use Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContractAttributeType;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\Checker\ClientChecker;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\ContractAttributeManager;

class BidValidator
{
    use ClientChecker;

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
    public function validate(Bids $bid, UnderlyingContract $contract)
    {
        $violations = [];

        if (false === $this->isEligibleForClientType($bid->getIdLenderAccount()->getIdClient(), $contract, $this->contractAttributeManager)) {
            $violations[] = UnderlyingContractAttributeType::ELIGIBLE_LENDER_TYPE;
        }

        return $violations;
    }
}
