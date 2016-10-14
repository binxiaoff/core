<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract;


use Unilend\Bundle\CoreBusinessBundle\Service\Product\ContractAttributeManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class ContractManager
{
    /** @var  EntityManager */
    private $entityManager;
    /** @var  LenderValidator*/
    private $lenderValidator;
    /** @var  ContractAttributeManager */
    private $contractAttributeManager;
    /** @var BidValidator */
    private $bidValidator;
    /** @var  ContractValidator */
    private $contractValidator;

    public function __construct(
        EntityManager $entityManager,
        LenderValidator $lenderValidator,
        BidValidator $bidValidator,
        ContractValidator $contractValidator
    ) {
        $this->entityManager            = $entityManager;
        $this->lenderValidator          = $lenderValidator;
        $this->bidValidator             = $bidValidator;
        $this->contractValidator        = $contractValidator;
    }

    /**
     * @param \lenders_accounts $lender
     * @param \underlying_contract $contract
     *
     * @return bool
     */
    public function isLenderEligibleForContract(\lenders_accounts $lender, \underlying_contract $contract)
    {
        return $this->lenderValidator->isContractEligible($lender, $contract);
    }

    /**
     * @param \bids $bid
     * @param \product $product
     * @param \lenders_accounts $lender
     *
     * @return bool
     */
    public function isBidAutobidEligible(\bids $bid, \product $product, \lenders_accounts $lender)
    {
        return $this->bidValidator->isBidAutobidEligible($bid, $product, $lender);
    }

    /**
     * @param \underlying_contract $contract
     *
     * @return null|int
     */
    public function getContractMaxAmount(\underlying_contract $contract)
    {
        return $this->contractValidator->getMaximumAmount($contract);
    }

    /**
     * @param array $contracts
     * @return \underlying_contract[]
     */
    public function getAutobidEligibleContracts(array $contracts)
    {
        /** @var \underlying_contract $contract */
        $contract = $this->entityManager->getRepository('underlying_contract');

        $autobidContracts = [];

        foreach($contracts as $underlyingContract) {
            $contract->get($underlyingContract['id_contract']);
            if ($this->contractValidator->isAutobidEligible($contract)) {
                $autobidContract = clone $contract;
                $autobidContracts[] = $autobidContract;
            }
        }

        return $autobidContracts;
    }

    /**
     * @param \product $product
     *
     * @return array
     */
    public function getProductAvailableContracts(\product $product)
    {
        /** @var \product_underlying_contract $productContract */
        $productContract = $this->entityManager->getRepository('product_underlying_contract');
        return $productContract->getUnderlyingContractsByProduct($product->id_product);
    }
}
