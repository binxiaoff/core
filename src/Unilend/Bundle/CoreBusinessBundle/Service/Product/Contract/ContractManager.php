<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract;

use Unilend\Bundle\CoreBusinessBundle\Entity\Bids;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContract;
use Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContractAttributeType;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\Validator\AutoBidSettingsValidator;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\Validator\BidValidator;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\Validator\ClientValidator;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\Validator\ProjectValidator;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ContractAttributeManager;

class ContractManager
{
    /** @var  ClientValidator */
    private $clientValidator;

    /** @var BidValidator */
    private $bidValidator;

    /** @var ProjectValidator */
    private $projectValidator;

    /** @var AutoBidSettingsValidator */
    private $autoBidSettingsValidator;

    /** @var  ContractAttributeManager */
    private $contractAttributeManager;

    public function __construct(
        ClientValidator $clientValidator,
        BidValidator $bidValidator,
        AutoBidSettingsValidator $autoBidSettingsValidator,
        ProjectValidator $projectValidator,
        ContractAttributeManager $contractAttributeManager
    )
    {
        $this->clientValidator          = $clientValidator;
        $this->bidValidator             = $bidValidator;
        $this->autoBidSettingsValidator = $autoBidSettingsValidator;
        $this->projectValidator         = $projectValidator;
        $this->contractAttributeManager = $contractAttributeManager;
    }

    /**
     * @param Clients            $client
     * @param UnderlyingContract $contract
     *
     * @return bool
     */
    public function isLenderEligible(Clients $client, UnderlyingContract $contract)
    {
        return 0 === count($this->checkClientEligibility($client, $contract));
    }

    /**
     * @param Clients            $client
     * @param UnderlyingContract $contract
     *
     * @return array
     */
    public function checkClientEligibility(Clients $client, UnderlyingContract $contract)
    {
        return $this->clientValidator->validate($client, $contract);
    }

    /**
     * @param UnderlyingContract $contract
     *
     * @return bool
     */
    public function isAutobidSettingsEligible(UnderlyingContract $contract)
    {
        return 0 === count($this->autoBidSettingsValidator->validate($contract));
    }

    /**
     * @param UnderlyingContract $contract
     *
     * @return null
     */
    public function getMaxAmount(UnderlyingContract $contract)
    {
        $maxAmount = $this->contractAttributeManager->getContractAttributesByType($contract, UnderlyingContractAttributeType::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO);
        if (empty($maxAmount)) {
            return null;
        }

        return $maxAmount[0];
    }

    /**
     * @param \underlying_contract $contract
     * @param                      $attributeType
     *
     * @return array
     */
    public function getAttributesByType(\underlying_contract $contract, $attributeType)
    {
        return $this->contractAttributeManager->getContractAttributesByType($contract, $attributeType);
    }

    /**
     * @param Bids               $bid
     * @param UnderlyingContract $contract
     *
     * @return array
     */
    public function checkBidEligibility(Bids $bid, UnderlyingContract $contract)
    {
        return $this->bidValidator->validate($bid, $contract);
    }

    /**
     * @param Projects           $project
     * @param UnderlyingContract $contract
     *
     * @return array
     */
    public function checkProjectEligibility(Projects $project, UnderlyingContract $contract)
    {
        return $this->projectValidator->validate($project, $contract);
    }

    /**
     * @param UnderlyingContract $contract
     *
     * @return mixed|null
     */
    public function getMaxEligibleDuration(UnderlyingContract $contract)
    {
        $durationMax = $this->contractAttributeManager->getContractAttributesByType($contract, UnderlyingContractAttributeType::MAX_LOAN_DURATION_IN_MONTH);

        return empty($durationMax) ? null : $durationMax[0];
    }
}
