<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract;

use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ContractAttributeManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

class LenderValidator
{
    use Checker\LenderChecker;

    /** @var ContractAttributeManager */
    private $contractAttributeManager;
    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;

    public function __construct(ContractAttributeManager $contractAttributeManager, EntityManagerSimulator $entityManagerSimulator)
    {
        $this->contractAttributeManager = $contractAttributeManager;
        $this->entityManagerSimulator   = $entityManagerSimulator;
    }

    /**
     * @param Clients              $client
     * @param \underlying_contract $contract
     *
     * @return bool
     */
    public function isEligible(Clients $client, \underlying_contract $contract)
    {
        return $this->isEligibleForLenderType($client, $contract, $this->contractAttributeManager);
    }
}
