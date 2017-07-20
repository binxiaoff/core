<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Unilend\Bundle\CoreBusinessBundle\Entity\LenderStatistic;
use Unilend\Bundle\CoreBusinessBundle\Entity\OperationType;
use Unilend\Bundle\CoreBusinessBundle\Entity\Product;
use Unilend\Bundle\CoreBusinessBundle\Entity\WalletType;
use Unilend\Bundle\CoreBusinessBundle\Repository\LenderStatisticRepository;
use Unilend\Bundle\CoreBusinessBundle\Repository\ProjectsRepository;
use Unilend\Bundle\CoreBusinessBundle\Repository\WalletRepository;
use Unilend\Bundle\CoreBusinessBundle\Service\LenderManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;
use Unilend\Bundle\FrontBundle\Service\LenderAccountDisplayManager;

class LenderDashboardController extends Controller
{
    const REPAYMENT_TIME_FRAME_MONTH   = 'month';
    const REPAYMENT_TIME_FRAME_QUARTER = 'quarter';
    const REPAYMENT_TIME_FRAME_YEAR    = 'year';

    /**
     * @Route("synthese", name="lender_dashboard")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @return Response
     */
    public function indexAction()
    {
        /** @var LenderStatisticRepository $lenderStatisticsRepository */
        $lenderStatisticsRepository = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:LenderStatistic');
        /** @var WalletRepository $walletRepository */
        $walletRepository = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Wallet');
        /** @var ProjectsRepository $projectsRepository */
        $projectsRepository = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Projects');
        /** @var LenderManager $lenderManager */
        $lenderManager = $this->get('unilend.service.lender_manager');
        /** @var EntityManagerSimulator $entityManager */
        $entityManagerSimulator = $this->get('unilend.service.entity_manager');
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var \loans $loan */
        $loan = $entityManagerSimulator->getRepository('loans');
        /** @var \echeanciers $lenderRepayment */
        $lenderRepayment = $entityManagerSimulator->getRepository('echeanciers');
        /** @var \projects $project */
        $project = $entityManagerSimulator->getRepository('projects');
        /** @var \companies $company */
        $company = $entityManagerSimulator->getRepository('companies');
        /** @var \bids $bid */
        $bid = $entityManagerSimulator->getRepository('bids');
        /** @var \clients $client */
        $client = $entityManagerSimulator->getRepository('clients');

        $repaymentScheduleRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Echeanciers');
        $operationRepository         = $entityManager->getRepository('UnilendCoreBusinessBundle:Operation');

        $client->get($this->getUser()->getClientId());
        $wallet          = $walletRepository->getWalletByType($client->id_client, WalletType::LENDER);
        $balance         = $wallet->getAvailableBalance();
        $products        = $entityManager->getRepository('UnilendCoreBusinessBundle:Product')->findAvailableProductsByClient($wallet->getIdClient());
        $productIds      = array_map(function (Product $product) {
            return $product->getIdProduct();
        }, $products);
        $ongoingProjects = $project->selectProjectsByStatus([\projects_status::EN_FUNDING], '', [\projects::SORT_FIELD_END => \projects::SORT_DIRECTION_ASC], 0, 30, true, $productIds);

        foreach ($ongoingProjects as $iKey => $aProject) {
            $project->get($aProject['id_project']);
            $ongoingProjects[$iKey]['avgrate'] = $project->getAverageInterestRate();
        }

        $ongoingBidsSum         = $bid->sumBidsEncours($wallet->getId());
        $problematicProjects    = $lenderRepayment->getProblematicProjects($wallet->getId());
        $upcomingGrossInterests = $lenderRepayment->getOwedInterests(['id_lender' => $wallet->getId()]);
        $repaidGrossInterests   = $lenderRepayment->getRepaidInterests(['id_lender' => $wallet->getId()]);
        $irr                    = 0;
        $irrTranslationType     = '';
        $hasIRR                 = false;

        if ($this->getUser()->getLevel() > 0) {
            /** @var LenderStatistic $lastIRR */
            $lastIRR = $lenderStatisticsRepository->findOneBy(['idWallet' => $wallet, 'typeStat' => LenderStatistic::TYPE_STAT_IRR], ['added' => 'DESC']);
            if (null !== $lastIRR && LenderStatistic::STAT_VALID_OK === $lastIRR->getStatus()) {
                $irr                = $lastIRR->getValue();
                $irrTranslationType = ($irr >= 0 ? 'positive-' : 'negative-');
                $hasIRR             = true;
            } else {
                $lossRate = $lenderManager->getLossRate($wallet->getIdClient());

                if ($lossRate > 0) {
                    $irr                = -$lossRate;
                    $irrTranslationType = 'not-calculable';
                } else {
                    $irrTranslationType = 'not-calculated-yet';
                }
            }
        }

        $ongoingBidsByProject = [];
        $publishedProjects    = [];

        foreach ($ongoingProjects as $iKey => $aProject) {
            $project->get($aProject['id_project']);
            $projectStats = $this->get('unilend.frontbundle.service.project_display_manager')->getFundingDuration($project);

            if (0 < $bid->counter('id_project = ' . $aProject['id_project'] . ' AND id_lender_account = ' . $wallet->getId())) {
                $ongoingBidsByProject[$iKey] = [
                    'title'            => $aProject['title'],
                    'amount'           => $aProject['amount'],
                    'publication_date' => $aProject['date_publication'],
                    'days_left'        => $aProject['daysLeft'],
                    'finished'         => ($aProject['status'] > \projects_status::EN_FUNDING || (new \DateTime($aProject['date_retrait'])) < (new \DateTime('NOW'))),
                    'end_date'         => $aProject['date_retrait'],
                    'funding_duration' => $projectStats->days,
                    'pending_bids'     => $bid->getBidsByStatus(\bids::STATUS_BID_PENDING, $aProject['id_project'], $wallet->getId())
                ];
            }

            $company->get($aProject['id_company']);
            $publishedProjects[] = [
                'title'            => $aProject['title'],
                'slug'             => $aProject['slug'],
                'company_address'  => (false === empty($company->city) ? $company->city . ', ' : '') . $company->zip,
                'amount'           => $aProject['amount'],
                'days_left'        => $aProject['daysLeft'],
                'risk'             => $aProject['risk'],
                'average_rate'     => $aProject['avgrate'],
                'bid_count'        => count($bid->getBidsByStatus(\bids::STATUS_BID_PENDING, $aProject['id_project'])),
                'finished'         => ($aProject['status'] > \projects_status::EN_FUNDING || (new \DateTime($aProject['date_retrait'])) < (new \DateTime('NOW'))),
                'end_date'         => $aProject['date_retrait'],
                'funding_duration' => $projectStats->days
            ];
        }
        /** @var LenderAccountDisplayManager $lenderDisplayManager */
        $lenderDisplayManager = $this->get('unilend.frontbundle.service.lender_account_display_manager');

        $repaymentDateRange   = $lenderRepayment->getFirstAndLastRepaymentDates($wallet->getId());
        $lenderRepaymentsData = [];
        $currentMonth         = (new \DateTime($repaymentDateRange['first_repayment_date']))->modify('first day of this month');
        $lastRepaymentMonth   = new \DateTime($repaymentDateRange['last_repayment_date']);
        while ($currentMonth <= $lastRepaymentMonth) {
            $lenderRepaymentsData[$currentMonth->format('Y-m')]                 = $repaymentScheduleRepository->getLenderRepaymentByMonth($wallet, $currentMonth)[0];
            $lenderRepaymentsData[$currentMonth->format('Y-m')]['capital']      = (float) $lenderRepaymentsData[$currentMonth->format('Y-m')]['capital'];
            $lenderRepaymentsData[$currentMonth->format('Y-m')]['netInterests'] = (float) $lenderRepaymentsData[$currentMonth->format('Y-m')]['netInterests'];
            $lenderRepaymentsData[$currentMonth->format('Y-m')]['taxes']        = (float) $lenderRepaymentsData[$currentMonth->format('Y-m')]['taxes'];
            $currentMonth->modify('+1 month');
        }
        $repaymentDataPerPeriod = $this->getQuarterAndYearSum($lenderRepaymentsData);
        $monthAxisData          = $this->getMonthAxis($repaymentDateRange);
        $quarterAxisData        = $this->getQuarterAxis($lenderRepaymentsData);
        $yearAxisData           = $this->getYearAxis($repaymentDateRange);

        $depositedAmount = bcsub($operationRepository->sumCreditOperationsByTypeAndYear($wallet, [OperationType::LENDER_PROVISION]), $operationRepository->sumDebitOperationsByTypeAndYear($wallet, [OperationType::LENDER_WITHDRAW]), 2);

        return $this->render(
            '/pages/lender_dashboard/lender_dashboard.html.twig',
            [
                'dashboardPanels'    => $this->getDashboardPreferences(),
                'lenderDetails'      => [
                    'balance'                   => (float)$balance,
                    'level'                     => $this->getUser()->getLevel(),
                    'hasIRR'                    => $hasIRR,
                    'irr'                       => $irr,
                    'irrTranslation'            => $irrTranslationType,
                    'initials'                  => $this->getUser()->getInitials(),
                    'companiesLenderInvestedIn' => $projectsRepository->countCompaniesLenderInvestedIn($wallet->getId()),
                    'numberOfLoans'             => $loan->getLoansCount($wallet->getId())
                ],
                'walletData'         => [
                    'by_sector' => $lenderDisplayManager->getLenderLoansAllocationByCompanySector($wallet->getIdClient()),
                    'by_region' => $lenderDisplayManager->getLenderLoansAllocationByRegion($wallet->getIdClient()),
                ],
                'amountDetails'      => [
                    'loaned_amount'     => round($loan->sumPrets($wallet->getId()), 2),
                    'blocked_amount'    => round($ongoingBidsSum, 2),
                    'expected_earnings' => round($repaidGrossInterests + $upcomingGrossInterests - $problematicProjects['interests'], 2),
                    'deposited_amount'  => $depositedAmount
                ],
                'capitalDetails'     => [
                    'repaid_capital'        => round($lenderRepayment->getRepaidCapital(['id_lender' => $wallet->getId()]), 2),
                    'owed_capital'          => round($lenderRepayment->getOwedCapital(['id_lender' => $wallet->getId()]) - $problematicProjects['capital'], 2),
                    'capital_in_difficulty' => round($problematicProjects['capital'], 2)
                ],
                'interestsDetails'   => [
                    'received_interests'      => round($repaidGrossInterests, 2),
                    'upcoming_interests'      => round($upcomingGrossInterests - $problematicProjects['interests'], 2),
                    'interests_in_difficulty' => round($problematicProjects['interests'], 2)
                ],
                'ongoingBids'        => $bid->counter('id_lender_account = ' . $wallet->getId() . ' AND status = ' . \bids::STATUS_BID_PENDING),
                'ongoingProjects'    => $ongoingBidsByProject,
                'publishedProjects'  => $publishedProjects,
                'timeAxis'           => [
                    'month'   => $monthAxisData['monthAxis'],
                    'quarter' => $quarterAxisData['quarterAxis'],
                    'year'    => $yearAxisData['yearAxis']
                ],
                'monthSum'           => [
                    'capital'   => array_column($lenderRepaymentsData, 'capital'),
                    'interests' => array_column($lenderRepaymentsData, 'netInterests'),
                    'tax'       => array_column($lenderRepaymentsData, 'taxes'),
                    'max'       => $repaymentScheduleRepository->getMaxRepaymentAmountForLender($wallet->getId(), self::REPAYMENT_TIME_FRAME_MONTH)
                ],
                'quarterSum'         => [
                    'capital'   => $repaymentDataPerPeriod['quarterCapital'],
                    'interests' => $repaymentDataPerPeriod['quarterInterests'],
                    'tax'       => $repaymentDataPerPeriod['quarterTax'],
                    'max'       => $repaymentScheduleRepository->getMaxRepaymentAmountForLender($wallet->getId(), self::REPAYMENT_TIME_FRAME_QUARTER)
                ],
                'yearSum'            => [
                    'capital'   => $repaymentDataPerPeriod['yearCapital'],
                    'interests' => $repaymentDataPerPeriod['yearInterests'],
                    'tax'       => $repaymentDataPerPeriod['yearTax'],
                    'max'       => $repaymentScheduleRepository->getMaxRepaymentAmountForLender($wallet->getId(), self::REPAYMENT_TIME_FRAME_YEAR)
                ],
                'bandOrigin'         => [
                    'month'   => $monthAxisData['monthBandOrigin'],
                    'quarter' => $quarterAxisData['quarterBandOrigin'],
                    'year'    => $yearAxisData['yearBandOrigin']
                ]
            ]
        );
    }

