<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;

class ProjectRatingManager
{
    /** @var EntityManager */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param Projects $project
     *
     * @return float
     */
    public function getRating(Projects $project)
    {
        if ($project->getRisk()) {
            $riskRating = $project->getRisk();
        } else {
            $riskRating = $this->calculateRiskRating($project);
        }

        return constant(Projects::class . '::RISK_' . $riskRating);
    }

    /**
     * @param Projects $project
     *
     * @return string
     */
    public function calculateRiskRating(Projects $project)
    {
        $committeeAvgGrade = $this->calculateCommitteeAverageGrade($project);

        if ($committeeAvgGrade >= 8.5) {
            $riskRating = 'A';
        } elseif ($committeeAvgGrade >= 7.1) {
            $riskRating = 'B';
        } elseif ($committeeAvgGrade >= 6.1) {
            $riskRating = 'C';
        } elseif ($committeeAvgGrade >= 5.1) {
            $riskRating = 'D';
        } elseif ($committeeAvgGrade >= 4) {
            $riskRating = 'E';
        } elseif ($committeeAvgGrade >= 2) {
            $riskRating = 'G';
        } else {
            $riskRating = 'I';
        }

        return $riskRating;
    }

    /**
     * @param Projects $project
     *
     * @return float
     */
    public function calculateCommitteeAverageGrade(Projects $project)
    {
        $projectRating = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsNotes')->findOneBy(['idProject' => $project]);

        if ($projectRating) {
            return round(
                $projectRating->getPerformanceFinanciereComite() * 0.2
                + $projectRating->getMarcheOpereComite() * 0.2
                + $projectRating->getDirigeanceComite() * 0.2
                + $projectRating->getIndicateurRisqueDynamiqueComite() * 0.4
                , 1);
        }

        return 0;
    }
}
