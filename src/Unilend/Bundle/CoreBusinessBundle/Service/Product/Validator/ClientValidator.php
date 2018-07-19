<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Validator;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProductAttributeType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Checker\ClientChecker;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Checker\LenderChecker;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\ContractManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductAttributeManager;

class ClientValidator
{
    use LenderChecker;
    use ClientChecker;

    /** @var ProductAttributeManager */
    protected $productAttributeManager;
    /** @var ContractManager */
    protected $contractManager;
    /** @var EntityManager */
    protected $entityManager;

    /**
     * @param ProductAttributeManager $productAttributeManager
     * @param ContractManager         $contractManager
     * @param EntityManager           $entityManager
     */
    public function __construct(
        ProductAttributeManager $productAttributeManager,
        ContractManager $contractManager,
        EntityManager $entityManager
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
    public function validate(?Clients $client = null, Projects $project): array
    {
        $violations = [];
        $product    = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Product')->find($project->getIdProduct());

        if (false === $this->isEligibleForClientId($client, $product, $this->productAttributeManager)) {
            $violations[] = ProductAttributeType::ELIGIBLE_CLIENT_ID;
        }

        if (false === $this->isEligibleForClientType($client, $product, $this->productAttributeManager)) {
            $violations[] = ProductAttributeType::ELIGIBLE_CLIENT_TYPE;
        }

        return $violations;
    }
}