    /**
     * @Route("/synthese/preferences", name="save_panel_preferences")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function saveUserDisplayPreferencesAction(Request $request)
    {
        /** @var EntityManagerSimulator $entityManagerSimulator */
        $entityManagerSimulator = $this->get('unilend.service.entity_manager');
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var \lender_panel_preference $panelPreferences */
        $panelPreferences = $entityManagerSimulator->getRepository('lender_panel_preference');

        $wallet   = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($this->getUser()->getClientId(), WalletType::LENDER);
        $pageName = 'lender_dashboard';
        $postData = $request->request->get('panels');
        $result   = ['error' => 1, 'msg' => ''];

        if ($request->getMethod() === 'PUT') {
            try {
                $preferences = $panelPreferences->getLenderPreferencesByPage($wallet->getId(), $pageName);
                foreach ($postData as $panel) {
                    if (
                        isset($preferences[$panel['id']], $panel['hidden'], $panel['order'])
                        && filter_var($panel['order'], FILTER_VALIDATE_INT)
                    ) {
                        if (
                            $preferences[$panel['id']]['hidden'] != $panel['hidden']
                            || $preferences[$panel['id']]['panel_order'] != $panel['order']
                        ) {
                            $panelPreferences->get($preferences[$panel['id']]['id_lender_panel_preference']);
                            $panelPreferences->hidden      = ('true' === $panel['hidden']) ? 1 : 0;
                            $panelPreferences->panel_order = $panel['order'];
                            $panelPreferences->update();
                        } else {
                            $panelPreferences->id_lender   = $wallet->getId();
                            $panelPreferences->page_name   = $pageName;
                            $panelPreferences->panel_name  = $panel['id'];
                            $panelPreferences->panel_order = $panel['order'];
                            $panelPreferences->hidden      = ('true' === $panel['hidden']) ? 1 : 0;
                            $panelPreferences->create();
                        }
                    }
                }
                $result = ['success' => 1, 'data' => $postData, 'preferences' => $preferences];
            } catch (\Exception $exception) {
                $result = ['error' => 1, 'msg' => $exception->getMessage(), 'data' => $postData];
            }
        } elseif ($request->getMethod() === 'GET') {
            try {
                $data        = ['panels' => []];
                $preferences = $panelPreferences->getLenderPreferencesByPage($wallet->getId(), $pageName);

                if (false === empty($preferences)) {
                    foreach ($preferences as $panelName => $panel) {
                        $data['panels'][] = ['id' => $panelName, 'order' => $panel['panel_order'], 'hidden' => $panel['hidden'] == 1];
                    }
                }
                $result = ['success' => 1, 'data' => $data, 'preferences' => $preferences];
            } catch (\Exception $exception) {
                $result = ['error' => 1, 'msg' => $exception->getMessage(), 'data' => $request->query->all()];
            }
        }

