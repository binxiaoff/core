<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Validator;

use Unilend\Bundle\CoreBusinessBundle\Entity\Product;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Checker\CompanyChecker;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Checker\ProjectChecker;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\Contract\ContractManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductAttributeManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class ProjectValidator
{
    use ProjectChecker;
    use CompanyChecker;

    /** @var ProductAttributeManager */
    private $productAttributeManager;

    /** @var EntityManager */
    private $entityManager;

    /** @var ContractManager */
    private $contractManager;

    public function __construct(ProductAttributeManager $productAttributeManager, EntityManager $entityManager, ContractManager $contractManager)
    {
        $this->productAttributeManager = $productAttributeManager;
        $this->entityManager           = $entityManager;
        $this->contractManager         = $contractManager;
    }

    /**
     * @param Projects $project
     * @param Product  $product
     *
     * @return bool
     */
    public function validate(Projects $project, Product $product)
    {
        if (false === $this->isEligibleForMinDuration($project, $product, $this->productAttributeManager)) {
            return false;
        }

        if (false === $this->isEligibleForMaxDuration($project, $product, $this->productAttributeManager)) {
            return false;
        }

        if (false === $this->isEligibleForMotive($project, $product, $this->productAttributeManager)) {
            return false;
        }

        if (false === $this->isEligibleForCreationDays($project->getIdCompany(), $product, $this->productAttributeManager)) {
            return false;
        }

        if (false === $this->isEligibleForRCS($project->getIdCompany(), $product, $this->productAttributeManager)) {
            return false;
        }

        if (false === $this->isEligibleForNafCode($project->getIdCompany(), $product, $this->productAttributeManager)) {
            return false;
        }

        $hasEligibleContract = false;
        foreach ($product->getIdContract() as $contract) {
            if ($this->contractManager->checkProjectEligibility($project, $contract)) {
                $hasEligibleContract = true;
            }
        }

        if (false === $hasEligibleContract) {
            return false;
        }

        return true;
    }
}
