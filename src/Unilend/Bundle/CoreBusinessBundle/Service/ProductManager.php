<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class ProductManager
{
    /** @var EntityManager */
    private $entityManager;
    /** @var ProductValidator */
    private $productValidator;

    public function __construct(EntityManager $entityManager, ProductValidator $productValidator)
    {
        $this->entityManager = $entityManager;
        $this->productValidator = $productValidator;
    }

    /**
     * @param \projects $project
     *
     * @return \product[]
     */
    public function findEligibleProducts(\projects $project)
    {
        /** @var \product $product */
        $product = $this->entityManager->getRepository('product');
        $eligibleProducts = [];
        foreach ($product->select() as $oneProduct) {
            $product->get($oneProduct['id_product']);
            if ($this->isProjectEligible($project, $product)) {
                $eligibleProducts[] = $product;
            }
        }

        return $eligibleProducts;
    }

    /**
     * @param \projects $projects
     * @param \product  $product
     *
     * @return bool
     */
    public function isProjectEligible(\projects $projects, \product $product)
    {
        foreach ($this->getAttributeTypeToCheckForProject() as $attributeTypeToCheck) {
            if (false === $this->isProjectEligibleByAttribute($projects, $product, $attributeTypeToCheck)) {
                return false;
            }
        }

        return true;
    }

    private function isProjectEligibleByAttribute(\projects $projects, \product $product, $attributeType)
    {
        $eligibility = false;

        switch ($attributeType) {
            case \product_attribute_type::MIN_LOAN_DURATION_IN_MONTH :
                $eligibility = $this->productValidator->isProjectEligibleForMinDuration($projects, $product);
                break;
            case \product_attribute_type::MAX_LOAN_DURATION_IN_MONTH :
                $eligibility = $this->productValidator->isProjectEligibleForMaxDuration($projects, $product);
                break;
            case \product_attribute_type::ELIGIBLE_BORROWER_COMPANY_TYPE :
                $eligibility = $this->productValidator->isProjectEligibleForCompanyType($projects, $product);
                break;
            case \product_attribute_type::ELIGIBLE_NEED :
                $eligibility = $this->productValidator->isProjectEligibleForNeed($projects, $product);
                break;
        }

        return $eligibility;
    }

    private function getAttributeTypeToCheckForProject()
    {
        return [
           //\product_attribute_type::ELIGIBLE_BORROWER_COMPANY_COUNTRY,
           //\product_attribute_type::TARGET_COUNTRY,
            \product_attribute_type::ELIGIBLE_BORROWER_COMPANY_TYPE,
            \product_attribute_type::ELIGIBLE_NEED,
            \product_attribute_type::MIN_LOAN_DURATION_IN_MONTH,
            \product_attribute_type::MAX_LOAN_DURATION_IN_MONTH
        ];
    }
}