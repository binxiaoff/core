<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service\Product;

use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class LenderValidator
{
    use Checker\LenderChecker;

    /** @var ProductAttributeManager */
    private $productAttributeManager;
    /** @var EntityManager */
    private $entityManager;

    public function __construct(ProductAttributeManager $productAttributeManager, EntityManager $entityManager)
    {
        $this->productAttributeManager = $productAttributeManager;
        $this->entityManager           = $entityManager;
    }

    /**
     * @param \lenders_accounts $lender
     * @param \projects         $project
     *
     * @return array
     */
    public function isEligible(\lenders_accounts $lender, \projects $project)
    {
        $eligible = true;
        $reason = [];
        /** @var \product $product */
        $product = $this->entityManager->getRepository('product');
        if (false === $product->get($project->id_product)) {
            throw new \InvalidArgumentException('The product id ' . $project->id_product . ' does not exist');
        }

        if (false === $this->isLenderEligibleForType($lender, $product, $this->productAttributeManager, $this->entityManager)) {
            $reason[] = \underlying_contract_attribute_type::ELIGIBLE_LENDER_TYPE;
            $eligible = false;
        }

        if (false === $this->isLenderEligibleForMaxTotalAmount($lender, $project, $this->productAttributeManager, $this->entityManager)) {
            $reason[] = \underlying_contract_attribute_type::TOTAL_LOAN_AMOUNT_LIMITATION_IN_EURO;
            $eligible = false;
        }

        return [
            'reason' => $reason,
            'eligible' => $eligible
        ];
    }
}
