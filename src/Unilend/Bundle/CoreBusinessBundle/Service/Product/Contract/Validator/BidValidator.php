<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\Validator;

use Doctrine\ORM\EntityManagerInterface;
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

    /** @var EntityManagerInterface */
    private $entityManager;

    /**
     * @param ContractAttributeManager $contractAttributeManager
     * @param EntityManagerInterface   $entityManager
     */
    public function __construct(ContractAttributeManager $contractAttributeManager, EntityManagerInterface $entityManager)
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

        if (false === $this->isEligibleForClientType($bid->getWallet()->getIdClient(), $contract, $this->contractAttributeManager)) {
            $violations[] = UnderlyingContractAttributeType::ELIGIBLE_CLIENT_TYPE;
        }

        return $violations;
    }
}
