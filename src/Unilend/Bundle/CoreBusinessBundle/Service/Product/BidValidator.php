<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product;

use Unilend\Bundle\CoreBusinessBundle\Entity\ProductAttributeType;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\ContractManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class BidValidator
{
    use Checker\BidChecker;

    /** @var ProductAttributeManager */
    private $productAttributeManager;
    /** @var EntityManager */
    private $entityManager;
    /** @var ContractManager */
    private $contractManager;

    public function __construct(
        ProductAttributeManager $productAttributeManager,
        EntityManager $entityManager,
        ContractManager $contractManager
    )
    {
        $this->productAttributeManager = $productAttributeManager;
        $this->entityManager           = $entityManager;
        $this->contractManager         = $contractManager;
    }

    public function isEligible(\bids $bid)
    {
        $reason   = [];
        $eligible = true;

        /** @var \projects $project */
        $project = $this->entityManager->getRepository('projects');
        $project->get($bid->id_project);
        /** @var \product $product */
        $product = $this->entityManager->getRepository('product');
        $product->get($project->id_product);

        /** @var \lenders_accounts $lender */
        $lender = $this->entityManager->getRepository('lenders_accounts');
        if (false === $lender->get($bid->id_lender_account)) {
            throw new \InvalidArgumentException('The lender account id ' . $bid->id_lender_account . ' does not exist');
        }

        if (false === $this->isEligibleForLenderId($lender->id_client_owner, $product, $this->productAttributeManager)) {
            $reason[] = ProductAttributeType::ELIGIBLE_LENDER_ID;
            $eligible = false;
        }

        if (false === $this->isEligibleForLenderType($lender->id_client_owner, $product, $this->productAttributeManager, $this->entityManager)) {
            $reason[] = ProductAttributeType::ELIGIBLE_LENDER_TYPE;
            $eligible = false;
        }

        if (false === $this->isContractEligibleForLenderType($lender, $product, $this->productAttributeManager, $this->entityManager)) {
            $reason[] = \underlying_contract_attribute_type::ELIGIBLE_LENDER_TYPE;
            $eligible = false;
        }

        if (false === $this->isBidEligibleForMaxTotalAmount($bid, $product, $this->productAttributeManager)) {
            $reason[] = \underlying_contract_attribute_type::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO;
            $eligible = false;
        }

        if (false === empty($bid->id_autobid)) {
            if (false === $this->isAutobidEligibleForMaxTotalAmount($bid, $lender, $product, $this->entityManager, $this->contractManager)) {
                $reason[] = \underlying_contract_attribute_type::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO;
                $eligible = false;
            }
        }

        return [
            'reason'   => $reason,
            'eligible' => $eligible
        ];
    }
}
