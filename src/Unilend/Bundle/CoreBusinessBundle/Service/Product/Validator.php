<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service\Product;

use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class Validator
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
     * @param \product $product
     * @param  string  $attributeType
     *
     * @return array
     */
    protected function getProductAttributesByType(\product $product, $attributeType)
    {
        /** @var \product_attribute $productAttr */
        $productAttr = $this->entityManager->getRepository('product_attribute');
        /** @var \product_attribute_type $productAttrType */
        $productAttrType = $this->entityManager->getRepository('product_attribute_type');
        if ($productAttrType->get($attributeType, 'label')) {
            $attrVars = $productAttr->select('id_product = ' . $product->id_product . ' AND id_type = ' . $productAttrType->id_type);
            if (empty($attrVars)) {
                return [];
            } elseif (count($attrVars) === 1) {
                return [array_values($attrVars)[0]['attribute_value']];
            } elseif (count($attrVars) > 1) {
                return array_column($attrVars, 'attribute_value');
            }
        } else {
            throw new \InvalidArgumentException('The product attribute type ' . $attributeType . ' does not exist');
        }
    }

    /**
     * @param \product $product
     * @param string   $attributeType
     *
     * @return array
     */
    protected function getContractAttributesByType(\product $product, $attributeType)
    {
        /** @var \product_underlying_contract $productContract */
        $productContract = $this->entityManager->getRepository('product_underlying_contract');
        /** @var \underlying_contract_attribute $contractAttr */
        $contractAttr = $this->entityManager->getRepository('underlying_contract_attribute');
        /** @var \underlying_contract_attribute_type $contractAttrType */
        $contractAttrType = $this->entityManager->getRepository('underlying_contract_attribute_type');

        $contracts          = $productContract->select('id_product = ' . $product->id_product);
        $contractTypeValues = [];
        foreach ($contracts as $contract) {
            if ($contractAttrType->get($attributeType, 'label')) {
                $attrVars = $contractAttr->select('id_contract = ' . $contract['id_contract'] . ' AND id_type = ' . $contractAttrType->id_type);
                if (empty($attrVars)) {
                    $contractTypeValues[$contract['id_contract']] = [];
                } elseif (count($attrVars) === 1) {
                    $contractTypeValues[$contract['id_contract']] = [array_values($attrVars)[0]['attribute_value']];
                } elseif (count($attrVars) > 1) {
                    $contractTypeValues[$contract['id_contract']] = array_column($attrVars, 'attribute_value');
                }
            } else {
                throw new \InvalidArgumentException('The loan contract attribute type ' . $attributeType . ' does not exist');
            }
        }

        return $contractTypeValues;
    }
}
