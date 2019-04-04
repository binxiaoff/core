<?php

namespace Unilend\Service\Product;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Unilend\Entity\{Product, ProductAttributeType};
use Unilend\librairies\CacheKeys;

class ProductAttributeManager
{
    /** @var EntityManagerInterface */
    protected $entityManager;
    /** @var CacheItemPoolInterface */
    protected $cachePool;

    /**
     * @param EntityManagerInterface $entityManager
     * @param CacheItemPoolInterface $cachePool
     */
    public function __construct(EntityManagerInterface $entityManager, CacheItemPoolInterface $cachePool)
    {
        $this->entityManager = $entityManager;
        $this->cachePool     = $cachePool;
    }

    /**
     * @param Product $product
     * @param string  $productAttributeTypeLabel
     *
     * @return array
     */
    public function getProductAttributesByType(Product $product, $productAttributeTypeLabel)
    {
        $cachedItem = $this->cachePool->getItem(CacheKeys::PRODUCT_ATTRIBUTE_BY_TYPE . '_' . $product->getIdProduct() . '_' . $productAttributeTypeLabel);
        if (false === $cachedItem->isHit()) {
            $productAttributeType = $this->entityManager->getRepository(ProductAttributeType::class)->findOneBy(['label' => $productAttributeTypeLabel]);

            if ($productAttributeType) {
                $attrVars          = $product->getProductAttributes($productAttributeType);
                $productAttributes = [];
                foreach ($attrVars as $attrVar) {
                    $productAttributes[] = $attrVar->getAttributeValue();
                }
                $cachedItem->set($productAttributes)->expiresAfter(CacheKeys::SHORT_TIME);
                $this->cachePool->save($cachedItem);
            } else {
                throw new \InvalidArgumentException('The product attribute type ' . $productAttributeTypeLabel . ' does not exist');
            }
        } else {
            $productAttributes = $cachedItem->get();
        }

        return $productAttributes;
    }
}
