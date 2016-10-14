<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\Checker;


use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ContractAttributeManager;


trait LenderChecker
{
    use ContractChecker;

    /**
     * @param EntityManager $entityManager
     * @param ContractAttributeManager $contractAttributeManager
     * @param \lenders_accounts $lender
     * @param \underlying_contract $contract
     *
     * @return bool
     */
    public function isLenderTypeEligibleForContract(EntityManager $entityManager, ContractAttributeManager $contractAttributeManager ,\lenders_accounts $lender, \underlying_contract $contract)
    {
        /** @var \clients $client */
        $client = $entityManager->getRepository('clients');
        if (false === $client->get($lender->id_client_owner)) {
            throw new \InvalidArgumentException('The client id ' . $lender->id_client_owner . ' does not exist');
        }

        $attrVars = $contractAttributeManager->getContractAttributesByType($contract, \underlying_contract_attribute_type::ELIGIBLE_LENDER_TYPE);
        if (empty($attrVars)) {
            return true; // No limitation found!
        }

        return in_array($client->type, $attrVars);
    }

    /**
     * @param EntityManager $entityManager
     * @param ContractAttributeManager $contractAttributeManager
     * @param \lenders_accounts $lender
     * @param \underlying_contract $contract
     *
     * @return bool
     */
    public function isLenderEligibleForAutobid(EntityManager $entityManager, ContractAttributeManager $contractAttributeManager ,\lenders_accounts $lender, \underlying_contract $contract)
    {
        return ($this->isLenderTypeEligibleForContract($entityManager, $contractAttributeManager, $lender, $contract) && $this->isContractAutobidEligible($contract, $contractAttributeManager));
    }

    /**
     * @param \lenders_accounts $lender
     * @param \product $product
     * @param EntityManager $entityManager
     * @param ContractAttributeManager $contractAttributeManager
     *
     * @return int
     */
    public function getMaxEligibleAutobidAmount(\lenders_accounts $lender, \product $product, EntityManager $entityManager, ContractAttributeManager $contractAttributeManager)
    {
        /** @var \product_underlying_contract $productContract */
        $productContract = $entityManager->getRepository('product_underlying_contract');
        $productContracts = $productContract->getUnderlyingContractsByProduct($product->id_product);

        /** @var \underlying_contract $contract */
        $contract = $entityManager->getRepository('underlying_contract');

        $bidMaxAmount = 0;

        foreach ($productContracts as $underlyingContract) {
            $contract->get($underlyingContract['id_contract']);
            if ($this->isContractAutobidEligible($contract, $this->contractAttributeManager)) {
                if ($this->isLenderTypeEligibleForContract($this->entityManager, $this->contractAttributeManager, $lender, $contract)) {
                    $maxAmount = $contractAttributeManager->getContractAttributesByType($contract, \underlying_contract_attribute_type::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO);
                    if (false === empty($maxAmount)) {
                        $bidMaxAmount =+ $maxAmount[0];
                    }
                }
            }
        }

        return $bidMaxAmount;
    }

}