        return $this->json($result);
    }

    /**
     * @return array
     */
    private function getDashboardPreferences()
    {
        /** @var EntityManagerSimulator $entityManagerSimulator */
        $entityManagerSimulator = $this->get('unilend.service.entity_manager');
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var \lender_panel_preference $panelPreferences */
        $panelPreferences = $entityManagerSimulator->getRepository('lender_panel_preference');

        $wallet               = $entityManager->getRepository('UnilendCoreBusinessBundle:Wallet')->getWalletByType($this->getUser()->getClientId(), WalletType::LENDER);
        $pageName             = 'lender_dashboard';
        $panelPreferencesData = [
            'account'    => ['order' => 0, 'id' => 'account', 'hidden' => false],
            'user-level' => ['order' => 1, 'id' => 'user-level', 'hidden' => false],
            'wallet'     => ['order' => 2, 'id' => 'wallet', 'hidden' => false],
            'offers'     => ['order' => 3, 'id' => 'offers', 'hidden' => false],
            'projects'   => ['order' => 4, 'id' => 'projects', 'hidden' => false],
            'repayments' => ['order' => 5, 'id' => 'repayments', 'hidden' => false],
        ];

        try {
            $preferences = $panelPreferences->getLenderPreferencesByPage($wallet->getId(), $pageName);

            if (false === empty($preferences)) {
                $panelPreferencesData = [];
                foreach ($preferences as $panelName => $panel) {
                    $panelPreferencesData[$panelName] = [
                        'id'     => $panelName,
                        'order'  => $panel['panel_order'],
                        'hidden' => $panel['hidden'] == 1
                    ];
                }
            }
        } catch (\Exception $exception) {
        }

        return $panelPreferencesData;
    }

    /**
     * @param array $repaymentDateRange
     * @return array
     */
    private function getPaddingData(array $repaymentDateRange)
    {
        $firstDateTime   = new \DateTime($repaymentDateRange['first_repayment_date']);
        $lastDateTime    = new \DateTime($repaymentDateRange['last_repayment_date']);
        $interval        = new \DateInterval('P1M');
        $paddingData     = [];

        while ($firstDateTime->format('Y-m') <= $lastDateTime->format('Y-m')) {
            $paddingData[$firstDateTime->format('Y-m')] = [
                'month'          => $firstDateTime->format('Y-m'),
                'quarter'        => ceil($firstDateTime->format('n') / 3),
                'year'           => $firstDateTime->format('Y'),
                'capital'        => 0,
                'grossInterests' => 0,
                'netInterests'   => 0,
                'taxes'          => 0
            ];
            $firstDateTime->add($interval);
        }

        return $paddingData;
    }

    /**
     * @param array $repaymentDateRange
     * @return array
     */
    private function getMonthAxis(array $repaymentDateRange)
    {
        $firstDateTime   = new \DateTime($repaymentDateRange['first_repayment_date']);
        $lastDateTime    = new \DateTime($repaymentDateRange['last_repayment_date']);
        $interval        = new \DateInterval('P1M');
        $monthAxis       = [];
        $monthBandOrigin = 0;
        $monthNames      = $this->getMonthNames()['fullNames'];

        while ($firstDateTime->format('Y-m') <= $lastDateTime->format('Y-m')) {
            if ($firstDateTime->format('Y-m') == date('Y-m')) {
                $monthBandOrigin = count($monthAxis) - 0.5;
            }

            $monthAxis[] = $monthNames[$firstDateTime->format('n')] . ' ' . $firstDateTime->format('Y');
            $firstDateTime->add($interval);
        }
        return ['monthAxis' => $monthAxis, 'monthBandOrigin' => $monthBandOrigin];
    }

    /**
     * @param array $lenderRepaymentsData
     * @return array
     */
    private function getQuarterAxis(array $lenderRepaymentsData)
    {
        $monthNames        = $this->getMonthNames()['shortNames'];
        $quarterLabels     = [
            1 => $monthNames[1] . '-' . $monthNames[3],
            2 => $monthNames[4] . '-' . $monthNames[6],
            3 => $monthNames[7] . '-' . $monthNames[9],
            4 => $monthNames[10] . '-' . $monthNames[12]
        ];
        $quarterAxis       = [];
        $quarterBandOrigin = 0;
        $currentQuarter    = 0;

        foreach ($lenderRepaymentsData as $lenderRepayment) {
            if ($lenderRepayment['month'] <= date('Y-m') && $currentQuarter != $lenderRepayment['quarter']) {
                $quarterBandOrigin++;
            }
            $currentQuarter = $lenderRepayment['quarter'];

            if (false === in_array($quarterLabel = $quarterLabels[$lenderRepayment['quarter']] . ' ' . $lenderRepayment['year'], $quarterAxis)) {
                $quarterAxis[] = $quarterLabel;
            }
        }

        return ['quarterAxis' => $quarterAxis, 'quarterBandOrigin' => $quarterBandOrigin - 1.5];
    }

    /**
     * Returns the full and short month names
     * @return array
     */
    private function getMonthNames()
    {
        $startDate       = new \DateTime('2016-01-01');
        $monthCounter    = new \DateInterval('P1M');
        $fullMonthNames  = [];
        $shortMonthNames = [];

        for ($i = 1; $i <= 12; $i++) {
            $fullMonthNames[$i]  = strftime('%B', $startDate->getTimestamp());
            $shortMonthNames[$i] = strftime('%b', $startDate->getTimestamp());
            $startDate->add($monthCounter);
        }
        return ['fullNames' => $fullMonthNames, 'shortNames' => $shortMonthNames];
    }

    /**
     * @param array $repaymentDateRange
     * @return array
     */
    private function getYearAxis(array $repaymentDateRange)
    {
        $yearAxis       = [];
        $yearBandOrigin = 0;

        for ($year = (new \DateTime($repaymentDateRange['first_repayment_date']))->format('Y'); $year <= (new \DateTime($repaymentDateRange['last_repayment_date']))->format('Y'); $year++) {
            $yearAxis[] = $year;

            if ($year == date('Y')) {
                $yearBandOrigin = count($yearAxis) - 1.5;
            }
        }
        return ['yearAxis' => $yearAxis, 'yearBandOrigin' => $yearBandOrigin];
    }

    /**
     * @param array $lenderRepaymentsData
     * @return array
     */
    private function getQuarterAndYearSum(array $lenderRepaymentsData)
    {
        $quarterCapital   = [];
        $quarterInterests = [];
        $quarterTax       = [];
        $yearCapital      = [];
        $yearInterests    = [];
        $yearTax          = [];

        foreach ($lenderRepaymentsData as $lenderRepayment) {
            if (false === isset($quarterCapital[$lenderRepayment['year']][$lenderRepayment['quarter']])) {
                $quarterCapital[$lenderRepayment['year']][$lenderRepayment['quarter']] = 0;
            }
            if (false === isset($quarterInterests[$lenderRepayment['year']][$lenderRepayment['quarter']])) {
                $quarterInterests[$lenderRepayment['year']][$lenderRepayment['quarter']] = 0;
            }
            if (false === isset($quarterTax[$lenderRepayment['year']][$lenderRepayment['quarter']])) {
                $quarterTax[$lenderRepayment['year']][$lenderRepayment['quarter']] = 0;
            }

            if (false === isset($yearCapital[$lenderRepayment['year']])) {
                $yearCapital[$lenderRepayment['year']] = 0;
            }
            if (false === isset($yearInterests[$lenderRepayment['year']])) {
                $yearInterests[$lenderRepayment['year']] = 0;
            }
            if (false === isset($yearTax[$lenderRepayment['year']])) {
                $yearTax[$lenderRepayment['year']] = 0;
            }

            $quarterCapital[$lenderRepayment['year']][$lenderRepayment['quarter']] += $lenderRepayment['capital'];
            $quarterInterests[$lenderRepayment['year']][$lenderRepayment['quarter']] += $lenderRepayment['netInterests'];
            $quarterTax[$lenderRepayment['year']][$lenderRepayment['quarter']] += $lenderRepayment['taxes'];

            $yearCapital[$lenderRepayment['year']] += $lenderRepayment['capital'];
            $yearInterests[$lenderRepayment['year']] += $lenderRepayment['netInterests'];
            $yearTax[$lenderRepayment['year']] += $lenderRepayment['taxes'];
        }

        $capital   = [];
        $interests = [];
        $tax       = [];
        array_walk_recursive($quarterCapital, function ($value) use (&$capital) {
            $capital[] = $value;
        });
        array_walk_recursive($quarterInterests, function ($value) use (&$interests) {
            $interests[] = $value;
        });
        array_walk_recursive($quarterTax, function ($value) use (&$tax) {
            $tax[] = $value;
        });
        return [
            'quarterCapital'   => $capital,
            'quarterInterests' => $interests,
            'quarterTax'       => $tax,
            'yearCapital'      => array_values($yearCapital),
            'yearInterests'    => array_values($yearInterests),
            'yearTax'          => array_values($yearTax),
        ];
    }

}
