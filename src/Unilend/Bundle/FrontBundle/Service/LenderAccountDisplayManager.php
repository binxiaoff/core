<?php
namespace Unilend\Bundle\FrontBundle\Service;

use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class LenderAccountDisplayManager
{
    /** @var EntityManager */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getActivityForProject(\lenders_accounts $lenderAccount, $projectId)
    {
        /** @var \projects_status $projectStatus */
        $projectStatus = $this->entityManager->getRepository('projects_status');
        $projectStatus->getLastStatut($projectId);

        $lenderActivity = [
            'bids' => $this->getBidsForProject($projectId, $lenderAccount)
        ];

        if ($projectStatus->status >= \projects_status::FUNDE) {
            $lenderActivity['loans'] = $this->getLoansForProject($projectId, $lenderAccount);
        }

        return $lenderActivity;
    }

    public function getBidsForProject($projectId, \lenders_accounts $lenderAccount)
    {
        /** @var \bids $bids */
        $bids       = $this->entityManager->getRepository('bids');
        $lenderBids = $bids->select('id_lender_account = ' . $lenderAccount->id_lender_account . ' AND id_project = ' . $projectId);

        return [
            'inprogress' => array_filter($lenderBids, function ($bid) {
                return $bid['status'] == \bids::STATUS_BID_PENDING;
            }),
            'rejected'   => array_filter($lenderBids, function ($bid) {
                return $bid['status'] == \bids::STATUS_BID_REJECTED;
            }),
            'accepted'   => array_filter($lenderBids, function ($bid) {
                return $bid['status'] == \bids::STATUS_BID_ACCEPTED;
            }),
            'autobid'    => [
                'inprogress' => array_filter($lenderBids, function ($bid) {
                    return $bid['id_autobid'] > 0 && $bid['status'] == \bids::STATUS_BID_PENDING;
                }),
                'rejected'   => array_filter($lenderBids, function ($bid) {
                    return $bid['id_autobid'] > 0 && $bid['status'] == \bids::STATUS_BID_REJECTED;
                }),
                'accepted'   => array_filter($lenderBids, function ($bid) {
                    return $bid['id_autobid'] > 0 && $bid['status'] == \bids::STATUS_BID_ACCEPTED;
                }),
                'count'      => count(array_filter($lenderBids, function ($bid) {
                    return $bid['id_autobid'] > 0;
                }))
            ],
            'count'      => count($lenderBids)
        ];
    }

    public function getLoansForProject($projectId, \lenders_accounts $lenderAccount)
    {
        /** @var \loans $loans */
        $loans = $this->entityManager->getRepository('loans');
        /** @var \echeanciers $repaymentSchedule */
        $repaymentSchedule = $this->entityManager->getRepository('echeanciers');

        $loanInfo = [];

        $loanInfo['amountAlreadyPaidBack'] = $repaymentSchedule->sumARembByProject($lenderAccount->id_lender_account, $projectId . ' AND status_ra = 0') + $repaymentSchedule->sumARembByProjectCapital($lenderAccount->id_lender_account, $projectId . ' AND status_ra = 1');
        $loanInfo['remainingToBeRepaid']   = $repaymentSchedule->getSumRestanteARembByProject($lenderAccount->id_lender_account, $projectId);
        $loanInfo['remainingMonths']       = $repaymentSchedule->counterPeriodRestantes($lenderAccount->id_lender_account, $projectId);
        $loanInfo['myLoanOnProject']       = $loans->getBidsValid($projectId, $lenderAccount->id_lender_account);
        $loanInfo['myAverageLoanRate']     = $loans->getAvgLoansPreteur($projectId, $lenderAccount->id_lender_account);
        $loanInfo['percentageRecovered']   = $loanInfo['myLoanOnProject']['solde']> 0 ? $loanInfo['amountAlreadyPaidBack'] / $loanInfo['myLoanOnProject']['solde'] * 100 : 0;

        return $loanInfo;
    }
}
