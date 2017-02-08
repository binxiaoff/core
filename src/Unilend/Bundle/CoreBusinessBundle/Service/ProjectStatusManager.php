<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class ProjectStatusManager
{
    /** @var EntityManager */
    private $entityManager;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param \projects $project
     *
     * @return array
     */
    public function getPossibleStatus(\projects $project)
    {
        /** @var \projects_status_history $projectStatusHistory */
        $projectStatusHistory = $this->entityManager->getRepository('projects_status_history');
        /** @var \projects_status $projectStatus */
        $projectStatus = $this->entityManager->getRepository('projects_status');

        switch ($project->status) {
            case \projects_status::ABANDONED:
                $formerStatus   = $projectStatusHistory->getBeforeLastStatus($project->id_project);
                $possibleStatus = [$formerStatus, $project->status];
                break;
            case \projects_status::COMPLETE_REQUEST:
            case \projects_status::COMMERCIAL_REVIEW:
                $nextStatus     = $projectStatus->getNextStatus($project->status);
                $possibleStatus = [\projects_status::ABANDONED, $project->status, $nextStatus];
                break;
            case \projects_status::PENDING_ANALYSIS:
            case \projects_status::ANALYSIS_REVIEW:
            case \projects_status::COMITY_REVIEW:
                $possibleStatus = [\projects_status::ABANDONED, $project->status];
                break;
            case \projects_status::PREP_FUNDING:
                $possibleStatus = [\projects_status::ABANDONED, \projects_status::PREP_FUNDING, \projects_status::A_FUNDER];
                break;
            case \projects_status::LIQUIDATION_JUDICIAIRE:
                $possibleStatus = [\projects_status::LIQUIDATION_JUDICIAIRE, \projects_status::DEFAUT];
                break;
            case \projects_status::REMBOURSEMENT_ANTICIPE:
            case \projects_status::REMBOURSE:
            case \projects_status::DEFAUT:
                return [];
            default:
                if ($project->status < \projects_status::REMBOURSEMENT) {
                    return [];
                }
                $possibleStatus = \projects_status::$afterRepayment;
                if ($key = array_search(\projects_status::DEFAUT, $possibleStatus)) {
                    unset($possibleStatus[$key]);
                }
                break;
        }

        return $projectStatus->select('status IN (' . implode(',' , $possibleStatus) . ')', 'status ASC');
    }
}
