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

    public function getLenderActivityForProject(\lenders_accounts $lenderAccount, \projects $project)
    {
        /** @var \projects_status $projectStatus */
        $projectStatus = $this->entityManager->getRepository('projects_status');
        $projectStatus->getLastStatut($project->id_project);

        $lenderActivity = [
            'bids' => $this->getBidInformationForProject($project->id_project, $lenderAccount)
        ];

        if ($projectStatus->status >= \projects_status::FUNDE) {
            $lenderActivity['loans'] = $this->getLoanInformationForProject($project, $lenderAccount);
        }

        return $lenderActivity;
    }

    public function getBidInformationForProject($projectId, \lenders_accounts $lenderAccount)
    {
        /** @var \bids $bids */
        $bids       = $this->entityManager->getRepository('bids');
        $lenderBids = $bids->select('id_lender_account = ' . $lenderAccount->id_lender_account . ' AND id_project = ' . $projectId);

        return [
            'all'        => $lenderBids,
            'inprogress' => array_filter($lenderBids, function ($bid) {
                return $bid['status'] == \bids::STATUS_BID_PENDING;
            }),
            'rejected'   => array_filter($lenderBids, function ($bid) {
                return $bid['status'] == \bids::STATUS_BID_REJECTED;
            }),
            'accepted'   => array_filter($lenderBids, function ($bid) {
                return $bid['status'] == \bids::STATUS_BID_ACCEPTED;
            }),
            'autolend'   => array_filter($lenderBids, function ($bid) {
                return $bid['id_autobid'] > 0;
            }),
            'count'      => count($lenderBids),
            'offerIds'   => array_column($lenderBids, 'id_bid')
        ];
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
        $loanInfo['percentageRecovered']   = $loanInfo['myLoanOnProject']['solde']> 0 ? $loanInfo['amountAlreadyPaidBack'] / $loanInfo['myLoanOnProject']['solde'] * 100 : 0;

        return $loanInfo;
    }
}
