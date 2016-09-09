<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product;

use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class ProductManager
{
    /** @var EntityManager */
    private $entityManager;
    /** @var ProjectValidator */
    private $projectValidator;
    /** @var BidValidator */
    private $bidValidator;

    public function __construct(EntityManager $entityManager, ProjectValidator $projectValidator, BidValidator $bidValidator)
    {
        $this->entityManager = $entityManager;
        $this->projectValidator = $projectValidator;
        $this->bidValidator = $bidValidator;
    }

    /**
     * @param \projects $project
     *
     * @return \product[]
     */
    public function findEligibleProducts(\projects $project)
    {
        /** @var \product $product */
        $product = $this->entityManager->getRepository('product');
        $eligibleProducts = [];

        foreach ($product->select() as $oneProduct) {
            $product->get($oneProduct['id_product']);
            if ($this->projectValidator->isEligible($project, $product)) {
                $eligibleProducts[] = $product;
            }
        }

        return $eligibleProducts;
    }

    public function isBidEligible(\bids $bid, \projects $project)
    {
        if (empty($project->id_product)) {
            return true;
        }
        /** @var \product $product */
        $product = $this->entityManager->getRepository('product');
        if ($product->get($project->id_product)) {
            return $this->bidValidator->isEligible($bid, $product);
        }

        return true;
    }

}
