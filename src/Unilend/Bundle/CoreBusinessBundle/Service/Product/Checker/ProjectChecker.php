<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service\Product\Checker;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Product;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProductAttributeType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductAttributeManager;
use Unilend\Bundle\WSClientBundle\Service\InfolegaleManager;

trait ProjectChecker
{
    /**
     * @param Projects                $project
     * @param Product                 $product
     * @param ProductAttributeManager $productAttributeManager
     *
     * @return bool|null Return true when the check is OK, false when the check is failed, null when the check cannot be done (lack of data for example).
     */
    private function isEligibleForMinDuration(Projects $project, Product $product, ProductAttributeManager $productAttributeManager)
    {
        $minDuration = $productAttributeManager->getProductAttributesByType($product, ProductAttributeType::MIN_LOAN_DURATION_IN_MONTH);

        if (empty($minDuration)) {
            return true;
        }

        if (empty($project->getPeriod())) {
            return null;
        }

        return $project->getPeriod() >= $minDuration[0];
    }

    /**
     * @param Projects                $project
     * @param Product                 $product
     * @param ProductAttributeManager $productAttributeManager
     *
     * @return bool|null Return true when the check is OK, false when the check is failed, null when the check cannot be done (lack of data for example).
     */
    private function isEligibleForMaxDuration(Projects $project, Product $product, ProductAttributeManager $productAttributeManager)
    {
        $maxDuration = $productAttributeManager->getProductAttributesByType($product, ProductAttributeType::MAX_LOAN_DURATION_IN_MONTH);

        if (empty($maxDuration)) {
            return true;
        }

        if (empty($project->getPeriod())) {
            return null;
        }

        return $project->getPeriod() <= $maxDuration[0];
    }

    /**
     * @param Projects                $project
     * @param Product                 $product
     * @param ProductAttributeManager $productAttributeManager
     *
     * @return bool|null Return true when the check is OK, false when the check is failed, null when the check cannot be done (lack of data for example).
     */
    private function isEligibleForMotive(Projects $project, Product $product, ProductAttributeManager $productAttributeManager)
    {
        $eligibleMotives = $productAttributeManager->getProductAttributesByType($product, ProductAttributeType::ELIGIBLE_BORROWING_MOTIVE);

        if (empty($eligibleMotives)) {
            return true;
        }

        if (empty($project->getIdBorrowingMotive())) {
            return null;
        }

        return in_array($project->getIdBorrowingMotive(), $eligibleMotives);
    }

    /**
     * @param Projects                $project
     * @param Product                 $product
     * @param ProductAttributeManager $productAttributeManager
     *
     * @return bool|null Return true when the check is OK, false when the check is failed, null when the check cannot be done (lack of data for example).
     */
    private function isEligibleForExcludedMotive(Projects $project, Product $product, ProductAttributeManager $productAttributeManager)
    {
        $eligibleExcludedMotives = $productAttributeManager->getProductAttributesByType($product, ProductAttributeType::ELIGIBLE_EXCLUDED_BORROWING_MOTIVE);

        if (empty($eligibleExcludedMotives)) {
            return true;
        }

        if (empty($project->getIdBorrowingMotive())) {
            return null;
        }

        return false === in_array($project->getIdBorrowingMotive(), $eligibleExcludedMotives);
    }

    /**
     * @param Projects                $project
     * @param Product                 $product
     * @param ProductAttributeManager $productAttributeManager
     * @param InfolegaleManager       $infolegaleManager
     * @param EntityManager           $entityManager
     *
     * @return bool|null
     */
    private function isEligibleForRequesterName(
        Projects $project,
        Product $product,
        ProductAttributeManager $productAttributeManager,
        InfolegaleManager $infolegaleManager,
        EntityManager $entityManager
    )
    {
        $eligibleRequester = $productAttributeManager->getProductAttributesByType($product, ProductAttributeType::VERIFICATION_REQUESTER_IS_ONE_OF_THE_DIRECTOR);

        if (empty($eligibleRequester)) {
            return true;
        }

        $company         = $project->getIdCompany();
        $companyIdentity = $infolegaleManager->getIdentity($company->getSiren());
        $client          = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($company->getIdClientOwner());
        if ($companyIdentity && $client && $client->getNom() && $client->getPrenom()) {
            foreach ($companyIdentity->getDirectors() as $director) {
                if (
                    mb_strtolower(trim($client->getNom())) === mb_strtolower(trim($director->getName()))
                    && mb_strtolower(trim($client->getPrenom())) === mb_strtolower(trim($director->getFirstName()))
                ) {
                    return true;
                }
            }

            return false;
        }

        return null;
    }

    /**
     * @param Projects                $project
     * @param Product                 $product
     * @param ProductAttributeManager $productAttributeManager
     * @param EntityManager           $entityManager
     *
     * @return bool|null
     */
    private function isEligibleForMinPreScore(Projects $project, Product $product, ProductAttributeManager $productAttributeManager, EntityManager $entityManager)
    {
        $minPreScore = $productAttributeManager->getProductAttributesByType($product, ProductAttributeType::MIN_PRE_SCORE);

        if (empty($minPreScore)) {
            return true;
        }

        $projectNote = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsNotes')->findOneBy(['idProject' => $project->getIdProject()]);

        if (empty($projectNote->getPreScoring())) {
            return null;
        }

        return $projectNote->getPreScoring() >= $minPreScore[0];
    }

    /**
     * @param Projects                $project
     * @param Product                 $product
     * @param ProductAttributeManager $productAttributeManager
     * @param EntityManager           $entityManager
     *
     * @return bool|null
     */
    private function isEligibleForMaxPreScore(Projects $project, Product $product, ProductAttributeManager $productAttributeManager, EntityManager $entityManager)
    {
        $maxPreScore = $productAttributeManager->getProductAttributesByType($product, ProductAttributeType::MAX_PRE_SCORE);

        if (empty($minPreScore)) {
            return true;
        }

        $projectNote = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsNotes')->findOneBy(['idProject' => $project->getIdProject()]);

        if (empty($projectNote->getPreScoring())) {
            return null;
        }

        return $projectNote->getPreScoring() <= $maxPreScore[0];
    }
}
