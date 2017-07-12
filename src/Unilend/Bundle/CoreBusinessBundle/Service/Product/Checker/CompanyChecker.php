<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Checker;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\CoreBusinessBundle\Entity\Product;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProductAttributeType;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductAttributeManager;

trait CompanyChecker
{
    /**
     * @param Companies               $company
     * @param Product                 $product
     * @param ProductAttributeManager $productAttributeManager
     *
     * @return bool|null
     */
    private function isEligibleForCreationDays(Companies $company, Product $product, ProductAttributeManager $productAttributeManager)
    {
        $minDays = $productAttributeManager->getProductAttributesByType($product, ProductAttributeType::MIN_CREATION_DAYS);

        if (empty($minDays)) {
            return true;
        }

        if (empty($company->getDateCreation())) {
            return null;
        }

        return $company->getDateCreation()->diff(new \DateTime())->days >= $minDays[0];
    }

    /**
     * @param Companies               $company
     * @param Product                 $product
     * @param ProductAttributeManager $productAttributeManager
     *
     * @return bool
     */
    private function isEligibleForRCS(Companies $company, Product $product, ProductAttributeManager $productAttributeManager)
    {
        $beRCS = $productAttributeManager->getProductAttributesByType($product, ProductAttributeType::ELIGIBLE_BORROWER_COMPANY_RCS);

        if (empty($beRCS)) {
            return true;
        }

        return (false === (bool) $beRCS[0] && true === empty($company->getRcs())) || (true === (bool) $beRCS[0] && false === empty($company->getRcs()));
    }

    /**
     * @param Companies               $company
     * @param Product                 $product
     * @param ProductAttributeManager $productAttributeManager
     *
     * @return bool|null
     */
    private function isEligibleForNafCode(Companies $company, Product $product, ProductAttributeManager $productAttributeManager)
    {
        $nafCode = $productAttributeManager->getProductAttributesByType($product, ProductAttributeType::ELIGIBLE_BORROWER_COMPANY_NAF_CODE);

        if (empty($nafCode)) {
            return true;
        }

        if (empty($company->getCodeNaf())) {
            return null;
        }

        return in_array($company->getCodeNaf(), $nafCode);
    }

    /**
     * @param Companies               $company
     * @param Product                 $product
     * @param ProductAttributeManager $productAttributeManager
     *
     * @return bool|null
     */
    private function isEligibleForExcludedHeadquartersLocation(Companies $company, Product $product, ProductAttributeManager $productAttributeManager)
    {
        $exclusiveLocations = $productAttributeManager->getProductAttributesByType($product, ProductAttributeType::ELIGIBLE_EXCLUDED_HEADQUARTERS_LOCATION);

        if (empty($exclusiveLocations)) {
            return true;
        }

        if (empty($company->getZip())) {
            return null;
        }

        $departement = in_array(substr($company->getZip(), 0, 2), ['97', '98']) ? substr($company->getZip(), 0, 3) : substr($company->getZip(), 0, 2);

        return false === in_array($departement, $exclusiveLocations);
    }

    /**
     * @param Companies               $company
     * @param Product                 $product
     * @param ProductAttributeManager $productAttributeManager
     * @param EntityManager           $entityManager
     *
     * @return bool|null
     */
    private function isEligibleForMaxXerfiScore(Companies $company, Product $product, ProductAttributeManager $productAttributeManager, EntityManager $entityManager)
    {
        $maxXerfiScore = $productAttributeManager->getProductAttributesByType($product, ProductAttributeType::MAX_XERFI_SCORE);

        if (empty($maxXerfiScore)) {
            return true;
        }

        if (empty($company->getCodeNaf())) {
            return null;
        }

        $xerfiScore = $entityManager->getRepository('UnilendCoreBusinessBundle:Xerfi')->find($company->getCodeNaf());
        if ($xerfiScore) {
            return $xerfiScore->getScore() <= $maxXerfiScore[0];
        }

        return null;
    }

