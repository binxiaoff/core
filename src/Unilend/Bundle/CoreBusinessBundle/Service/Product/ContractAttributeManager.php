<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service\Product;

use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class ContractAttributeManager
{
    /** @var EntityManager */
    protected $entityManager;

    /**
     * ProductValidator constructor.
     *
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param \underlying_contract $contract
     * @param string               $attributeType
     *
     * @return array
     */
    public function getContractAttributesByType(\underlying_contract $contract, $attributeType)
    {
        /** @var \underlying_contract_attribute $contractAttr */
        $contractAttr = $this->entityManager->getRepository('underlying_contract_attribute');
        /** @var \underlying_contract_attribute_type $contractAttrType */
        $contractAttrType = $this->entityManager->getRepository('underlying_contract_attribute_type');

        if ($contractAttrType->get($attributeType, 'label')) {
            $attrVars = $contractAttr->select('id_contract = ' . $contract->id_contract . ' AND id_type = ' . $contractAttrType->id_type);
            if (empty($attrVars)) {
                return [];
            } elseif (count($attrVars) === 1) {
                return [array_values($attrVars)[0]['attribute_value']];
            } elseif (count($attrVars) > 1) {
                return array_column($attrVars, 'attribute_value');
            }
        } else {
            throw new \InvalidArgumentException('The loan contract attribute type ' . $attributeType . ' does not exist');
        }
    }
}
