<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product;

use Doctrine\ORM\EntityManager;
use Psr\Cache\CacheItemPoolInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Product;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
use Unilend\librairies\CacheKeys;

class ProductAttributeManager
{
    /** @var EntityManager */
    protected $entityManager;
    /** @var EntityManagerSimulator */
    protected $entityManagerSimulator;
    /** @var CacheItemPoolInterface */
    protected $cachePool;

    /**
     * @param EntityManager          $entityManager
     * @param EntityManagerSimulator $entityManagerSimulator
     * @param CacheItemPoolInterface $cachePool
     */
    public function __construct(EntityManager $entityManager, EntityManagerSimulator $entityManagerSimulator, CacheItemPoolInterface $cachePool)
    {
        $this->entityManager          = $entityManager;
        $this->entityManagerSimulator = $entityManagerSimulator;
        $this->cachePool              = $cachePool;
    }

    /**
     * @param \product|Product $product
     * @param string           $attributeType
     *
     * @return array
     */
    public function getProductAttributesByType($product, $attributeType)
    {
        if ($product instanceof Product) {
            $productId = $product->getIdProduct();
        } else {
            $productId = $product->id_product;
        }
        $cachedItem = $this->cachePool->getItem(CacheKeys::PRODUCT_ATTRIBUTE_BY_TYPE . '_' . $productId . '_' . $attributeType);
        if (false === $cachedItem->isHit()) {
            /** @var \product_attribute $productAttr */
            $productAttr          = $this->entityManagerSimulator->getRepository('product_attribute');
            $productAttributeType = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProductAttributeType')->findOneBy(['label' => $attributeType]);

            if ($productAttributeType) {
                $attrVars          = $productAttr->select('id_product = ' . $productId . ' AND id_type = ' . $productAttributeType->getIdType());
                $productAttributes = [];
                if (count($attrVars) === 1) {
                    $productAttributes = [array_values($attrVars)[0]['attribute_value']];
                } elseif (count($attrVars) > 1) {
                    $productAttributes = array_column($attrVars, 'attribute_value');
                }
                $cachedItem->set($productAttributes)->expiresAfter(CacheKeys::SHORT_TIME);
                $this->cachePool->save($cachedItem);
            } else {
                throw new \InvalidArgumentException('The product attribute type ' . $attributeType . ' does not exist');
            }
        } else {
            $productAttributes = $cachedItem->get();
        }

        return $productAttributes;
    }
}
