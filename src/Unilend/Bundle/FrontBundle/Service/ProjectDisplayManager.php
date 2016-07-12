<?php

namespace Unilend\Bundle\FrontBundle\Service;


use Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class ProjectDisplayManager
{

    /** @var  EntityManager */
    private $entityManager;
    /** @var  ProjectManager */
    private $projectManager;
    /** @var  LenderAccountDisplayManager */
    private $lenderAccountDisplayManager;
    /** @var  array */
    private $projectsStatus;


    public function __construct(EntityManager $entityManager, ProjectManager $projectManager, LenderAccountDisplayManager $lenderAccountDisplayManager)
    {
        $this->entityManager  = $entityManager;
        $this->projectManager = $projectManager;
        $this->lenderAccountDisplayManager = $lenderAccountDisplayManager;
        $this->projectsStatus = [
            \projects_status::EN_FUNDING,
            \projects_status::FUNDE,
            \projects_status::FUNDING_KO,
            \projects_status::REMBOURSEMENT,
            \projects_status::REMBOURSE,
            \projects_status::PROBLEME,
            \projects_status::RECOUVREMENT,
            \projects_status::DEFAUT,
            \projects_status::REMBOURSEMENT_ANTICIPE,
            \projects_status::PROBLEME_J_X,
            \projects_status::PROCEDURE_SAUVEGARDE,
            \projects_status::REDRESSEMENT_JUDICIAIRE,
            \projects_status::LIQUIDATION_JUDICIAIRE
        ];
    }

    public function getProjectsStatus()
    {
        return $this->projectsStatus;
    }

    public function getProjectsForDisplay($projectStatus = array(), $orderBy = null, $rateRange = array(),  $start = null, $limit = null, $clientId = null)
    {
        /** @var \projects $projects */
        $projects  = $this->entityManager->getRepository('projects');
        /** @var \companies $company */
        $company = $this->entityManager->getRepository('companies');
        /** @var \bids $bids */
        $bids = $this->entityManager->getRepository('bids');

        if (empty($projectStatus)) {
            $projectStatus = $this->projectsStatus;
        }

        $aProjects = $projects->selectProjectsByStatus(implode(',', $projectStatus), null, $orderBy, $rateRange, $start, $limit, false);

        foreach ($aProjects as $key => $project) {
            $projects->get($project['id_project']);
            $aCompany                               = $company->select('id_company = ' . $project['id_company']);
            $aProjects[$key]['company']             = array_shift($aCompany);
            $aProjects[$key]['category']            = $aProjects[$key]['company']['sector'];
            $aProjects[$key]['number_bids']         = $bids->counter('id_project = ' . $projects->id_project);

            if (isset($clientID)) {
                /** @var \lenders_accounts $lenderAccount */
                $lenderAccount = $this->entityManager->getRepository('lenders_accounts');
                $lenderAccount->get($clientId, 'id_client_owner');
                $aProjects[$key]['currentUser']['offers']     = $this->lenderAccountDisplayManager->getBidInformationForProject($projects, $lenderAccount);
                $aProjects[$key]['currentUser']['isInvolved'] = $this->lenderAccountDisplayManager->isLenderInvolvedInProject($projects, $lenderAccount);
            }
        }

        return $aProjects;
    }

    public function getProjectInformationForDisplay(\projects $project, $clientId = null)
    {
        /** @var \companies $companies */
        $companies = $this->entityManager->getRepository('companies');
        /** @var \bids $bids */
        $bids = $this->entityManager->getRepository('bids');
        /** @var \projects_status $projectStatus */
        $projectStatus = $this->entityManager->getRepository('projects_status');
        /** @var \loans $loans */
        $loans = $this->entityManager->getRepository('loans');
        /** @var \projects_status_history $projectStatusHistory */
        $projectStatusHistory = $this->entityManager->getRepository('projects_status_history');

        $projectData = $project->select('id_project = ' . $project->id_project);
        $projectData = array_shift($projectData);
        /** @var array $company */
        $company = $companies->select('id_company = ' . $project->id_company);
        $company = array_shift($company);

        $projectStatus->getLastStatut($project->id_project);

        $alreadyFunded = $bids->getSoldeBid($project->id_project);
        $totalLenders  = (\projects_status::EN_FUNDING == $projectStatus->status) ? $bids->countLendersOnProject($project->id_project) : $loans->getNbPreteurs($project->id_project);
        $navigation    = $project->positionProject($project->id_project, implode(',', $this->projectsStatus), 'lestatut ASC, IF(lestatut = 2, p.date_retrait_full ,"") DESC, IF(lestatut = 1, p.date_retrait_full ,"") ASC, projects_status.status DESC');

        $projectData['categoryId']    = $company['sector'];
        $projectData['costFunded']    = $alreadyFunded;
        $projectData['costRemaining'] = $project->amount - $alreadyFunded;
        $projectData['percentFunded'] = $alreadyFunded / $project->amount * 100;
        $projectData['company']       = $company;
        $projectData['avg_rate']      = $project->getAverageInterestRate($project->id_project);
        $projectData['totalLenders']  = $totalLenders;
        $projectData['status']        = $projectStatus->status;
        $projectData['number_bids']   = '';//TODO count bids on project
        $projectData['navigation']    = $navigation;

        $now = new \DateTime('NOW');
        $projectEnd = new \DateTime($project->date_retrait_full);
        if ($projectEnd  <= $now && $projectStatus->status == \projects_status::EN_FUNDING) {
            $projectData['projectPending'] = true;
        }

        if ($projectStatus->status >= \projects_status::REMBOURSEMENT) {
            $projectData['statusHistory']   = $projectStatusHistory->getHistoryDetails($project->id_project);
        }

        if (in_array($projectStatus->status, [\projects_status::REMBOURSE, \projects_status::REMBOURSEMENT_ANTICIPE])) {
            $lastStatusHistory            = $projectStatusHistory->select('id_project = ' . $project->id_project, 'id_project_status_history DESC', 0, 1);
            $lastStatusHistory            = array_shift($lastStatusHistory);
            $projectData['dateLastRepayment'] = date('d/m/Y', strtotime($lastStatusHistory['added']));
        }

        if (\projects_status::EN_FUNDING == $projectStatus->status) {

            //TODO once decision taken for carnet d'ordre change names of variables in template make things more redable ...
            $bidsOnProject = $bids->select('id_project = ' . $project->id_project, 'added ASC');
            $projectData['alloffersOverview'] = '';
            //$templateVariables['charts']['projectOffers'] = $highchartsService->getBidsChartSetting($activeBidsByRate, 6);
            $bidsStatistics = $this->projectManager->getBidsStatistics($project);
            //$meanBidAmount  = round(array_sum(array_column($bidsStatistics, 'amount_total')) / array_sum(array_column($bidsStatistics, 'nb_bids')), 2);
            $activeBidsByRate = $bids->getNumberActiveBidsByRate($project->id_project);
        } else {
            $projectData['fundingStatistics'] = $this->getProjectFundingStatistic($project, $projectStatus->status);
        }



        return $projectData;
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

    public function getTotalNumberOfDisplayedProjects()
    {
        /** @var \projects $projects */
        $projects  = $this->entityManager->getRepository('projects');
        return $projects->countSelectProjectsByStatus(implode(',', $this->projectsStatus) . ', ' . \projects_status::PRET_REFUSE, ' AND p.status = 0 AND p.display = ' . \projects::DISPLAY_PROJECT_ON);

    }

}
