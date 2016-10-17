<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service\Product;

use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class ProjectValidator
{
    use Checker\ProjectChecker;

    /** @var ProductAttributeManager */
    private $productAttributeManager;
    /** @var EntityManager */
    private $entityManager;

    public function __construct(ProductAttributeManager $productAttributeManager, EntityManager $entityManager)
    {
        $this->productAttributeManager = $productAttributeManager;
        $this->entityManager = $entityManager;
    }

    /**
     * @param \projects $projects
     * @param \product  $product
     *
     * @return bool
     */
    public function isEligible(\projects $projects, \product $product)
    {
        /** @var \companies $company */
        $company = $this->entityManager->getRepository('companies');
        $company->get($projects->id_company);

        if (false === $this->isEligibleForMinDuration($projects, $product, $this->productAttributeManager)) {
            return false;
        }

        if (false === $this->isEligibleForMaxDuration($projects, $product, $this->productAttributeManager)) {
            return false;
        }

        if (false === $this->isEligibleForMotive($projects, $product, $this->productAttributeManager)) {
            return false;
        }

        if (false === $this->isEligibleForCreationDays($company, $product, $this->productAttributeManager)) {
            return false;
        }

        if (false === $this->isEligibleForRCS($company, $product, $this->productAttributeManager)) {
            return false;
        }

        if (false === $this->isEligibleForNafCode($company, $product, $this->productAttributeManager)) {
            return false;
        }

        return true;
    }
}
