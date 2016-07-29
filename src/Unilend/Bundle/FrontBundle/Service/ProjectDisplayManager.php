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
            $aCompany                       = $company->select('id_company = ' . $project['id_company']);
            $aProjects[$key]['company']     = array_shift($aCompany);
            $aProjects[$key]['category']    = $aProjects[$key]['company']['sector'];
            $aProjects[$key]['number_bids'] = $bids->counter('id_project = ' . $project['id_project']);

            if (isset($clientID)) {
                /** @var \lenders_accounts $lenderAccount */
                $lenderAccount = $this->entityManager->getRepository('lenders_accounts');
                $lenderAccount->get($clientId, 'id_client_owner');

                $aProjects[$key]['currentUser']['offers']     = $this->lenderAccountDisplayManager->getBidInformationForProject($project['id_project'], $lenderAccount);
                $aProjects[$key]['currentUser']['isInvolved'] = false === empty($aProjects[$key]['currentUser']['offers']['offerIds']);
            }
        }

        return $aProjects;
    }

    public function getProjectInformationForDisplay(\projects $project)
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

        $projectData = [
            'projectId'            => $project->id_project,
            'hash'                 => $project->hash,
            'slug'                 => $project->slug,
            'amount'               => $project->amount,
            'duration'             => $project->period,
            'title'                => $project->title,
            'picture'              => $project->photo_projet,
            'introduction'         => $project->nature_project,
            'projectDescription'   => $project->objectif_loan,
            'companyDescription'   => $project->presentation_company,
            'repaymentDescription' => $project->means_repayment,
            'startDate'            => $project->date_publication_full,
            'endDate'              => $project->date_retrait_full,
            'fundedDate'           => $project->date_funded,
            'projectNeed'          => $project->id_project_need,
            'risk'                 => $project->risk
        ];

        /** @var array $company */
        $company = $companies->select('id_company = ' . $project->id_company)[0];

        $projectStatus->getLastStatut($project->id_project);

        $alreadyFunded = $bids->getSoldeBid($project->id_project);
        $totalLenders  = (\projects_status::EN_FUNDING == $projectStatus->status) ? $bids->countLendersOnProject($project->id_project) : $loans->getNbPreteurs($project->id_project);
        $navigation    = $project->positionProject($project->id_project, implode(',', $this->projectsStatus), 'lestatut ASC, IF(lestatut = 2, p.date_retrait_full ,"") DESC, IF(lestatut = 1, p.date_retrait_full ,"") ASC, projects_status.status DESC');

        if ($alreadyFunded >= $project->amount) {
            $projectData['costFunded']    = $project->amount;
            $projectData['costRemaining'] = 0;
            $projectData['percentFunded'] = 100;
            $projectData['maxValidRate']  = $bids->getProjectMaxRate($project);
        } else {
            $projectData['costFunded']    = $alreadyFunded;
            $projectData['costRemaining'] = $project->amount - $alreadyFunded;
            $projectData['percentFunded'] = $alreadyFunded / $project->amount * 100;
            $projectData['maxValidRate']  = \bids::BID_RATE_MAX;
        }

        $projectData['sectorId']     = $company['sector'];
        $projectData['company']      = $company;
        $projectData['averageRate']  = $project->getAverageInterestRate($project->id_project);
        $projectData['totalLenders'] = $totalLenders;
        $projectData['status']       = $projectStatus->status;
        $projectData['navigation']   = $navigation;
        // @todo
        $projectData['latitude']     = 45;
        $projectData['longitude']    = 0;

        $now        = new \DateTime('NOW');
        $projectEnd = new \DateTime($project->date_retrait_full);

        $projectData['daysLeft'] = $now->diff($projectEnd)->days;

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
            $bidsStatistics = $this->projectManager->getBidsStatistics($project);
            $projectData['bidsStatistics'] = $bidsStatistics;
            $projectData['meanBidAmount']  = round(array_sum(array_column($bidsStatistics, 'amount_total')) / array_sum(array_column($bidsStatistics, 'nb_bids')), 2);
        } else {
            $projectData['fundingStatistics'] = $this->getProjectFundingStatistic($project, $projectStatus->status);
        }

        return $projectData;
    }

    public function getProjectFinancialData(\projects $project)
    {
        /** @var \companies $company */
        $company = $this->entityManager->getRepository('companies');
        /** @var \companies_actif_passif $assetsDebtsEntity */
        $assetsDebtsEntity = $this->entityManager->getRepository('companies_actif_passif');
        /** @var \companies_bilans $balanceSheetEntity */
        $balanceSheetEntity = $this->entityManager->getRepository('companies_bilans');
        /** @var \settings $setting */
        $setting = $this->entityManager->getRepository('settings');

        $setting->get('Entreprises fundés au passage du risque lot 1', 'type');
        $isBeforeRiskProject = in_array($company->id_company, explode(',', $setting->value));

        $finance                = [];
        $previousBalanceSheetId = null;
        $balanceSheets          = $balanceSheetEntity->select('id_company = "' . $project->id_company . '" AND cloture_exercice_fiscal <= (SELECT cloture_exercice_fiscal FROM companies_bilans WHERE id_bilan = ' . $project->id_dernier_bilan . ')', 'cloture_exercice_fiscal DESC', 0, 3);

        foreach ($balanceSheets as $balanceSheet) {
            $finance[$balanceSheet['id_bilan']] = [
                'closingDate'   => $balanceSheet['cloture_exercice_fiscal'],
                'monthDuration' => $balanceSheet['duree_exercice_fiscal'],
                'balanceSheet'  => [],
                'assets'        => [],
                'debts'         => [],
            ];

            $finance[$balanceSheet['id_bilan']]['balanceSheet']['ca']                          = [$balanceSheet['ca']];
            $finance[$balanceSheet['id_bilan']]['balanceSheet']['resultat-brute-exploitation'] = [$balanceSheet['resultat_brute_exploitation']];
            $finance[$balanceSheet['id_bilan']]['balanceSheet']['resultat-exploitation']       = [$balanceSheet['resultat_exploitation']];

            if (false === $isBeforeRiskProject) {
                $finance[$balanceSheet['id_bilan']]['balanceSheet']['resultat-financier']      = [$balanceSheet['resultat_financier']];
                $finance[$balanceSheet['id_bilan']]['balanceSheet']['produit-exceptionnel']    = [$balanceSheet['produit_exceptionnel']];
                $finance[$balanceSheet['id_bilan']]['balanceSheet']['charges-exceptionnelles'] = [$balanceSheet['charges_exceptionnelles']];
                $finance[$balanceSheet['id_bilan']]['balanceSheet']['resultat-exceptionnel']   = [$balanceSheet['resultat_exceptionnel']];
                $finance[$balanceSheet['id_bilan']]['balanceSheet']['resultat-net']            = [$balanceSheet['resultat_net']];
            }

            $finance[$balanceSheet['id_bilan']]['balanceSheet']['investissements'] = [$balanceSheet['investissements']];

            if (null !== $previousBalanceSheetId) {
                foreach ($finance[$balanceSheet['id_bilan']]['balanceSheet'] as $name => $currentBalanceSheet) {
                    $finance[$previousBalanceSheetId]['balanceSheet'][$name][1] = empty($finance[$balanceSheet['id_bilan']]['balanceSheet'][$name][0]) ? null : round(($finance[$previousBalanceSheetId]['balanceSheet'][$name][0] - $finance[$balanceSheet['id_bilan']]['balanceSheet'][$name][0]) / abs($finance[$balanceSheet['id_bilan']]['balanceSheet'][$name][0]) * 100, 1);
                }
            }

            $previousBalanceSheetId = $balanceSheet['id_bilan'];
        }

        $previousBalanceSheetId = null;
        $assetsDebts            = $assetsDebtsEntity->select('id_bilan IN (' . implode(', ', array_keys($finance)) . ')', 'FIELD(id_bilan, ' . implode(', ', array_keys($finance)) . ') ASC');

        foreach ($assetsDebts as $balanceSheetAssetsDebts) {
            $finance[$balanceSheetAssetsDebts['id_bilan']]['assets']['immobilisations-corporelles']     = [$balanceSheetAssetsDebts['immobilisations_corporelles']];
            $finance[$balanceSheetAssetsDebts['id_bilan']]['assets']['immobilisations-incorporelles']   = [$balanceSheetAssetsDebts['immobilisations_incorporelles']];
            $finance[$balanceSheetAssetsDebts['id_bilan']]['assets']['immobilisations-financieres']     = [$balanceSheetAssetsDebts['immobilisations_financieres']];
            $finance[$balanceSheetAssetsDebts['id_bilan']]['assets']['stocks']                          = [$balanceSheetAssetsDebts['stocks']];
            $finance[$balanceSheetAssetsDebts['id_bilan']]['assets']['creances-clients']                = [$balanceSheetAssetsDebts['creances_clients']];
            $finance[$balanceSheetAssetsDebts['id_bilan']]['assets']['disponibilites']                  = [$balanceSheetAssetsDebts['disponibilites']];
            $finance[$balanceSheetAssetsDebts['id_bilan']]['assets']['valeurs-mobilieres-de-placement'] = [$balanceSheetAssetsDebts['valeurs_mobilieres_de_placement']];
            $finance[$balanceSheetAssetsDebts['id_bilan']]['assets']['comptes-regularisation-actif']    = [$balanceSheetAssetsDebts['comptes_regularisation_actif']];
            $finance[$balanceSheetAssetsDebts['id_bilan']]['assets']['total']                           = [array_sum(array_column($finance[$balanceSheetAssetsDebts['id_bilan']]['assets'], 0))];

            $finance[$balanceSheetAssetsDebts['id_bilan']]['debts']['capitaux-propres']                   = [$balanceSheetAssetsDebts['capitaux_propres']];
            $finance[$balanceSheetAssetsDebts['id_bilan']]['debts']['provisions-pour-risques-et-charges'] = [$balanceSheetAssetsDebts['provisions_pour_risques_et_charges']];
            $finance[$balanceSheetAssetsDebts['id_bilan']]['debts']['amortissement-sur-immo']             = [$balanceSheetAssetsDebts['amortissement_sur_immo']];
            $finance[$balanceSheetAssetsDebts['id_bilan']]['debts']['dettes-financieres']                 = [$balanceSheetAssetsDebts['dettes_financieres']];
            $finance[$balanceSheetAssetsDebts['id_bilan']]['debts']['dettes-fournisseurs']                = [$balanceSheetAssetsDebts['dettes_fournisseurs']];
            $finance[$balanceSheetAssetsDebts['id_bilan']]['debts']['autres-dettes']                      = [$balanceSheetAssetsDebts['autres_dettes']];
            $finance[$balanceSheetAssetsDebts['id_bilan']]['debts']['comptes-regularisation-passif']      = [$balanceSheetAssetsDebts['comptes_regularisation_passif']];
            $finance[$balanceSheetAssetsDebts['id_bilan']]['debts']['total']                              = [array_sum(array_column($finance[$balanceSheetAssetsDebts['id_bilan']]['debts'], 0))];

            if (null !== $previousBalanceSheetId) {
                foreach ($finance[$balanceSheetAssetsDebts['id_bilan']]['assets'] as $name => $currentBalanceSheetAssets) {
                    $finance[$previousBalanceSheetId]['assets'][$name][1] = empty($finance[$balanceSheetAssetsDebts['id_bilan']]['assets'][$name][0]) ? null : round(($finance[$previousBalanceSheetId]['assets'][$name][0] - $finance[$balanceSheetAssetsDebts['id_bilan']]['assets'][$name][0]) / abs($finance[$balanceSheetAssetsDebts['id_bilan']]['assets'][$name][0]) * 100, 1);
                }
                foreach ($finance[$balanceSheetAssetsDebts['id_bilan']]['debts'] as $name => $currentBalanceSheetDebts) {
                    $finance[$previousBalanceSheetId]['debts'][$name][1] = empty($finance[$balanceSheetAssetsDebts['id_bilan']]['debts'][$name][0]) ? null : round(($finance[$previousBalanceSheetId]['debts'][$name][0] - $finance[$balanceSheetAssetsDebts['id_bilan']]['debts'][$name][0]) / abs($finance[$balanceSheetAssetsDebts['id_bilan']]['debts'][$name][0]) * 100, 1);
                }
            }

            $previousBalanceSheetId = $balanceSheetAssetsDebts['id_bilan'];
        }

        if (array_column(array_column(array_column($finance, 'assets'), 'comptes-regularisation-actif'), 0) == [0, 0, 0]) {
            foreach ($finance as $balanceSheetId => $balanceSheet) {
                unset($finance[$balanceSheetId]['assets']['comptes-regularisation-actif']);
            }
        }

        if (array_column(array_column(array_column($finance, 'debts'), 'comptes-regularisation-passif'), 0) == [0, 0, 0]) {
            foreach ($finance as $balanceSheetId => $balanceSheet) {
                unset($finance[$balanceSheetId]['debts']['comptes-regularisation-passif']);
            }
        }

        return $finance;
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
