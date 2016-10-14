<?php


namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract;


use Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\Checker\BidChecker;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ContractAttributeManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class BidValidator
{
    use BidChecker;

    /** @var  EntityManager */
    private $entityManager;

    /** @var  ContractAttributeManager */
    private $contractAttributeManager;

    public function __construct(EntityManager $entityManager, ContractAttributeManager $contractAttributeManager)
    {
        $this->entityManager            = $entityManager;
        $this->contractAttributeManager = $contractAttributeManager;
    }

    public function isBidAutobidEligible(\bids $bid, \product $product, \lenders_accounts $lender)
    {
        return $this->isAutobidEligible($lender, $bid, $product, $this->entityManager, $this->contractAttributeManager);
    }
}
