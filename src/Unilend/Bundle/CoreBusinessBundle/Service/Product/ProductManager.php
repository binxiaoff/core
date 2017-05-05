<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service\Product;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\ContractManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

abstract class ProductManager
{
    /** @var EntityManagerSimulator */
    protected $entityManagerSimulator;
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
    /** @var  EntityManager */
    protected $entityManager;

    /**
     * ProductManager constructor.
     * @param EntityManagerSimulator  $entityManagerSimulator
     * @param ProjectValidator        $projectValidator
     * @param BidValidator            $bidValidator
     * @param LenderValidator         $lenderValidator
     * @param ProductAttributeManager $productAttributeManager
     * @param ContractManager         $contractManager
     * @param EntityManager           $entityManager
     */
    public function __construct(
        EntityManagerSimulator $entityManagerSimulator,
        ProjectValidator $projectValidator,
        BidValidator $bidValidator,
        LenderValidator $lenderValidator,
        ProductAttributeManager $productAttributeManager,
        ContractManager $contractManager,
        EntityManager $entityManager
    )
    {
        $this->entityManagerSimulator  = $entityManagerSimulator;
        $this->projectValidator        = $projectValidator;
        $this->bidValidator            = $bidValidator;
        $this->lenderValidator         = $lenderValidator;
        $this->productAttributeManager = $productAttributeManager;
        $this->contractManager         = $contractManager;
        $this->entityManager           = $entityManager;
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
     * @param Clients  $client
     * @param \product $product
     *
     * @return int|null
     * @throws \Exception
     */
    public function getMaxEligibleAmount(\product $product)
    {
        return $this->lenderValidator->getMaxEligibleAmount($product, $this->productAttributeManager);
    }

    /**
     * @param Clients  $client
     * @param \product $product
     *
     * @return int|null
     */
    public function getAutobidMaxEligibleAmount(Clients $client, \product $product)
    {
        return $this->lenderValidator->getAutobidMaxEligibleAmount($client, $product, $this->entityManagerSimulator, $this->contractManager);
    }

    /**
     * @param Clients   $client
     * @param \projects $project
     *
     * @return mixed
     */
    public function getLenderEligibilityWithReasons(Clients $client, \projects $project)
    {
        return $this->lenderValidator->isEligible($client, $project)['reason'];
    }

    /**
     * @param Clients   $client
     * @param \projects $project
     *
     * @return mixed
     */
    public function getLenderEligibility(Clients $client, \projects $project)
    {
        return $this->lenderValidator->isEligible($client, $project)['eligible'];
    }

    /**
     * @param \bids    $bid
     *
     * @return mixed
     */
    public function getBidEligibilityWithReasons(\bids $bid)
    {
        return $this->bidValidator->isEligible($bid)['reason'];
    }

    /**
     * @param Clients $client
     * @param \projects $project
     *
     * @return null|string
     */
    public function getAmountLenderCanStillBid(Clients $client, \projects $project)
    {
        return $this->lenderValidator->getAmountLenderCanStillBid($client, $project, $this->productAttributeManager, $this->entityManagerSimulator, $this->entityManager);
    }

    /**
     * @param \product $product
     * @param $attributeType
     *
     * @return array
     */
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
        $productContract = $this->entityManagerSimulator->getRepository('product_underlying_contract');
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
        $contract         = $this->entityManagerSimulator->getRepository('underlying_contract');
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
