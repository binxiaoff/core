<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product;

use Doctrine\ORM\EntityManager;
use Psr\Cache\CacheItemPoolInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Product;
use Unilend\librairies\CacheKeys;

class ProductAttributeManager
{
    /** @var EntityManager */
    protected $entityManager;
    /** @var CacheItemPoolInterface */
    protected $cachePool;

    /**
     * @param EntityManager          $entityManager
     * @param CacheItemPoolInterface $cachePool
     */
    public function __construct(EntityManager $entityManager, CacheItemPoolInterface $cachePool)
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
            $productAttributeType = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProductAttributeType')->findOneBy(['label' => $productAttributeTypeLabel]);

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
