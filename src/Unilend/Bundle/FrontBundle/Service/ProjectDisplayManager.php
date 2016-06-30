<?php

namespace Unilend\Bundle\FrontBundle\Service;


use Unilend\Bundle\CoreBusinessBundle\Service\LenderManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class ProjectDisplayManager
{

    /** @var  EntityManager */
    private $entityManager;
    /** @var LenderManager  */
    private $lenderManager;

    public function __construct(EntityManager $entityManager, LenderManager $lenderManager)
    {
        $this->entityManager = $entityManager;
        $this->lenderManager = $lenderManager;
    }


    public function getProjectsForDisplay(array $aProjectStatus, $sOrderBy, $aRateRange, $iClientID = null)
    {
        /** @var \projects $projects */
        $projects  = $this->entityManager->getRepository('projects');
        /** @var \companies $company */
        $company = $this->entityManager->getRepository('companies');

        $aProjects = $projects->selectProjectsByStatus(implode(',', $aProjectStatus), null, $sOrderBy, $aRateRange);

        foreach ($projects->selectProjectsByStatus(implode(',', $aProjectStatus), null, $sOrderBy, $aRateRange) as $key => $project) {
            $aCompany                               = $company->select('id_company = ' . $project['id_company']);
            $aProjects[$key]['company']             = array_shift($aCompany);

            if (isset($iClientID)) {
                $aProjects[$key]['currentUser'] = $this->getClientBidsForProject($iClientID, $project['id_project']);
            }
        }

        return $aProjects;
    }

    public function getClientBidsForProject($clientId, $projectId)
    {
        /** @var \bids $bids */
        $bids = $this->entityManager->getRepository('bids');

        /** @var \lenders_accounts $lendersAccount */
        $lendersAccount = $this->entityManager->getRepository('lenders_accounts');
        $lendersAccount->get($clientId, 'id_client_owner');

        $aCurrentUserInformation = [
            'isInvoled' => $this->lenderManager->hasBidOnProject($lendersAccount, $projectId),
            'offers'    => [
                'inprogress' => $bids->countBidsOnProjectByStatusForLender($lendersAccount->id_lender_account, $projectId, \bids::STATUS_BID_PENDING),
                'rejected'   => $bids->countBidsOnProjectByStatusForLender($lendersAccount->id_lender_account, $projectId, \bids::STATUS_BID_REJECTED),
                'accepted'   => $bids->countBidsOnProjectByStatusForLender($lendersAccount->id_lender_account, $projectId, \bids::STATUS_BID_ACCEPTED),
                'autolend'   => $bids->counter('id_lender_account = ' . $lendersAccount->id_lender_account . ' AND id_project = ' . $projectId . ' AND id_autobid != 0'),
                'total'      => $bids->counter('id_lender_account = ' . $lendersAccount->id_lender_account . ' AND id_project = ' . $projectId)
            ]
        ];

        return $aCurrentUserInformation;
    }

}
