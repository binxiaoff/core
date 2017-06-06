<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProductAttributeType;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

class ClientValidator
{
    use Checker\LenderChecker;

    /** @var ProductAttributeManager */
    private $productAttributeManager;
    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;
    /** @var EntityManager */
    private $entityManager;

    /**
     * ClientValidator constructor.
     * @param ProductAttributeManager $productAttributeManager
     * @param EntityManagerSimulator  $entityManagerSimulator
     * @param EntityManager           $entityManager
     */
    public function __construct(
        ProductAttributeManager $productAttributeManager,
        EntityManagerSimulator $entityManagerSimulator,
        EntityManager $entityManager
    )
    {
        $this->productAttributeManager = $productAttributeManager;
        $this->entityManagerSimulator  = $entityManagerSimulator;
        $this->entityManager           = $entityManager;
    }

    /**
     * @param Clients   $client
     * @param \projects $project
     *
     * @return array
     */
    public function isEligible(Clients $client, \projects $project)
    {
        $eligible = true;
        $reason   = [];
        /** @var \product $product */
        $product = $this->entityManagerSimulator->getRepository('product');
        if (false === $product->get($project->id_product)) {
            throw new \InvalidArgumentException('The product id ' . $project->id_product . ' does not exist');
        }

        if (false === $this->isEligibleForLenderId($client->getIdClient(), $product, $this->productAttributeManager)) {
            $reason[] = ProductAttributeType::ELIGIBLE_LENDER_ID;
            $eligible = false;
        }

        if (false === $this->isEligibleForLenderType($client->getIdClient(), $product, $this->productAttributeManager, $this->entityManager)) {
            $reason[] = ProductAttributeType::ELIGIBLE_LENDER_TYPE;
            $eligible = false;
        }

        if (false === $this->isContractEligibleForLenderType($client, $product, $this->productAttributeManager, $this->entityManager)) {
            $reason[] = \underlying_contract_attribute_type::ELIGIBLE_LENDER_TYPE;
            $eligible = false;
        }

        if (false === $this->isLenderEligibleForMaxTotalAmount($client, $project, $this->productAttributeManager, $this->entityManagerSimulator, $this->entityManager)) {
            $reason[] = \underlying_contract_attribute_type::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO;
            $eligible = false;
        }

        return [
            'reason'   => $reason,
            'eligible' => $eligible
        ];
    }
}
