<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service;


use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class ProductValidator
{

    /** @var EntityManager */
    private $entityManager;

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
     * @param \projects $project
     * @param \product  $product
     *
     * @return bool
     */
    public function isProjectEligibleForMinDuration(\projects $project, \product $product)
    {
        $minDuration = array_values($this->getProductAttributesByType($product, \product_attribute_type::MIN_LOAN_DURATION_IN_MONTH))[0];
        return $project->period >= $minDuration['attribute_value'];
    }

    /**
     * @param \projects $project
     * @param \product  $product
     *
     * @return bool
     */
    public function isProjectEligibleForMaxDuration(\projects $project, \product $product)
    {
        $maxDuration = array_values($this->getProductAttributesByType($product, \product_attribute_type::MAX_LOAN_DURATION_IN_MONTH))[0];
        return $project->period >= $maxDuration['attribute_value'];
    }

    /**
     * @param \projects $project
     * @param \product  $product
     *
     * @return bool
     */
    public function isProjectEligibleForNeed(\projects $project, \product $product)
    {
        $eligibleNeeds = array_column($this->getProductAttributesByType($product, \product_attribute_type::ELIGIBLE_NEED), 'attribute_value');

        if (false === empty($eligibleNeeds)) {
            return in_array($project->id_project_need, $eligibleNeeds);
        }

        return true;
    }

    public function isProjectEligibleForCompanyType(\projects $project, \product $product)
    {
        $eligibleNeeds = array_column($this->getProductAttributesByType($product, \product_attribute_type::ELIGIBLE_BORROWER_COMPANY_TYPE), 'attribute_value');

        if (false === empty($eligibleNeeds)) {
            // todo: need to define a list of available company type.
        }

        return true;
    }

    /**
     * @param \product $product
     * @param          $attributeType
     *
     * @return array
     */
    private function getProductAttributesByType(\product $product, $attributeType)
    {
        /** @var \product_attribute $productAttr */
        $productAttr = $this->entityManager->getRepository('product_attribute');
        /** @var \product_attribute_type $productAttrType */
        $productAttrType = $this->entityManager->getRepository('product_attribute_type');
        if ($productAttrType->get($attributeType, 'label')) {
            return $productAttr->select('id_product = ' . $product->id_product . ' AND id_type = ' . $productAttrType->id_type);
        } else {
            throw new \InvalidArgumentException('The attribute type ' . $attributeType . ' does not exist');
        }
    }
}