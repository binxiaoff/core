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
    /** @var ProductAttributeManager */
    private $productAttributeManager;

    public function __construct(
        EntityManager $entityManager,
        ProjectValidator $projectValidator,
        BidValidator $bidValidator,
        LenderValidator $lenderValidator,
        ProductAttributeManager $productAttributeManager
    )
    {
        $this->entityManager            = $entityManager;
        $this->projectValidator         = $projectValidator;
        $this->bidValidator             = $bidValidator;
        $this->lenderValidator          = $lenderValidator;
        $this->productAttributeManager  = $productAttributeManager;
    }

    /**
     * @param \projects $project
     * @param bool      $includeInactiveProduct
     *
     * @return \product[]
     */
    public function findEligibleProducts(\projects $project, $includeInactiveProduct = false)
    {
        $eligibleProducts = [];

        foreach ($this->getAvailableProducts($includeInactiveProduct) as $product) {
            if ($this->projectValidator->isEligible($project, $product)) {
                $eligibleProduct = clone $product;
                $eligibleProducts[] = $eligibleProduct;
            }
        }

        return $eligibleProducts;
    }

    /**\
     * @param \bids $bid
     * @param \projects $project
     * @return bool
     * @throws \Exception
     */
    public function isBidEligible(\bids $bid, \projects $project)
    {
        $product = $this->getAssociatedProduct($project);

        return $this->bidValidator->isEligible($bid, $product);
    }

    public function isLenderEligible(\lenders_accounts $lender, \projects $project)
    {
        return $this->lenderValidator->isEligible($lender, $project);
    }

    /**
     * @param \projects $project
     * @return mixed
     * @throws \Exception
     */
    public function getProjectAvailableContractTypes(\projects $project)
    {
        $product = $this->getAssociatedProduct($project);
        return $this->getProductAvailableContracts($product);
    }

    /**
     * @param \projects $project
     * @return \product
     * @throws \Exception
     */
    private function getAssociatedProduct(\projects $project)
    {
        /** @var \product $product */
        $product = $this->entityManager->getRepository('product');
        if (! $product->get($project->id_product)) {
            throw new \Exception('Invalid product id ' . $project->id_product . ' found for project id ' . $project->id_project);
        }

        return $product;
    }

    /**
     * @param \product $product
     * @return mixed|null
     */
    public function getMaxEligibleDuration(\product $product)
    {
        $durationContractMaxAttr = $this->productAttributeManager->getProductContractAttributesByType($product, \underlying_contract_attribute_type::MAX_LOAN_DURATION_IN_MONTH);
        $durationContractMax     = null;
        foreach ($durationContractMaxAttr as $contractVars) {
            if (isset($contractVars[0])) {
                if ($durationContractMax === null) {
                    $durationContractMax = $contractVars[0];
                } else {
                    $durationContractMax = min($durationContractMax, $contractVars[0]);
                }
            }
        }

        $durationProductMaxAttr = $this->productAttributeManager->getProductAttributesByType($product, \product_attribute_type::MAX_LOAN_DURATION_IN_MONTH);
        $durationProductMax     = empty($durationProductMaxAttr) ? null : $durationProductMaxAttr[0];

        if (null === $durationProductMax) {
            return $durationContractMax;
        } elseif (null === $durationContractMax) {
            return $durationProductMax;
        }

        return min($durationProductMax, $durationContractMax);
    }

    /**
     * @param \product $product
     * @return string|null
     */
    public function getMinEligibleDuration(\product $product)
    {
        $durationMinAttr = $this->productAttributeManager->getProductAttributesByType($product, \product_attribute_type::MIN_LOAN_DURATION_IN_MONTH);
        $durationMin     = empty($durationMinAttr) ? null : $durationMinAttr[0];

        return $durationMin;
    }

    /**
     * @param \product $product
     *
     * @return int|null
     */
    public function getMaxEligibleAmount(\product $product)
    {
        return $this->lenderValidator->getMaxEligibleAmount($product, $this->productAttributeManager);
    }

    /**
     * @param \lenders_accounts $lenderAccount
     * @param \projects         $project
     *
     * @return array
     */
    public function getLenderValidationReasons(\lenders_accounts $lenderAccount, \projects $project)
    {
        return $this->lenderValidator->getReasons($lenderAccount, $project);
    }

    /**
     * @param \bids    $bid
     * @param \product $product
     *
     * @return array
     */
    public function getBidValidatorReasons(\bids $bid, \product $product)
    {
        return $this->bidValidator->getReasons($bid, $product);
    }

    public function getAmountLenderCanStillBid(\lenders_accounts $lender, \projects $project)
    {
        return $this->lenderValidator->getAmountLenderCanStillBid($lender, $project, $this->productAttributeManager, $this->entityManager);
    }

    public function getAttributesByType(\product $product, $attributeType)
    {
        return $this->productAttributeManager->getProductAttributesByType($product, $attributeType);
    }

    /**
     * @param bool      $includeInactiveProduct
     *
     * @return \product[]
     */
    public function getAvailableProducts($includeInactiveProduct = false)
    {
        /** @var \product $product */
        $product  = $this->entityManager->getRepository('product');
        $availableProducts = [];

        foreach ($product->select() as $oneProduct) {
            $product->get($oneProduct['id_product']);
            if ($product->status != \product::STATUS_ARCHIVED
                && ($includeInactiveProduct || $product->status == \product::STATUS_ONLINE)) {
                $availableProduct = clone $product;
                $availableProducts[] = $availableProduct;
            }
        }

        return $availableProducts;
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
