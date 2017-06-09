<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Bids;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\Product;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProductAttributeType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContract;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\ContractManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Validator\BidValidator;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Validator\ClientValidator;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Validator\ProjectValidator;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

abstract class ProductManager
{
    /** @var EntityManagerSimulator */
    protected $entityManagerSimulator;
    /** @var ProjectValidator */
    protected $projectValidator;
    /** @var BidValidator */
    protected $bidValidator;
    /** @var ClientValidator */
    protected $clientValidator;
    /** @var ProductAttributeManager */
    protected $productAttributeManager;
    /** @var ContractManager */
    protected $contractManager;
    /** @var  EntityManager */
    protected $entityManager;

    /**
     * ProductManager constructor.
     *
     * @param EntityManagerSimulator  $entityManagerSimulator
     * @param ProjectValidator        $projectValidator
     * @param BidValidator            $bidValidator
     * @param ClientValidator         $clientValidator
     * @param ProductAttributeManager $productAttributeManager
     * @param ContractManager         $contractManager
     * @param EntityManager           $entityManager
     */
    public function __construct(
        EntityManagerSimulator $entityManagerSimulator,
        ProjectValidator $projectValidator,
        BidValidator $bidValidator,
        ClientValidator $clientValidator,
        ProductAttributeManager $productAttributeManager,
        ContractManager $contractManager,
        EntityManager $entityManager
    )
    {
        $this->entityManagerSimulator  = $entityManagerSimulator;
        $this->projectValidator        = $projectValidator;
        $this->bidValidator            = $bidValidator;
        $this->clientValidator         = $clientValidator;
        $this->productAttributeManager = $productAttributeManager;
        $this->contractManager         = $contractManager;
        $this->entityManager           = $entityManager;
    }

    abstract public function findEligibleProducts(\projects $project, $includeInactiveProduct = false);

    /**
     * @param Projects|\projects $project
     * @param Product|\product   $product
     *
     * @return bool
     */
    public function isProjectEligible($project, $product)
    {
        $product = $this->convertProduct($product);
        $project = $this->convertProject($project);

        return $this->isProductUsable($product) && 0 === count($this->projectValidator->validate($project, $product));
    }

    public function checkProjectEligibility($project, $product)
    {
        $product = $this->convertProduct($product);
        $project = $this->convertProject($project);

        return $this->projectValidator->validate($project, $product);
    }

    /**\
     * @param Bids $bid
     *
     * @return bool
     */
    public function isBidEligible(Bids $bid)
    {
        return 0 === count($this->checkBidEligibility($bid));
    }

    /**
     * @param Bids $bid
     *
     * @return mixed
     */
    public function checkBidEligibility($bid)
    {
        return $this->bidValidator->validate($bid);
    }

    /**
     * @param Clients|null       $client When it's null, it's an anonymous client (logout)
     * @param Projects|\projects $project
     *
     * @return mixed
     */
    public function checkClientEligibility(Clients $client = null, $project)
    {
        $project = $this->convertProject($project);

        return $this->clientValidator->validate($client, $project);
    }

    /**
     * @param Clients   $client
     * @param \projects $project
     *
     * @return bool
     */
    public function isClientEligible(Clients $client, \projects $project)
    {
        return 0 === count($this->checkClientEligibility($client, $project));
    }

    /**
     * @param \product $product
     *
     * @return mixed|null
     */
    public function getEligibleMaxDuration(\product $product)
    {
        $product = $this->convertProduct($product);

        $durationContractMax = null;

        foreach ($product->getIdContract() as $contract) {
            $durationContract = $this->contractManager->getMaxEligibleDuration($contract);
            if (null === $durationContractMax) {
                $durationContractMax = $durationContract;
            } else {
                $durationContractMax = min($durationContractMax, $durationContract);
            }
        }

        $durationProductMaxAttr = $this->productAttributeManager->getProductAttributesByType($product, ProductAttributeType::MAX_LOAN_DURATION_IN_MONTH);
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
    public function getEligibleMinDuration(\product $product)
    {
        $durationMinAttr = $this->productAttributeManager->getProductAttributesByType($product, ProductAttributeType::MIN_LOAN_DURATION_IN_MONTH);
        $durationMin     = empty($durationMinAttr) ? null : $durationMinAttr[0];

        return $durationMin;
    }

    /**
     * @param Clients          $client
     * @param Product|\product $product
     * @param boolean          $isAutobid
     *
     * @return int|null
     * @throws \Exception
     */
    public function getMaxEligibleAmount(Clients $client, $product, $isAutobid = false)
    {
        $product = $this->convertProduct($product);

        return $this->clientValidator->getMaxEligibleAmount($client, $product, $this->contractManager, $isAutobid);
    }

    /**
     * @param Clients   $client
     * @param \projects $project
     *
     * @return null|string
     */
    public function getAmountLenderCanStillBid(Clients $client, \projects $project)
    {
        $project = $this->convertProject($project);

        return $this->clientValidator->getAmountLenderCanStillBid($client, $project, $this->contractManager, $this->entityManager);
    }

    /**
     * @param \product $product
     * @param          $attributeType
     *
     * @return array
     */
    public function getAttributesByType(\product $product, $attributeType)
    {
        return $this->productAttributeManager->getProductAttributesByType($product, $attributeType);
    }

    /**
     * @return \product[]
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
     * @param \product|Product $product
     *
     * @return UnderlyingContract[]
     */
    public function getAutobidEligibleContracts($product)
    {
        $product          = $this->convertProduct($product);
        $autobidContracts = [];

        foreach ($product->getIdContract() as $contract) {
            if ($this->contractManager->isAutobidSettingsEligible($contract)) {
                $autobidContract    = clone $contract;
                $autobidContracts[] = $autobidContract;
            }
        }

        return $autobidContracts;
    }

    /**
     * @param Product $product
     *
     * @return bool
     */
    private function isProductUsable(Product $product)
    {
        return false === empty($product->getIdProduct()) && in_array($product->getStatus(), [Product::STATUS_ONLINE, Product::STATUS_OFFLINE]);
    }

    /**
     * @param Product|\product $product
     *
     * @return null|Product
     */
    private function convertProduct($product)
    {
        if ($product instanceof \product) {
            return $this->entityManager->getRepository('UnilendCoreBusinessBundle:Product')->find($product->id_product);
        }

        return $product;
    }

    private function convertProject($project)
    {
        if ($project instanceof \projects) {
            return $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->find($project->id_project);
        }

        return $project;
    }
}
