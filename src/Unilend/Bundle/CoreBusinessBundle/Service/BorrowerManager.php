<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class BorrowerManager
{
    /**
     * @var EntityManager
     */
    private $oEntityManager;

    public function __construct(EntityManager $oEntityManager)
    {
        $this->oEntityManager = $oEntityManager;
    }

    /**
     * @param \projects|Projects $project
     *
     * @return string
     */
    public function getBorrowerBankTransferLabel($project)
    {
        if ($project instanceof \projects) {
            $projectId = $project->id_project;
            /** @var \companies $company */
            $company = $this->oEntityManager->getRepository('companies');
            $company->get($project->id_company);
            $siren = $company->siren;
        } elseif ($project instanceof Projects) {
            $projectId = $project->getIdProject();
            $siren     = $project->getIdCompany()->getSiren();
        } else {
            return '';
        }

        return 'UNILEND' . str_pad($projectId, 6, 0, STR_PAD_LEFT) . 'E' . trim($siren);
    }
}
