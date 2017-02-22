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
        /** @var \projects_status $projectStatus */
        $projectStatus = $this->entityManager->getRepository('projects_status');

        switch ($project->status) {
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
