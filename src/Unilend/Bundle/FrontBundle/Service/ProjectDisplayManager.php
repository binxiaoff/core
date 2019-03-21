<?php

namespace Unilend\Bundle\FrontBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\{Clients, ClientsStatus, Projects, ProjectsStatus};
use Unilend\Bundle\CoreBusinessBundle\Repository\ProjectsRepository;
use Unilend\Bundle\CoreBusinessBundle\Service\{BidManager, CompanyBalanceSheetManager, ProjectManager};
use Unilend\Bundle\CoreBusinessBundle\Service\Product\ProductManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
use Unilend\librairies\CacheKeys;

class ProjectDisplayManager
{
    const VISIBILITY_FULL                 = 'full';
    const VISIBILITY_NOT_VALIDATED_LENDER = 'not_validated_lender';
    const VISIBILITY_ANONYMOUS            = 'anonymous';
    const VISIBILITY_NONE                 = 'none';

    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var EntityManagerSimulator */
    private $entityManagerSimulator;
    /** @var ProjectManager */
    private $projectManager;
    /** @var BidManager  */
    private $bidManager;
    /** @var LenderAccountDisplayManager */
    private $lenderAccountDisplayManager;
    /** @var CompanyBalanceSheetManager */
    private $companyBalanceSheetManager;
    /** @var ProductManager */
    private $productManager;
    /** @var CacheItemPoolInterface */
    private $cachePool;
    /** @var array */
    const STATUS_DISPLAYABLE = [
        ProjectsStatus::STATUS_ONLINE,
        ProjectsStatus::STATUS_FUNDED,
        ProjectsStatus::STATUS_REPAYMENT,
        ProjectsStatus::STATUS_REPAID,
        ProjectsStatus::STATUS_LOSS
    ];

    /**
     * @param EntityManagerInterface      $entityManager
     * @param EntityManagerSimulator      $entityManagerSimulator
     * @param ProjectManager              $projectManager
     * @param BidManager                  $bidManager
     * @param LenderAccountDisplayManager $lenderAccountDisplayManager
     * @param CompanyBalanceSheetManager  $companyBalanceSheetManager
     * @param ProductManager              $productManager
     * @param CacheItemPoolInterface      $cachePool
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        EntityManagerSimulator $entityManagerSimulator,
        ProjectManager $projectManager,
        BidManager $bidManager,
        LenderAccountDisplayManager $lenderAccountDisplayManager,
        CompanyBalanceSheetManager $companyBalanceSheetManager,
        ProductManager $productManager,
        CacheItemPoolInterface $cachePool
    )
    {
        $this->entityManager               = $entityManager;
        $this->entityManagerSimulator      = $entityManagerSimulator;
        $this->projectManager              = $projectManager;
        $this->bidManager                  = $bidManager;
        $this->lenderAccountDisplayManager = $lenderAccountDisplayManager;
        $this->companyBalanceSheetManager  = $companyBalanceSheetManager;
        $this->productManager              = $productManager;
        $this->cachePool                   = $cachePool;
    }

    /**
     * @param array        $projectStatus
     * @param array        $sort
     * @param int|null     $start
     * @param int|null     $limit
     * @param Clients|null $client
     *
     * @return array
     */
    public function getProjectsList(array $projectStatus = [], array $sort = [], $start = null, $limit = null, Clients $client = null)
    {
        /** @var \bids $bids */
        $bids = $this->entityManagerSimulator->getRepository('bids');
        /** @var \projects $projectData */
        $projectData = $this->entityManagerSimulator->getRepository('projects');

        if (empty($projectStatus)) {
            $projectStatus = self::STATUS_DISPLAYABLE;
        }

        $projectsData = [];
        $products     = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Product')->findAvailableProductsByClient($client);

        $projectSearchRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
//        $projects                = $projectSearchRepository->findByWithCustomSort(
//            ['status' => $projectStatus, 'idProduct' => $products],
//            $sort,
//            $limit,
//            $start
//        );
        $projects = $projectSearchRepository->findBy(['status' => self::STATUS_DISPLAYABLE]);

        /** @var Projects $project */
        foreach ($projects as $project) {
            $projectData->get($project->getIdProject());
            $projectsData[$project->getIdProject()]              = $this->getBaseData($projectData);
            $projectsData[$project->getIdProject()]['bidsCount'] = $bids->counter('id_project = ' . $project->getIdProject());

            if (null !== $client && $client->isLender()) {
                $projectsData[$project->getIdProject()]['lender']['bids']      = $this->lenderAccountDisplayManager->getBidsForProject($project->getIdProject(), $client);
                $projectsData[$project->getIdProject()]['lender']['isAdvised'] = $this->lenderAccountDisplayManager->isProjectAdvisedForLender($project, $client);
            }
        }

        return $projectsData;
    }

