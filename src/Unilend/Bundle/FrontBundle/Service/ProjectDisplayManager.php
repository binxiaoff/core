<?php

namespace Unilend\Bundle\FrontBundle\Service;


use Symfony\Component\Validator\Constraints\DateTime;
use Unilend\Bundle\CoreBusinessBundle\Service\LenderManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class ProjectDisplayManager
{

    /** @var  EntityManager */
    private $entityManager;


    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getProjectsForDisplay(array $projectStatus, $orderBy, $rateRange,  $start = null, $limit = null, $clientID = null)
    {
        /** @var \projects $projects */
        $projects  = $this->entityManager->getRepository('projects');
        /** @var \companies $company */
        $company = $this->entityManager->getRepository('companies');

        $aProjects = $projects->selectProjectsByStatus(implode(',', $projectStatus), null, $orderBy, $rateRange, $start, $limit, false);

        foreach ($aProjects as $key => $project) {
            $aCompany                               = $company->select('id_company = ' . $project['id_company']);
            $aProjects[$key]['company']             = array_shift($aCompany);
            $aProjects[$key]['category']            = $aProjects[$key]['company']['sector'];
            $aProjects[$key]['number_bids']         = '';//TODO count bids on project

            if (isset($clientID)) {
                $aProjects[$key]['currentUser'] = $this->getClientBidsForProjectList($clientID, $project['id_project']);
            }
        }

        return $aProjects;
    }

    public function getClientBidsForProjectList($clientId, $projectId)
    {
        /** @var \bids $bids */
        $bids = $this->entityManager->getRepository('bids');

        /** @var \lenders_accounts $lendersAccount */
        $lendersAccount = $this->entityManager->getRepository('lenders_accounts');
        $lendersAccount->get($clientId, 'id_client_owner');

        $aCurrentUserInformation = [
            'isInvoled' => $bids->exist($projectId, 'id_lender_account = ' . $lendersAccount->id_lender_account. ' AND id_project '),
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

    public function getProjectInformationForDisplay($slug, $clientId = null)
    {
        /** @var \projects $projects */
        $projects = $this->entityManager->getRepository('projects');
        /** @var \companies $companies */
        $companies = $this->entityManager->getRepository('companies');
        /** @var \bids $bids */
        $bids = $this->entityManager->getRepository('bids');
        /** @var \projects_status $projectStatus */
        $projectStatus = $this->entityManager->getRepository('projects_status');
        /** @var \loans $loans */
        $loans = $this->entityManager->getRepository('loans');
        /** @var array $project */
        $project = $projects->select('slug = "' . $slug . '"');
        $project = array_shift($project);
        /** @var array $company */
        $company = $companies->select('id_company = ' . $project['id_company']);
        $company = array_shift($company);

        $projectStatus->getLastStatut($project['id_project']);

        $alreadyFunded = $bids->getSoldeBid($project['id_project']);

        $project['categoryId']           = $company['sector'];
        $project['costFunded']           = $alreadyFunded;
        $project['costRemaining']        = $project['amount'] - $alreadyFunded;
        $project['percentFunded']        = $alreadyFunded / $project['amount'] * 100;
        $project['company']              = $company;
        $project['avg_rate']             = $projects->getAverageInterestRate($project['id_project']);
        $project['totalLenders']         = (\projects_status::EN_FUNDING == $projectStatus->status) ? $bids->countLendersOnProject($project['id_project']) : $loans->getNbPreteurs($project['id_project']);
        $project['status'] = $projectStatus->status;
        $roject['number_bids']         = '';//TODO count bids on project



//        if ($alreadyFunded >= $project['amount']) {
//            $this->payer                = $this->projects->amount;
//            $this->resteApayer          = 0;
//            $this->pourcentage          = 100;
//            $this->decimalesPourcentage = 0;
//            $this->txLenderMax          = $this->bids->getProjectMaxRate($this->projects);
//        } else {
//            $this->payer                = $this->soldeBid;
//            $this->resteApayer          = $this->projects->amount - $this->soldeBid;
//            $this->pourcentage          = (1 - $this->resteApayer / $this->projects->amount) * 100;
//            $this->decimalesPourcentage = 1;
//            $this->txLenderMax          = 10;
//        }

//
//        if ($this->projects_status->status == \projects_status::EN_FUNDING) {
//            $this->date_retrait  = $this->dates->formatDateComplete($this->projects->date_retrait);
//            $this->heure_retrait = substr($this->heureFinFunding, 0, 2);
//        } else {
//            $this->date_retrait  = $this->dates->formatDateComplete($this->projects->date_fin);
//            $this->heure_retrait = $this->dates->formatDate($this->projects->date_fin, 'G');
//        }



        return $project;
    }

    public function getClientBidsOnProject($clientId, $projectId)
    {
        /** @var \bids $bids */
        $bids = $this->entityManager->getRepository('bids');

        /** @var \lenders_accounts $lendersAccount */
        $lendersAccount = $this->entityManager->getRepository('lenders_accounts');
        $lendersAccount->get($clientId, 'id_client_owner');

        return $bids->select('id_lender_account = "' . $lendersAccount->id_lender_account . '" AND id_project = ' . $projectId);
    }

    public function getProjectFinancialData(\projects $project)
    {
        /** @var \companies $company */
        $company = $this->entityManager->getRepository('companies');
        /** @var \companies_actif_passif $companyAssets */
        $companyAssets = $this->entityManager->getRepository('companies_actif_passif');
        /** @var \companies_bilans $companyBalanceSheet */
        $companyBalanceSheet = $this->entityManager->getRepository('companies_bilans');

        $balanceSheets = [];
        $annualAccountsIds = [];

        foreach ($companyBalanceSheet->select('id_company = "' . $project->id_company . '" AND cloture_exercice_fiscal <= (SELECT cloture_exercice_fiscal FROM companies_bilans WHERE id_bilan = ' . $project->id_dernier_bilan . ')', 'cloture_exercice_fiscal DESC', 0, 3) as $aAnnualAccounts) {
            $balanceSheets[]     = $aAnnualAccounts;
            $annualAccountsIds[] = $aAnnualAccounts['id_bilan'];
        }

        $totalYearlyAssets = array();
        $totalYearlyDebts  = array();
        $listAP            = $companyAssets->select('id_bilan IN (' . implode(', ', $annualAccountsIds) . ')', 'FIELD(id_bilan, ' . implode(', ', $annualAccountsIds) . ') ASC');

        foreach ($listAP as $ap) {
            $totalYearlyAssets[]  = $ap['immobilisations_corporelles']
                + $ap['immobilisations_incorporelles']
                + $ap['immobilisations_financieres']
                + $ap['stocks']
                + $ap['creances_clients']
                + $ap['disponibilites']
                + $ap['valeurs_mobilieres_de_placement']
                + $ap['comptes_regularisation_actif'];
            $totalYearlyDebts[] = $ap['capitaux_propres']
                + $ap['provisions_pour_risques_et_charges']
                + $ap['amortissement_sur_immo']
                + $ap['dettes_financieres']
                + $ap['dettes_fournisseurs']
                + $ap['autres_dettes']
                + $ap['comptes_regularisation_passif'];
        }

        /** @var \settings $setting */
        $setting = $this->entityManager->getRepository('settings');
        $setting->get('Entreprises fundés au passage du risque lot 1', 'type');
        $fundedCompanies = explode(',', $setting->value);
        $previousRiskProject = in_array($company->id_company, $fundedCompanies);

        $accountData = [
            'balanceSheets'       => $balanceSheets,
            'annualAccountsIds'   => $annualAccountsIds,
            'totalYearlyAssets'   => $totalYearlyAssets,
            'totalYearlyDebts'    => $totalYearlyDebts,
            'previousRiskProject' => $previousRiskProject
        ];

        return $accountData;

    }

    public function getClientInformationOnProject(\projects $project, $clientId)
    {
        /** @var \lenders_accounts $lendersAccount */
        $lendersAccount = $this->entityManager->getRepository('lenders_accounts');
        $lendersAccount->get($clientId, 'id_client_owner');

        /** @var \loans $loans */
        $loans = $this->entityManager->getRepository('loans');
        /** @var \echeanciers $repaymentSchedule */
        $repaymentSchedule = $this->entityManager->getRepository('echeanciers');
        /** @var \projects_status_history $projectStatusHistory */
        $projectStatusHistory = $this->entityManager->getRepository('projects_status_history');

        $this->bidsvalid        = $loans->getBidsValid($project->id_project, $lendersAccount->id_lender_account);
        $this->AvgLoansPreteur  = $loans->getAvgLoansPreteur($project->id_project, $lendersAccount->id_lender_account);
        $this->aStatusHistory   = $projectStatusHistory->getHistoryDetails($project->id_project);
        $this->sumRemb          = $repaymentSchedule->sumARembByProject($lendersAccount->id_lender_account, $project->id_project . ' AND status_ra = 0') + $repaymentSchedule->sumARembByProjectCapital($lendersAccount->id_lender_account, $project->id_project . ' AND status_ra = 1');
        $this->sumRestanteARemb = $repaymentSchedule->getSumRestanteARembByProject($lendersAccount->id_lender_account, $project->id_project);
        $this->nbPeriod         = $repaymentSchedule->counterPeriodRestantes($lendersAccount->id_lender_account, $project->id_project);

    }

    public function getProjectFundingStatistic(\projects $project, $status)
    {
        /** @var \loans $loans */
        $loans = $this->entityManager->getRepository('loans');

        $startFundingPeriod = ($project->date_publication_full != '0000-00-00 00:00:00') ? new \DateTime($project->date_publication_full) : new \DateTime($project->date_publication . ' 00:00:00');
        $endFundingPeriod   = ($project->date_retrait_full != '0000-00-00 00:00:00') ? new \DateTime($project->date_retrait_full) : new \DateTime($project->date_fin);

        $fundingStatistics['fundingTime']  = $startFundingPeriod->diff($endFundingPeriod);
        $fundingStatistics['NumberLender'] = $loans->getNbPreteurs($project->id_project);
        $fundingStatistics['AvgRate']      = $project->getAverageInterestRate($project->id_project, $status);

        return $fundingStatistics;
    }

    public function getClientLoansOnProject($clientId, $projectId)
    {
        /** @var \loans $loans */
        $loans = $this->entityManager->getRepository('loans');

        /** @var \lenders_accounts $lendersAccount */
        $lendersAccount = $this->entityManager->getRepository('lenders_accounts');
        $lendersAccount->get($clientId, 'id_client_owner');

        return $loans->sum('id_lender = "' . $lendersAccount->id_lender_account . '" AND id_project = ' . $projectId, 'amount');
    }


}
