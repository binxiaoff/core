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
    /** @var LenderValidator */
    private $lenderValidator;

    public function __construct(EntityManager $entityManager, ProjectValidator $projectValidator, BidValidator $bidValidator, LenderValidator $lenderValidator)
    {
        $this->entityManager    = $entityManager;
        $this->projectValidator = $projectValidator;
        $this->bidValidator     = $bidValidator;
        $this->lenderValidator  = $lenderValidator;
    }

    /**
     * @param \projects $project
     * @param bool      $includeInactiveProduct
     *
     * @return \product[]
     */
    public function findEligibleProducts(\projects $project, $includeInactiveProduct = false)
    {
        /** @var \product $product */
        $product          = $this->entityManager->getRepository('product');
        $eligibleProducts = [];

        foreach ($product->select() as $oneProduct) {
            $product->get($oneProduct['id_product']);
            if ($product->status != \product::STATUS_ARCHIVED
                && ($includeInactiveProduct || $product->status == \product::STATUS_ONLINE)
                && $this->projectValidator->isEligible($project, $product)
            ) {
                $eligibleProduct = clone $product;
                $eligibleProducts[] = $eligibleProduct;
            }
        }

        return $eligibleProducts;
    }

    /**
     * @param \bids     $bid
     * @param \projects $project
     *
     * @return bool
     */
    public function isBidEligible(\bids $bid, \projects $project)
    {
        $product = $this->getAssociatedProduct($project);
        if (false === $product) {
            return true;
        }

        return $this->bidValidator->isEligible($bid, $product);

    }

    public function isLenderEligible(\lenders_accounts $lender, \projects $project)
    {
        return $this->lenderValidator->isEligible($lender, $project);
    }

    /**
     * @param \projects $project
     *
     * @return array
     */
    public function getProjectAvailableContractTypes(\projects $project)
    {
        $product = $this->getAssociatedProduct($project);
        if (false === $product) {
            return [];
        }

        /** @var \product_underlying_contract $productContract */
        $productContract = $this->entityManager->getRepository('product_underlying_contract');
        return $productContract->getUnderlyingContractsByProduct($product->id_product);
    }

    /**
     * @param \projects $project
     *
     * @return bool|\product
     */
    public function getAssociatedProduct(\projects $project)
    {
        if (empty($project->id_product)) {
            return false;
        }
        /** @var \product $product */
        $product = $this->entityManager->getRepository('product');

        if ($product->get($project->id_product)) {
            return $product;
        }

        return false;
    }

}