    /**
     * @param \projects $project
     *
     * @return array
     */
    public function getBaseData(\projects $project): array
    {
        $company = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Companies')->find($project->id_company);

        $now      = new \DateTime('NOW');
        $end      = $this->projectManager->getProjectEndDate($project);
        $daysLeft = $now->diff($end);
        $daysLeft = $daysLeft->invert == 0 ? $daysLeft->days : 0;

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
                'city'      => $company->getIdAddress() ? $company->getIdAddress()->getCity() : '',
                'zip'       => $company->getIdAddress() ? $company->getIdAddress()->getZip() : '',
                'sectorId'  => $company->getSector(),
                'latitude'  => $company->getIdAddress() ? (float) $company->getIdAddress()->getLatitude() : '',
                'longitude' => $company->getIdAddress() ? (float) $company->getIdAddress()->getLongitude() : ''
            ],
            'status'               => $project->status,
            'finished'             => ($project->status > ProjectsStatus::STATUS_ONLINE || $end < $now),
            'averageRate'          => round($project->getAverageInterestRate(), 1),
            'fundingDuration'      => (ProjectsStatus::STATUS_ONLINE > $project->status) ? '' : $this->getFundingDurationTranslation($project),
            'daysLeft'             => $daysLeft
        ];

        return $projectData;
    }

    /**
     * @param \projects     $project
     * @param Clients|null  $client
     *
     * @return array
     */
    public function getProjectData(\projects $project, ?Clients $client = null): array
    {
        /** @var \loans $loans */
        $loans = $this->entityManagerSimulator->getRepository('loans');
        /** @var \projects_status_history $projectStatusHistory */
        $projectStatusHistory = $this->entityManagerSimulator->getRepository('projects_status_history');
        /** @var \bids $bids */
        $bids          = $this->entityManagerSimulator->getRepository('bids');
        $bidRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Bids');

        $projectData         = $this->getBaseData($project);
        $alreadyFunded       = $bidRepository->getProjectTotalAmount($project->id_project);
        $projectRateSettings = $this->bidManager->getProjectRateRange($project);

        $projectData['minRate']      = (float) $projectRateSettings['rate_min'];
        $projectData['maxRate']      = (float) $projectRateSettings['rate_min'];
        $projectData['totalLenders'] = (ProjectsStatus::STATUS_ONLINE >= $project->status) ? $bids->countLendersOnProject($project->id_project) : $loans->getNbPreteurs($project->id_project);

        if ($alreadyFunded >= $project->amount) {
            $projectData['costFunded']    = $project->amount;
            $projectData['percentFunded'] = 100;
            $projectData['maxValidRate']  = $bidRepository->getProjectMaxRate($project->id_project);
        } else {
            $projectData['costFunded']    = $alreadyFunded;
            $projectData['percentFunded'] = bcdiv(bcmul($alreadyFunded, 100), $project->amount, 1);
            $projectData['maxValidRate']  = $projectRateSettings['rate_max'];
        }

        $products  = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Product')->findAvailableProductsByClient($client);
        $neighbors = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->findNeighbors(
            $project->id_project,
            ['status' => self::STATUS_DISPLAYABLE, 'idProduct' => $products],
            [ProjectsRepository::SORT_FIELD_END => 'DESC']
        );

        // Not clean but will be once migration of Projects to Doctrine is over
        $projectData['navigation'] = [];

        if (false === empty($neighbors['previous'])) {
            $projectData['navigation']['previous'] = [
                'project' => $neighbors['previous'],
                'slug'    => $neighbors['previous']->getSlug(),
                'title'   => $neighbors['previous']->getTitle()
            ];
        }

        if (false === empty($neighbors['next'])) {
            $projectData['navigation']['next'] = [
                'project' => $neighbors['next'],
                'slug'    => $neighbors['next']->getSlug(),
                'title'   => $neighbors['next']->getTitle()
            ];
        }

        $now = new \DateTime('NOW');
        if ($projectData['endDate'] <= $now && $projectData['status'] == ProjectsStatus::STATUS_ONLINE) {
            $projectData['projectPending'] = true;
        }

        if (in_array($projectData['status'], [ProjectsStatus::STATUS_REPAID, ProjectsStatus::STATUS_LOSS])) {
            $lastStatusHistory             = $projectStatusHistory->select('id_project = ' . $project->id_project, 'added DESC, id_project_status_history DESC', 0, 1);
            $lastStatusHistory             = array_shift($lastStatusHistory);
            $projectData['dateLastStatus'] = date('d/m/Y', strtotime($lastStatusHistory['added']));
        }

        if (ProjectsStatus::STATUS_ONLINE <= $projectData['status']) {
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

        $projectData['isCloseOutNetting'] = $project->close_out_netting_date && '0000-00-00' !== $project->close_out_netting_date;

        return $projectData;
    }

    /**
     * @param \projects $project
     * @param boolean   $excludeNonPositiveLines2035
     * @return array
     */
    public function getProjectFinancialData(\projects $project, $excludeNonPositiveLines2035 = false)
    {
        $finance    = [];
        $cachedItem = $this->cachePool->getItem(__FUNCTION__ . $project->id_project);

        if (false === $cachedItem->isHit()) {
            if ($project->id_dernier_bilan) {
                /** @var \companies_bilans $balanceSheetEntity */
                $balanceSheetEntity = $this->entityManagerSimulator->getRepository('companies_bilans');

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
                    $finance[$balanceSheet['id_bilan']]['income_statement'] = $this->companyBalanceSheetManager->getIncomeStatement($balanceSheetEntity, $excludeNonPositiveLines2035);

                    foreach ($finance[$balanceSheet['id_bilan']]['income_statement']['details'] as $label => &$value) {
                        $value = [$value];

                        if (null !== $previousBalanceSheetId) {
                            $finance[$previousBalanceSheetId]['income_statement']['details'][$label][1] = empty($value[0]) ? null : round(($finance[$previousBalanceSheetId]['income_statement']['details'][$label][0] - $finance[$balanceSheet['id_bilan']]['income_statement']['details'][$label][0]) / abs($finance[$balanceSheet['id_bilan']]['income_statement']['details'][$label][0]) * 100, 1);

                        }
                    }

                    $previousBalanceSheetId = $balanceSheet['id_bilan'];
                }

                /** @var \company_tax_form_type $companyTaxFormType */
                $companyTaxFormType = $this->entityManagerSimulator->getRepository('company_tax_form_type');
                $companyTaxFormType->get($lastBalanceTaxFormTypeId);
                $lastBalanceTaxFormType = $companyTaxFormType->label;

                if ($lastBalanceTaxFormType === \company_tax_form_type::FORM_2033) {
                    /** @var \companies_actif_passif $assetsDebtsEntity */
                    $assetsDebtsEntity = $this->entityManagerSimulator->getRepository('companies_actif_passif');

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
     * @return array
     */
    public function getFundingDurationTranslation(\projects $project)
    {
        $startFundingPeriod = new \DateTime($project->date_publication);
        $endFundingPeriod   = new \DateTime($project->date_funded);
        $duration           = $startFundingPeriod->diff($endFundingPeriod);

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
            case $duration->s >= 0:
            default:
                $x           = $duration->i;
                $y           = max($duration->s, 1);
                $translation = 'second';
                break;
        }

        return [
            'translation' => $translation,
            'choice'      => (int) (($x >= 2 ? '1' : '0') . ($y >= 2 ? '1' : '0')),
            'values'      => ['%x%' => $x, '%y%' => $y]
        ];
    }

    /**
     * @param Clients|null $client
     *
     * @return int
     */
    public function getTotalNumberOfDisplayedProjects(?Clients $client): int
    {
        $productRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Product');
        $products          = $productRepository->findAvailableProductsByClient($client);

        $projectRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
        $projects          = $projectRepository->findBy(['status' => self::STATUS_DISPLAYABLE, 'idProduct' => $products]);

        return count($projects);
    }

    /**
     * @param Projects      $project
     * @param Clients|null  $client
     *
     * @return string
     */
    public function getVisibility(Projects $project, ?Clients $client = null): string
    {
        if ($project->getStatus() < ProjectsStatus::STATUS_ONLINE) {
            return self::VISIBILITY_NONE;
        }

        if (null === $client) {
            return self::VISIBILITY_NONE;
        }

        if (in_array($client->getCompany(), [$project->getIdCompany(), $project->getIdCompanySubmitter()])) {
            return self::VISIBILITY_FULL;
        }

        $projectParticipant = $project->getProjectParticipants($client->getCompany());

        if ($projectParticipant->count()) {
            return self::VISIBILITY_FULL;
        }

        return self::VISIBILITY_NONE;

        $violations = $this->productManager->checkClientEligibility($client, $project);

        if (0 < count($violations)) {
            return self::VISIBILITY_NONE;
        }

        if (null !== $client) {
            if ($client->isLender()) {
                if (in_array($client->getIdClientStatusHistory()->getIdStatus()->getId(), [ClientsStatus::STATUS_MODIFICATION, ClientsStatus::STATUS_VALIDATED, ClientsStatus::STATUS_SUSPENDED])) {
                    return self::VISIBILITY_FULL;
                }

                return self::VISIBILITY_NOT_VALIDATED_LENDER;
            } elseif ($client->isBorrower() || $client->isPartner()) {
                return self::VISIBILITY_FULL;
            }
        }

        return self::VISIBILITY_ANONYMOUS;
    }
}
