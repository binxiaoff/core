<?php

namespace Unilend\Bundle\FrontBundle\Service;


use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class LenderAccountDisplayManager
{
    /** @var  EntityManager */
    private $entityManager;


    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getLenderActivityForProject($projectId, \lenders_accounts $lenderAccount)
    {
        /** @var \projects $project */
        $project = $this->entityManager->getRepository('projects');
        $project->get($projectId);
        /** @var \projects_status $projectStatus */
        $projectStatus = $this->entityManager->getRepository('projects_status');
        $projectStatus->getLastStatut($project->id_project);

        $lenderIsInvolved = $this->isLenderInvolvedInProject($project, $lenderAccount);

        if (false === $lenderIsInvolved) {
            $lenderActivity['isInvolved'] = $lenderIsInvolved;
        } else {
            $lenderActivity = [
                'isInvolved' => $lenderIsInvolved,
                'offers'     => $this->getBidInformationForProject($project, $lenderAccount)
            ];

            if ($projectStatus->status >= \projects_status::FUNDE) {
                $lenderActivity['loans'] = $this->getLoanInformationForProject($project, $lenderAccount);
            }
        }

        return $lenderActivity;
    }

    public function getBidInformationForProject(\projects $project, \lenders_accounts $lenderAccount)
    {
        /** @var \bids $bids */
        $bids = $this->entityManager->getRepository('bids');
        $allBidsOnProject = $bids->select('id_lender_account = "' . $lenderAccount->id_lender_account . '" AND id_project = ' . $project->id_project);

        $lenderInformation = [
            'inprogress' => $bids->countBidsOnProjectByStatusForLender($lenderAccount->id_lender_account, $project->id_project, \bids::STATUS_BID_PENDING),
            'rejected'   => $bids->countBidsOnProjectByStatusForLender($lenderAccount->id_lender_account, $project->id_project, \bids::STATUS_BID_REJECTED),
            'accepted'   => $bids->countBidsOnProjectByStatusForLender($lenderAccount->id_lender_account, $project->id_project, \bids::STATUS_BID_ACCEPTED),
            'all'        => $allBidsOnProject,
            'autolend'   => $bids->counter('id_lender_account = ' . $lenderAccount->id_lender_account . ' AND id_project = ' . $project->id_project . ' AND id_autobid != 0'),
            'total'      => $bids->counter('id_lender_account = ' . $lenderAccount->id_lender_account . ' AND id_project = ' . $project->id_project),
            'offerIds'   => array_column($allBidsOnProject, 'id_bid')
        ];

        return $lenderInformation;
    }

    public function isLenderInvolvedInProject(\projects $project, \lenders_accounts $lenderAccount)
    {
        /** @var \bids $bids */
        $bids = $this->entityManager->getRepository('bids');
        return $bids->exist($project->id_project, 'id_lender_account = ' . $lenderAccount->id_lender_account. ' AND id_project ');

    }

    public function getLoanInformationForProject(\projects $project, \lenders_accounts $lenderAccount)
    {
        /** @var \loans $loans */
        $loans = $this->entityManager->getRepository('loans');
        /** @var \echeanciers $repaymentSchedule */
        $repaymentSchedule = $this->entityManager->getRepository('echeanciers');

        $loanInfo = [];

        $loanInfo['amountAlreadyPaidBack'] = $repaymentSchedule->sumARembByProject($lenderAccount->id_lender_account, $project->id_project . ' AND status_ra = 0') + $repaymentSchedule->sumARembByProjectCapital($lenderAccount->id_lender_account, $project->id_project . ' AND status_ra = 1');
        $loanInfo['remainingToBeRepaid']   = $repaymentSchedule->getSumRestanteARembByProject($lenderAccount->id_lender_account, $project->id_project);
        $loanInfo['remainingMonths']       = $repaymentSchedule->counterPeriodRestantes($lenderAccount->id_lender_account, $project->id_project);
        $loanInfo['myLoanOnProject']       = $loans->getBidsValid($project->id_project, $lenderAccount->id_lender_account);
        $loanInfo['myAverageLoanRate']     = $loans->getAvgLoansPreteur($project->id_project, $lenderAccount->id_lender_account);
        $loanInfo['percentageRecovered']   = $loanInfo['amountAlreadyPaidBack'] / $loanInfo['myLoanOnProject']['solde'] * 100;

        return $loanInfo;
    }

}