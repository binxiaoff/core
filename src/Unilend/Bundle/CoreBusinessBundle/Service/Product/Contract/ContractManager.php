<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract;

use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ContractAttributeManager;

class ContractManager
{
    /** @var  LenderValidator */
    private $lenderValidator;
    /** @var AutoBidSettingsValidator */
    private $autoBidSettingsValidator;
    /** @var  ContractAttributeManager */
    private $contractAttributeManager;

    public function __construct(
        LenderValidator $lenderValidator,
        AutoBidSettingsValidator $autoBidSettingsValidator,
        ContractAttributeManager $contractAttributeManager
    ) {
        $this->lenderValidator          = $lenderValidator;
        $this->autoBidSettingsValidator = $autoBidSettingsValidator;
        $this->contractAttributeManager = $contractAttributeManager;
    }

    /**
     * @param Clients              $client
     * @param \underlying_contract $contract
     *
     * @return bool
     * @throws \Exception
     */
    public function isLenderEligible(Clients $client, \underlying_contract $contract)
    {
        if (false === $client->isLender()) {
            throw new \Exception('Client ' . $client->getIdClient() . ' is not a Lender');
        }

        return $this->lenderValidator->isEligible($client, $contract);
    }

    public function isAutobidSettingsEligible(\underlying_contract $contract)
    {
        return $this->autoBidSettingsValidator->isEligible($contract);
    }

    public function getMaxAmount(\underlying_contract $contract)
    {
        $maxAmount = $this->contractAttributeManager->getContractAttributesByType($contract, \underlying_contract_attribute_type::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO);
        if (empty($maxAmount)) {
            return null;
        }

        return $maxAmount[0];
    }

    public function getAttributesByType(\underlying_contract $contract, $attributeType)
    {
        return $this->contractAttributeManager->getContractAttributesByType($contract, $attributeType);
    }
}
