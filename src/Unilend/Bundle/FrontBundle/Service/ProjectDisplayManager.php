<?php
namespace Unilend\Bundle\FrontBundle\Service;

use Psr\Cache\CacheItemPoolInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\BidManager;
use Unilend\Bundle\CoreBusinessBundle\Service\CompanyBalanceSheetManager;
use Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\librairies\CacheKeys;

class ProjectDisplayManager
{
    /** @var EntityManager */
    private $entityManager;
    /** @var ProjectManager */
    private $projectManager;
    /** @var BidManager  */
    private $bidManager;
    /** @var LenderAccountDisplayManager */
    private $lenderAccountDisplayManager;
    /** @var CompanyBalanceSheetManager */
    private $companyBalanceSheetManager;
    /** @var CacheItemPoolInterface */
    private $cachePool;
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
     * @param BidManager                  $bidManager
     * @param LenderAccountDisplayManager $lenderAccountDisplayManager
     * @param CompanyBalanceSheetManager  $companyBalanceSheetManager
     * @param CacheItemPoolInterface      $cachePool
     */
    public function __construct(
        EntityManager $entityManager,
        ProjectManager $projectManager,
        BidManager $bidManager,
        LenderAccountDisplayManager $lenderAccountDisplayManager,
        CompanyBalanceSheetManager $companyBalanceSheetManager,
        CacheItemPoolInterface $cachePool
    )
    {
        $this->entityManager               = $entityManager;
        $this->projectManager              = $projectManager;
        $this->bidManager                  = $bidManager;
        $this->lenderAccountDisplayManager = $lenderAccountDisplayManager;
        $this->companyBalanceSheetManager  = $companyBalanceSheetManager;
        $this->cachePool                   = $cachePool;
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
        $projectList  = $projectsEntity->selectProjectsByStatus($projectStatus, ' AND p.display = ' . \projects::DISPLAY_PROJECT_ON, $sort, $start, $limit);

        foreach ($projectList as $item) {
            $project->get($item['id_project']);
            $projectsData[$project->id_project] = $this->getBaseData($project);
            $projectsData[$project->id_project]['bidsCount'] = $bids->counter('id_project = ' . $project->id_project);

            if (false === empty($lenderAccount->id_lender_account)) {
                $projectsData[$project->id_project]['lender']['bids']      = $this->lenderAccountDisplayManager->getBidsForProject($project->id_project, $lenderAccount);
                $projectsData[$project->id_project]['lender']['isAdvised'] = $this->lenderAccountDisplayManager->isProjectAdvisedForLender($project, $lenderAccount);
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
            'startDate'            => $project->date_publication,
            'endDate'              => $end,
            'fundedDate'           => $project->date_funded,
            'projectNeed'          => $project->id_project_need,
            'risk'                 => $project->risk,
            'company'              => [
                'city'      => $company->city,
                'zip'       => $company->zip,
                'sectorId'  => $company->sector,
                'latitude'  => (float) $company->latitude,
                'longitude' => (float) $company->longitude
            ],
            'status'               => $project->status,
            'finished'             => ($project->status > \projects_status::EN_FUNDING || $end < $now),
            'averageRate'          => round($project->getAverageInterestRate(), 1),
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
        /** @var \loans $loans */
        $loans = $this->entityManager->getRepository('loans');
        /** @var \projects_status_history $projectStatusHistory */
        $projectStatusHistory = $this->entityManager->getRepository('projects_status_history');

        $projectData   = $this->getBaseData($project);
        $alreadyFunded = $bids->getSoldeBid($project->id_project);
        $projectRateSettings = $this->bidManager->getProjectRateRange($project);

        $projectData['minRate']      = (float) $projectRateSettings['rate_min'];
        $projectData['maxRate']      = (float) $projectRateSettings['rate_min'];
        $projectData['totalLenders'] = (\projects_status::EN_FUNDING >= $project->status) ? $bids->countLendersOnProject($project->id_project) : $loans->getNbPreteurs($project->id_project);

        if ($alreadyFunded >= $project->amount) {
            $projectData['costFunded']    = $project->amount;
            $projectData['percentFunded'] = 100;
            $projectData['maxValidRate']  = $bids->getProjectMaxRate($project);
        } else {
            $projectData['costFunded']    = $alreadyFunded;
            $projectData['percentFunded'] = round($alreadyFunded / $project->amount * 100, 1);
            $projectData['maxValidRate']  = $projectRateSettings['rate_max'];
        }

        $projectData['navigation'] = $project->positionProject($project->id_project, self::$projectsStatus, [\projects::SORT_FIELD_END => \projects::SORT_DIRECTION_DESC]);

        $now = new \DateTime('NOW');
        if ($projectData['endDate'] <= $now && $projectData['status'] == \projects_status::EN_FUNDING) {
            $projectData['projectPending'] = true;
        }

        if ($projectData['status'] >= \projects_status::REMBOURSEMENT) {
            $projectData['statusHistory'] = $projectStatusHistory->getHistoryDetails($project->id_project);
        }

        if (in_array($projectData['status'], [\projects_status::REMBOURSE, \projects_status::REMBOURSEMENT_ANTICIPE])) {
            $lastStatusHistory                = $projectStatusHistory->select('id_project = ' . $project->id_project, 'added DESC, id_project_status_history DESC', 0, 1);
            $lastStatusHistory                = array_shift($lastStatusHistory);
            $projectData['dateLastRepayment'] = date('d/m/Y', strtotime($lastStatusHistory['added']));
        }

        if (\projects_status::EN_FUNDING <= $projectData['status']) {
            $rateSummary     = [];
            $bidsSummary     = $this->projectManager->getBidsSummary($project);
            $bidsCount       = array_sum(array_column($bidsSummary, 'bidsCount'));
            $bidsTotalAmount = array_sum(array_column($bidsSummary, 'totalAmount'));

            foreach (range($projectRateSettings['rate_max'], $projectRateSettings['rate_min'], 0.1) as $rate) {
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

    /**
     * @param \projects $project
     * @return array
     */
    public function getProjectFinancialData(\projects $project)
    {
        $finance = [];
        $cachedItem = $this->cachePool->getItem(__FUNCTION__ . $project->id_project);

        if (false === $cachedItem->isHit()) {
            if ($project->id_dernier_bilan) {
                /** @var \companies_bilans $balanceSheetEntity */
                $balanceSheetEntity = $this->entityManager->getRepository('companies_bilans');

                $previousBalanceSheetId   = null;
                $balanceSheets            = $balanceSheetEntity->getLastTypeSheets($project, 3);
                $lastBalanceSheet         = current($balanceSheets);
                $lastBalanceTaxFormTypeId = $lastBalanceSheet['id_company_tax_form_type'];

                foreach ($balanceSheets as $balanceSheet) {
                    $balanceSheetEntity->get($balanceSheet['id_bilan']);
                    $finance[$balanceSheet['id_bilan']]                     = [
                        'closingDate'   => $balanceSheet['cloture_exercice_fiscal'],
                        'monthDuration' => $balanceSheet['duree_exercice_fiscal'],
                        'assets'        => [],
                        'debts'         => [],
                    ];
                    $finance[$balanceSheet['id_bilan']]['income_statement'] = $this->companyBalanceSheetManager->getIncomeStatement($balanceSheetEntity);

                    foreach ($finance[$balanceSheet['id_bilan']]['income_statement']['details'] as $label => &$value) {
                        $value = [$value];
                        if (null !== $previousBalanceSheetId) {
                            $finance[$previousBalanceSheetId]['income_statement']['details'][$label][1] = empty($value[0]) ? null : round(($finance[$previousBalanceSheetId]['income_statement']['details'][$label][0] - $finance[$balanceSheet['id_bilan']]['income_statement']['details'][$label][0]) / abs($finance[$balanceSheet['id_bilan']]['income_statement']['details'][$label][0]) * 100, 1);

                        }
                    }

                    $previousBalanceSheetId = $balanceSheet['id_bilan'];
                }

                /** @var \company_tax_form_type $companyTaxFormType */
                $companyTaxFormType = $this->entityManager->getRepository('company_tax_form_type');
                $companyTaxFormType->get($lastBalanceTaxFormTypeId);
                $lastBalanceTaxFormType = $companyTaxFormType->label;

                if ($lastBalanceTaxFormType === \company_tax_form_type::FORM_2033) {
                    /** @var \companies_actif_passif $assetsDebtsEntity */
                    $assetsDebtsEntity = $this->entityManager->getRepository('companies_actif_passif');

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
                }
            }

            $cachedItem->set($finance)->expiresAfter(CacheKeys::LONG_TIME);
            $this->cachePool->save($cachedItem);
        } else {
            $finance = $cachedItem->get();
        }

        return $finance;
    }

    /**
     * @param \projects $project
     * @return \DateInterval
     */
    public function getFundingDuration(\projects $project)
    {
        $startFundingPeriod = new \DateTime($project->date_publication);
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
