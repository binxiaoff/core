<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract;

use Psr\Cache\CacheItemPoolInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\UnderlyingContract;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\librairies\CacheKeys;

class ContractAttributeManager
{
    /** @var EntityManager */
    protected $entityManager;
    /** @var CacheItemPoolInterface */
    protected $cachePool;

    /**
     * ContractAttributeManager constructor.
     *
     * @param EntityManager          $entityManager
     * @param CacheItemPoolInterface $cachePool
     */
    public function __construct(EntityManager $entityManager, CacheItemPoolInterface $cachePool)
    {
        $this->entityManager = $entityManager;
        $this->cachePool     = $cachePool;
    }

    /**
     * @param UnderlyingContract|\underlying_contract $contract
     * @param string               $attributeType
     *
     * @return array
     */
    public function getContractAttributesByType($contract, $attributeType)
    {
        if ($contract instanceof UnderlyingContract) {
            $contractId = $contract->getIdContract();
        } else {
            $contractId = $contract->id_contract;
        }
        $cachedItem = $this->cachePool->getItem(CacheKeys::CONTRACT_ATTRIBUTE_BY_TYPE . '_' . $contractId . '_' . $attributeType);
        if (false === $cachedItem->isHit()) {
            /** @var \underlying_contract_attribute $contractAttr */
            $contractAttr = $this->entityManager->getRepository('underlying_contract_attribute');
            /** @var \underlying_contract_attribute_type $contractAttrType */
            $contractAttrType = $this->entityManager->getRepository('underlying_contract_attribute_type');

            if ($contractAttrType->get($attributeType, 'label')) {
                $attrVars           = $contractAttr->select('id_contract = ' . $contractId . ' AND id_type = ' . $contractAttrType->id_type);
                $contractAttributes = [];
                if (count($attrVars) === 1) {
                    $contractAttributes = [array_values($attrVars)[0]['attribute_value']];
                } elseif (count($attrVars) > 1) {
                    $contractAttributes = array_column($attrVars, 'attribute_value');
                }
                $cachedItem->set($contractAttributes)->expiresAfter(CacheKeys::SHORT_TIME);
                $this->cachePool->save($cachedItem);
            } else {
                throw new \InvalidArgumentException('The loan contract attribute type ' . $attributeType . ' does not exist');
            }
        } else {
            $contractAttributes = $cachedItem->get();
        }

        return $contractAttributes;
    }
}
