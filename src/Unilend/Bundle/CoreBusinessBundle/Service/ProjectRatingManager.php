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

    public function calculateRiskRating(Projects $project)
    {
        $committeeAverageNote = $this->calculateCommitteeAverageNote($project);
        if ($committeeAverageNote >= 8.5 && $committeeAverageNote <= 10) {
            $riskRating = 'A';
        } elseif ($committeeAverageNote >= 7.1 && $committeeAverageNote < 8.5) {
            $riskRating = 'B';
        } elseif ($committeeAverageNote >= 6.1 && $committeeAverageNote < 7.1) {
            $riskRating = 'C';
        } elseif ($committeeAverageNote >= 5.1 && $committeeAverageNote < 6.1) {
            $riskRating = 'D';
        } elseif ($committeeAverageNote >= 4 && $committeeAverageNote < 5.1) {
            $riskRating = 'E';
        } elseif ($committeeAverageNote >= 2 && $committeeAverageNote < 4) {
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
    public function calculateCommitteeAverageNote(Projects $project)
    {
        $projectRating = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsNotes')->findOneBy(['idProject' => $project]);

        return round(
            $projectRating->getPerformanceFianciereComite() * 0.2
            + $projectRating->getMarcheOpereComite() * 0.2
            + $projectRating->getDirigeanceComite() * 0.2
            + $projectRating->getIndicateurRisqueDynamiqueComite() * 0.4
            , 1);
    }
}
