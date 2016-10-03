<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service\Product;

use Psr\Cache\CacheItemPoolInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\librairies\CacheKeys;

class ProductAttributeManager
{
    /** @var EntityManager */
    protected $entityManager;
    protected $cachePool;

    /**
     * ProductAttributeManager constructor.
     *
     * @param EntityManager          $entityManager
     * @param CacheItemPoolInterface $cachePool
     */
    public function __construct(EntityManager $entityManager, CacheItemPoolInterface $cachePool)
    {
        $this->entityManager = $entityManager;
        $this->cachePool = $cachePool;
    }

    /**
     * @param \product $product
     * @param  string  $attributeType
     *
     * @return array
     */
    public function getProductAttributesByType(\product $product, $attributeType)
    {
        $cachedItem = $this->cachePool->getItem(CacheKeys::PRODUCT_ATTRIBUTE_BY_TYPE . '_' . $product->id_product . '_' . $attributeType);
        if (false === $cachedItem->isHit()) {
            /** @var \product_attribute $productAttr */
            $productAttr = $this->entityManager->getRepository('product_attribute');
            /** @var \product_attribute_type $productAttrType */
            $productAttrType = $this->entityManager->getRepository('product_attribute_type');
            if ($productAttrType->get($attributeType, 'label')) {
                $attrVars = $productAttr->select('id_product = ' . $product->id_product . ' AND id_type = ' . $productAttrType->id_type);
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

    /**
     * @param \product $product
     * @param string   $attributeType
     *
     * @return array
     */
    public function getProductContractAttributesByType(\product $product, $attributeType)
    {
        $cachedItem = $this->cachePool->getItem(CacheKeys::PRODUCT_CONTRACT_ATTRIBUTE_BY_TYPE . '_' . $product->id_product . '_' . $attributeType);
        if (false === $cachedItem->isHit()) {
            /** @var \product_underlying_contract $productContract */
            $productContract = $this->entityManager->getRepository('product_underlying_contract');
            /** @var \underlying_contract_attribute $contractAttr */
            $contractAttr = $this->entityManager->getRepository('underlying_contract_attribute');
            /** @var \underlying_contract_attribute_type $contractAttrType */
            $contractAttrType = $this->entityManager->getRepository('underlying_contract_attribute_type');
            /** @var \underlying_contract $underlyingContract */
            $underlyingContract = $this->entityManager->getRepository('underlying_contract');

            $contracts          = $productContract->select('id_product = ' . $product->id_product);
            $contractTypeValues = [];
            foreach ($contracts as $contract) {
                $underlyingContract->get($contract['id_contract']);
                if ($contractAttrType->get($attributeType, 'label')) {
                    $attrVars = $contractAttr->select('id_contract = ' . $underlyingContract->id_contract . ' AND id_type = ' . $contractAttrType->id_type);
                    if (empty($attrVars)) {
                        $contractTypeValues[$underlyingContract->label] = [];
                    } elseif (count($attrVars) === 1) {
                        $contractTypeValues[$underlyingContract->label] = [array_values($attrVars)[0]['attribute_value']];
                    } elseif (count($attrVars) > 1) {
                        $contractTypeValues[$underlyingContract->label] = array_column($attrVars, 'attribute_value');
                    }
                    $cachedItem->set($contractTypeValues)->expiresAfter(CacheKeys::SHORT_TIME);
                    $this->cachePool->save($cachedItem);
                } else {
                    throw new \InvalidArgumentException('The loan contract attribute type ' . $attributeType . ' does not exist');
                }
            }
        } else {
            $contractTypeValues = $cachedItem->get();
        }

        return $contractTypeValues;
    }
}
