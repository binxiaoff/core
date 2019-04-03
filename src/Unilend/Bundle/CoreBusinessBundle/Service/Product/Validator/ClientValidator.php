<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Validator;

use Doctrine\ORM\EntityManagerInterface;
use Unilend\Entity\{Clients, Product, ProductAttributeType, Projects};
use Unilend\Bundle\CoreBusinessBundle\Service\{Product\Checker\ClientChecker, Product\Checker\LenderChecker, Product\Contract\ContractManager, Product\ProductAttributeManager};

class ClientValidator
{
    use LenderChecker;
    use ClientChecker;

    /** @var ProductAttributeManager */
    protected $productAttributeManager;
    /** @var ContractManager */
    protected $contractManager;
    /** @var EntityManagerInterface */
    protected $entityManager;

    /**
     * @param ProductAttributeManager $productAttributeManager
     * @param ContractManager         $contractManager
     * @param EntityManagerInterface  $entityManager
     */
    public function __construct(
        ProductAttributeManager $productAttributeManager,
        ContractManager $contractManager,
        EntityManagerInterface $entityManager
    )
    {
        $this->productAttributeManager = $productAttributeManager;
        $this->contractManager         = $contractManager;
        $this->entityManager           = $entityManager;
    }

    /**
     * @param Clients|null $client
     * @param Projects     $project
     *
     * @return array
     */
    public function validate(?Clients $client, Projects $project): array
    {
        $violations = [];
        $product    = $this->entityManager->getRepository(Product::class)->find($project->getIdProduct());

        if (false === $this->isEligibleForClientId($client, $product, $this->productAttributeManager)) {
            $violations[] = ProductAttributeType::ELIGIBLE_CLIENT_ID;
        }

        if (false === $this->isEligibleForClientType($client, $product, $this->productAttributeManager)) {
            $violations[] = ProductAttributeType::ELIGIBLE_CLIENT_TYPE;
        }

        return $violations;
    }
}
