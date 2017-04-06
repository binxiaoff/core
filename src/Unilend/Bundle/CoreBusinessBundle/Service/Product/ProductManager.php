<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service\Product;

use Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\ContractManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

abstract class ProductManager
{
    /** @var EntityManager */
    protected $entityManager;
    /** @var ProjectValidator */
    protected $projectValidator;
    /** @var BidValidator */
    protected $bidValidator;
    /** @var LenderValidator */
    protected $lenderValidator;
    /** @var ProductAttributeManager */
    protected $productAttributeManager;
    /** @var ContractManager */
    protected $contractManager;

    public function __construct(
        EntityManager $entityManager,
        ProjectValidator $projectValidator,
        BidValidator $bidValidator,
        LenderValidator $lenderValidator,
        ProductAttributeManager $productAttributeManager,
        ContractManager $contractManager
    ) {
        $this->entityManager           = $entityManager;
        $this->projectValidator        = $projectValidator;
        $this->bidValidator            = $bidValidator;
        $this->lenderValidator         = $lenderValidator;
        $this->productAttributeManager = $productAttributeManager;
        $this->contractManager         = $contractManager;
    }

    abstract public function findEligibleProducts(\projects $project, $includeInactiveProduct = false);

    /**
     * @param \projects $project
     * @param \product  $product
     *
     * @return bool
     */
    public function isProjectEligible(\projects $project, \product $product)
    {
        return $this->isProductUsable($product) && $this->projectValidator->isEligible($project, $product);
    }

    /**\
     * @param \bids     $bid
     * @return bool
     * @throws \Exception
     */
    public function isBidEligible(\bids $bid)
    {
        return $this->bidValidator->isEligible($bid)['eligible'];
    }

    /**
     * @param \product $product
     *
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
     *
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
     * @param \product $product
     *
     * @return int|null
     */
    public function getAutobidMaxEligibleAmount($lender, $product)
    {
        return $this->lenderValidator->getAutobidMaxEligibleAmount($lender, $product, $this->entityManager, $this->contractManager);
    }

    /**
     * @param \lenders_accounts $lenderAccount
     * @param \projects         $project
     *
     * @return array
     */
    public function getLenderEligibilityWithReasons(\lenders_accounts $lenderAccount, \projects $project)
    {
        return $this->lenderValidator->isEligible($lenderAccount, $project)['reason'];
    }

    /**
     * @param \lenders_accounts $lenderAccount
     * @param \projects         $project
     *
     * @return array
     */
    public function getLenderEligibility(\lenders_accounts $lenderAccount, \projects $project)
    {
        return $this->lenderValidator->isEligible($lenderAccount, $project)['eligible'];
    }

    /**
     * @param \bids    $bid
     *
     * @return array
     */
    public function getBidEligibilityWithReasons(\bids $bid)
    {
        return $this->bidValidator->isEligible($bid)['reason'];
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
     * @return mixed
     */
    abstract public function getAvailableProducts();

    /**
     * @param \product $product
     *
     * @return array
     */
    public function getAvailableContracts(\product $product)
    {
        /** @var \product_underlying_contract $productContract */
        $productContract = $this->entityManager->getRepository('product_underlying_contract');
        return $productContract->getUnderlyingContractsByProduct($product->id_product);
    }

    /**
     * @param \product
     *
     * @return \underlying_contract[]
     */
    public function getAutobidEligibleContracts(\product $product)
    {
        /** @var \underlying_contract $contract */
        $contract         = $this->entityManager->getRepository('underlying_contract');
        $contracts        = $this->getAvailableContracts($product);
        $autobidContracts = [];

        foreach ($contracts as $underlyingContract) {
            $contract->get($underlyingContract['id_contract']);
            if ($this->contractManager->isAutobidSettingsEligible($contract)) {
                $autobidContract    = clone $contract;
                $autobidContracts[] = $autobidContract;
            }
        }

        return $autobidContracts;
    }

    /**
     * @param \product $product
     *
     * @return bool
     */
    public function isProductUsable(\product $product)
    {
        return false === empty($product->id_product) && in_array($product->status, [\product::STATUS_ONLINE, \product::STATUS_OFFLINE]);
    }
}
