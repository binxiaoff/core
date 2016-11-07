<?php
namespace Unilend\Bundle\FrontBundle\Service;

use Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class ProjectDisplayManager
{
    /** @var EntityManager */
    private $entityManager;
    /** @var ProjectManager */
    private $projectManager;
    /** @var LenderAccountDisplayManager */
    private $lenderAccountDisplayManager;
    /** @var array */
    private static $projectsStatus = [
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

    /**
     * @param EntityManager               $entityManager
     * @param ProjectManager              $projectManager
     * @param LenderAccountDisplayManager $lenderAccountDisplayManager
     */
    public function __construct(EntityManager $entityManager, ProjectManager $projectManager, LenderAccountDisplayManager $lenderAccountDisplayManager)
    {
        $this->entityManager               = $entityManager;
        $this->projectManager              = $projectManager;
        $this->lenderAccountDisplayManager = $lenderAccountDisplayManager;
    }

    /**
     * @param array                  $projectStatus
     * @param array                  $sort
     * @param int|null               $start
     * @param int|null               $limit
     * @param \lenders_accounts|null $lenderAccount
     * @return array
     */
    public function getProjectsList(array $projectStatus = [], array $sort = [], $start = null, $limit = null, \lenders_accounts $lenderAccount = null)
    {
        /** @var \projects $projectsEntity */
        $projectsEntity = $this->entityManager->getRepository('projects');
        /** @var \bids $bids */
        $bids = $this->entityManager->getRepository('bids');
        /** @var \projects $project */
        $project = $this->entityManager->getRepository('projects');

        if (empty($projectStatus)) {
            $projectStatus = self::$projectsStatus;
        }

        $projectsData = [];
        $projectList     = $projectsEntity->selectProjectsByStatus($projectStatus, ' AND p.display = ' . \projects::DISPLAY_PROJECT_ON, $sort, $start, $limit);

        foreach ($projectList as $item) {
            $project->get($item['id_project']);
            $projectsData[$project->id_project] = $this->getBaseData($project);
            $projectsData[$project->id_project]['bidsCount'] = $bids->counter('id_project = ' . $project->id_project);

            if (false === empty($lenderAccount->id_lender_account)) {
                $projectsData[$project->id_project]['lender']['bids'] = $this->lenderAccountDisplayManager->getBidsForProject($project->id_project, $lenderAccount);
            }
        }

        return $projectsData;
    }

    /**
     * @param \projects $project
     *
     * @return array
     */
    public function getBaseData(\projects $project)
    {
        /** @var \bids $bids */
        $bids = $this->entityManager->getRepository('bids');
        /** @var \loans $loans */
        $loans = $this->entityManager->getRepository('loans');
        /** @var \companies $company */
        $company = $this->entityManager->getRepository('companies');
        $company->get($project->id_company);

        $now = new \DateTime('NOW');
        $end = $this->projectManager->getProjectEndDate($project);

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
            'endDate'              => $end,
            'fundedDate'           => $project->date_funded,
            'projectNeed'          => $project->id_project_need,
            'risk'                 => $project->risk,
            'company'              => [
                'city'      => $company->city,
                'zip'       => $company->zip,
                'sectorId'  => $company->sector,
                'latitude'  => $company->latitude,
                'longitude' => $company->longitude
            ],
            'status'               => $project->status,
            'finished'             => ($project->status > \projects_status::EN_FUNDING || $end < $now),
            'averageRate'          => round($project->getAverageInterestRate(), 1),
            'totalLenders'         => (\projects_status::EN_FUNDING == $project->status) ? $bids->countLendersOnProject($project->id_project) : $loans->getNbPreteurs($project->id_project),
            'fundingDuration'      => (\projects_status::EN_FUNDING > $project->status) ? '' : $this->getFundingDurationTranslation($project)
        ];

        $daysLeft = $now->diff($end);
        $daysLeft = $daysLeft->invert == 0 ? $daysLeft->days : 0;

        $projectData['daysLeft'] = $daysLeft;

        return $projectData;
    }

    /**
     * @param \projects $project
     * @return array
     */
    public function getProjectData(\projects $project)
    {
        /** @var \bids $bids */
        $bids = $this->entityManager->getRepository('bids');
        /** @var \projects_status_history $projectStatusHistory */
        $projectStatusHistory = $this->entityManager->getRepository('projects_status_history');

        $projectData   = $this->getBaseData($project);
        $alreadyFunded = $bids->getSoldeBid($project->id_project);

        /** @var \project_rate_settings $projectRateSettings */
        $projectRateSettings = $this->entityManager->getRepository('project_rate_settings');
        $projectRateSettings->get($this->projectManager->getProjectRateRange($project));

        $projectData['minRate'] = $projectRateSettings->rate_min;
        $projectData['maxRate'] = $projectRateSettings->rate_max;

        if ($alreadyFunded >= $project->amount) {
            $projectData['costFunded']    = $project->amount;
            $projectData['percentFunded'] = 100;
            $projectData['maxValidRate']  = $bids->getProjectMaxRate($project);
        } else {
            $projectData['costFunded']    = $alreadyFunded;
            $projectData['percentFunded'] = $alreadyFunded / $project->amount * 100;
            $projectData['maxValidRate']  = $projectRateSettings->rate_max;
        }

        $now        = new \DateTime('NOW');
        $projectEnd = $this->projectManager->getProjectEndDate($project);

        $projectData['navigation'] = $project->positionProject($project->id_project, self::$projectsStatus, [\projects::SORT_FIELD_END => \projects::SORT_DIRECTION_DESC]);

        if ($projectEnd  <= $now && $projectData['status'] == \projects_status::EN_FUNDING) {
            $projectData['projectPending'] = true;
        }

        if ($projectData['status'] >= \projects_status::REMBOURSEMENT) {
            $projectData['statusHistory']   = $projectStatusHistory->getHistoryDetails($project->id_project);
        }

        if (in_array($projectData['status'], [\projects_status::REMBOURSE, \projects_status::REMBOURSEMENT_ANTICIPE])) {
            $lastStatusHistory            = $projectStatusHistory->select('id_project = ' . $project->id_project, 'id_project_status_history DESC', 0, 1);
            $lastStatusHistory            = array_shift($lastStatusHistory);
            $projectData['dateLastRepayment'] = date('d/m/Y', strtotime($lastStatusHistory['added']));
        }

        if (\projects_status::EN_FUNDING <= $projectData['status']) {
            $rateSummary     = [];
            $bidsSummary     = $this->projectManager->getBidsSummary($project);
            $bidsCount       = array_sum(array_column($bidsSummary, 'bidsCount'));
            $bidsTotalAmount = array_sum(array_column($bidsSummary, 'totalAmount'));

            foreach (range($projectRateSettings->rate_max, $projectRateSettings->rate_min, 0.1) as $rate) {
                $rate = (string) $rate; // Fix an issue with float array keys
                $rateSummary[$rate] = [
                    'rate'              => $rate,
                    'activeBidsCount'   => isset($bidsSummary[$rate]) ? (int) $bidsSummary[$rate]['activeBidsCount'] : 0,
                    'bidsCount'         => isset($bidsSummary[$rate]) ? (int) $bidsSummary[$rate]['bidsCount'] : 0,
                    'totalAmount'       => isset($bidsSummary[$rate]) ? (float) $bidsSummary[$rate]['totalAmount'] : 0,
                    'activeTotalAmount' => isset($bidsSummary[$rate]) ? (float) $bidsSummary[$rate]['activeTotalAmount'] : 0,
                    'activePercentage'  => isset($bidsSummary[$rate]) ? (float) $bidsSummary[$rate]['activePercentage'] : 100,
                ];
            }

            $projectData['bids'] = [
                'summary'         => $rateSummary,
                'averageAmount'   => $bidsCount > 0 ? round($bidsTotalAmount / $bidsCount, 2) : 0,
                'activeBidsCount' => array_sum(array_column($bidsSummary, 'activeBidsCount'))
            ];
        }

        return $projectData;
    }

    public function getProjectFinancialData(\projects $project)
    {
        /** @var \companies_actif_passif $assetsDebtsEntity */
        $assetsDebtsEntity = $this->entityManager->getRepository('companies_actif_passif');
        /** @var \companies_bilans $balanceSheetEntity */
        $balanceSheetEntity = $this->entityManager->getRepository('companies_bilans');
        /** @var \settings $setting */
        $setting = $this->entityManager->getRepository('settings');

        $setting->get('Entreprises fundés au passage du risque lot 1', 'type');
        $isBeforeRiskProject = in_array($project->id_company, explode(',', $setting->value));

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

            $finance[$balanceSheet['id_bilan']]['balanceSheet']['ca']                         = [$balanceSheet['ca']];
            $finance[$balanceSheet['id_bilan']]['balanceSheet']['resultat-brut-exploitation'] = [$balanceSheet['resultat_brute_exploitation']];
            $finance[$balanceSheet['id_bilan']]['balanceSheet']['resultat-exploitation']      = [$balanceSheet['resultat_exploitation']];

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

    /**
     * @param \projects $project
     * @return \DateInterval
     */
    public function getFundingDuration(\projects $project)
    {
        $startFundingPeriod = new \DateTime($project->date_publication_full);
        $endFundingPeriod   = new \DateTime($project->date_funded);

        return $startFundingPeriod->diff($endFundingPeriod);
    }

    /**
     * @param \projects $project
     * @return array
     */
    public function getFundingDurationTranslation(\projects $project)
    {
        $duration = $this->getFundingDuration($project);

        switch (true) {
            case $duration->d > 0:
                $x           = $duration->d;
                $y           = $duration->h;
                $translation = 'day-hour';
                break;
            case $duration->h > 0:
                $x           = $duration->h;
                $y           = $duration->i;
                $translation = 'hour-minute';
                break;
            case $duration->i > 0:
                $x           = $duration->i;
                $y           = $duration->s;
                $translation = 'minute-second';
                break;
            case $duration->s > 0:
            default:
                $x           = $duration->i;
                $y           = $duration->s;
                $translation = 'second';
                break;
        }

        return [
            'translation' => $translation,
            'choice'      => (int) (($x >= 2 ? '1' : '0') . ($y >= 2 ? '1' : '0')),
            'values'      => ['%x%' => $x, '%y%' => $y]
        ];
    }

    public function getTotalNumberOfDisplayedProjects()
    {
        /** @var \projects $projects */
        $projects  = $this->entityManager->getRepository('projects');
        return $projects->countSelectProjectsByStatus(implode(',', self::$projectsStatus), ' AND display = ' . \projects::DISPLAY_PROJECT_ON);
    }
}
