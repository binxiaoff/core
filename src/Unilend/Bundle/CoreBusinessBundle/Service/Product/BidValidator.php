<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service\Product;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Bids;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\ContractManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

class BidValidator
{
    use Checker\BidChecker;

    /** @var ProductAttributeManager */
    private $productAttributeManager;
    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;
    /** @var ContractManager */
    private $contractManager;
    /** @var EntityManager */
    private $entityManager;

    public function __construct(
        ProductAttributeManager $productAttributeManager,
        EntityManagerSimulator $entityManagerSimulator,
        ContractManager $contractManager,
        EntityManager $entityManager
    )
    {
        $this->productAttributeManager = $productAttributeManager;
        $this->entityManagerSimulator  = $entityManagerSimulator;
        $this->contractManager         = $contractManager;
        $this->entityManager           = $entityManager;
    }

    /**
     * @param Bids $bid
     *
     * @return array
     */
    public function isEligible(Bids $bid)
    {
        $reason = [];
        $eligible = true;

        /** @var \product $product */
        $product = $this->entityManagerSimulator->getRepository('product');
        $product->get($bid->getProject()->getIdProduct());

        if (WalletType::LENDER !== $bid->getIdLenderAccount()->getIdType()->getLabel()) {
            throw new \InvalidArgumentException('The wallet for client ' . $bid->getIdLenderAccount()->getIdClient()->getIdClient() . ' is no lender wallet ');
        }

        if (false === $this->isLenderEligibleForType($bid->getIdLenderAccount()->getIdClient(), $product, $this->productAttributeManager)) {
            $reason[] = \underlying_contract_attribute_type::ELIGIBLE_LENDER_TYPE;
            $eligible = false;
        }

        if (false === $this->isBidEligibleForMaxTotalAmount($bid, $product, $this->productAttributeManager, $this->entityManager)) {
            $reason[] = \underlying_contract_attribute_type::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO;
            $eligible = false;
        }

        if ($bid->getAutobid()) {
            if (false === $this->isAutobidEligibleForMaxTotalAmount($bid, $product, $this->entityManagerSimulator, $this->contractManager)) {
                $reason[] = \underlying_contract_attribute_type::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO;
                $eligible = false;
            }
        }

        return [
            'reason' => $reason,
            'eligible' => $eligible
        ];
    }
}