    /**
     * @param Companies               $company
     * @param Product                 $product
     * @param ProductAttributeManager $productAttributeManager
     * @param EntityManager           $entityManager
     *
     * @return bool
     */
    private function isEligibleForNoBlendProject(Companies $company, Product $product, ProductAttributeManager $productAttributeManager, EntityManager $entityManager)
    {
        $noInProgressBlendSince = $productAttributeManager->getProductAttributesByType($product, ProductAttributeType::NO_IN_PROGRESS_BLEND_PROJECT_DAYS);

        if (empty($noInProgressBlendSince)) {
            return true;
        }

        $projectRepository              = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $productRepository              = $entityManager->getRepository('UnilendCoreBusinessBundle:Product');
        $projectStatusHistoryRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatusHistory');
        $projects                       = $projectRepository->findBySiren($company->getSiren());

        $acceptableStatus        = [ProjectsStatus::REMBOURSE, ProjectsStatus::REMBOURSEMENT_ANTICIPE];
        $partialAcceptableStatus = [ProjectsStatus::ANALYSIS_REJECTION, ProjectsStatus::COMITY_REJECTION, ProjectsStatus::ABANDONED];

        foreach ($projects as $project) {
            $product = $productRepository->find($project->getIdProduct());
            if (null === $product || Product::PRODUCT_BLEND !== $product->getLabel()) {
                continue;
            }
            if (in_array($project->getStatus(), $acceptableStatus)) {
                continue;
            } elseif (in_array($project->getStatus(), $partialAcceptableStatus)) {
                $lastStatus = $projectStatusHistoryRepository->findStatusFirstOccurrence($project, $project->getStatus());
                if ($lastStatus && $lastStatus->getAdded()->diff(new \DateTime())->days <= $noInProgressBlendSince[0]) {
                    return false;
                }
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * @param Companies               $company
     * @param Product                 $product
     * @param ProductAttributeManager $productAttributeManager
     * @param EntityManager           $entityManager
     *
     * @return bool
     */
    private function isEligibleForNoUnilendProjectIncident(Companies $company, Product $product, ProductAttributeManager $productAttributeManager, EntityManager $entityManager)
    {
        $noUnilendIncidentSince = $productAttributeManager->getProductAttributesByType($product, ProductAttributeType::NO_INCIDENT_UNILEND_PROJECT_DAYS);
        if (empty($noUnilendIncidentSince)) {
            return true;
        }

        $projectRepository              = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $productRepository              = $entityManager->getRepository('UnilendCoreBusinessBundle:Product');
        $projectStatusHistoryRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatusHistory');

        $projects = $projectRepository->findBySiren($company->getSiren());

        foreach ($projects as $project) {
            $product = $productRepository->find($project->getIdProduct());
            if (null === $product || Product::PRODUCT_BLEND === $product->getLabel()) {
                continue;
            }
            $lastIncidentStatus = $projectStatusHistoryRepository->findStatusLastOccurrence($project, [ProjectsStatus::PROBLEME, ProjectsStatus::PROBLEME_J_X, ProjectsStatus::RECOUVREMENT]);

            if ($lastIncidentStatus && $lastIncidentStatus->getAdded()->diff(new \DateTime())->days <= $noUnilendIncidentSince[0]) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param Companies               $company
     * @param Product                 $product
     * @param ProductAttributeManager $productAttributeManager
     * @param EntityManager           $entityManager
     *
     * @return bool
     */
    private function isEligibleForNoBlendProjectIncident(Companies $company, Product $product, ProductAttributeManager $productAttributeManager, EntityManager $entityManager)
    {
        $noBlendIncidentSince = $productAttributeManager->getProductAttributesByType($product, ProductAttributeType::NO_INCIDENT_BLEND_PROJECT_DAYS);
        if (empty($noBlendIncidentSince)) {
            return true;
        }

        $projectRepository              = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $productRepository              = $entityManager->getRepository('UnilendCoreBusinessBundle:Product');
        $projectStatusHistoryRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatusHistory');

        $projects = $projectRepository->findBySiren($company->getSiren());

        foreach ($projects as $project) {
            $product = $productRepository->find($project->getIdProduct());
            if (null === $product || Product::PRODUCT_BLEND !== $product->getLabel()) {
                continue;
            }
            $lastIncidentStatus = $projectStatusHistoryRepository->findStatusLastOccurrence($project, [ProjectsStatus::PROBLEME, ProjectsStatus::PROBLEME_J_X, ProjectsStatus::RECOUVREMENT]);

            if ($lastIncidentStatus && $lastIncidentStatus->getAdded()->diff(new \DateTime())->days <= $noBlendIncidentSince[0]) {
                return false;
            }
        }

        return true;
    }
}
