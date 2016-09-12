<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\FrontBundle\Service\LenderAccountDisplayManager;
use Unilend\core\Loader;

class LenderDashboardController extends Controller
{
    /**
     * @Route("synthese", name="lender_dashboard")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @return Response
     */
    public function indexAction()
    {
        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');
        /** @var EntityManager $entityManager */
        $entityManager = $this->get('unilend.service.entity_manager');
        /** @var \lenders_account_stats $oLenderAccountStats */
        $oLenderAccountStats = $entityManager->getRepository('lenders_account_stats');
        /** @var \loans $loan */
        $loan = $entityManager->getRepository('loans');
        /** @var \echeanciers $echeancier */
        $echeancier = $entityManager->getRepository('echeanciers');
        /** @var \projects $project */
        $project = $entityManager->getRepository('projects');
        /** @var \companies $company */
        $company = $entityManager->getRepository('companies');
        /** @var \bids $bid */
        $bid = $entityManager->getRepository('bids');
        /** @var \wallets_lines $wallet_line */
        $wallet_line = $entityManager->getRepository('wallets_lines');
        /** @var \clients $client */
        $client = $entityManager->getRepository('clients');
        /** @var \lenders_accounts $lender */
        $lender = $entityManager->getRepository('lenders_accounts');

        $client->get($this->getUser()->getClientId());
        $lender->get($client->id_client, 'id_client_owner');

        $balance         = $this->getUser()->getBalance();
        $ongoingProjects = $project->selectProjectsByStatus([\projects_status::EN_FUNDING], '', [\projects::SORT_FIELD_END => \projects::SORT_DIRECTION_ASC], 0, 30);

        foreach ($ongoingProjects as $iKey => $aProject) {
            $project->get($aProject['id_project']);
            $ongoingProjects[$iKey]['avgrate'] = $project->getAverageInterestRate();
        }

        $ongoingBidsSum         = $bid->sumBidsEncours($lender->id_lender_account);
        $problematicProjects    = $echeancier->getProblematicProjects($lender->id_lender_account);
        $upcomingGrossInterests = $echeancier->getSumARemb($lender->id_lender_account, 'interets');
        $repaidGrossInterests   = $echeancier->getSumRemb($lender->id_lender_account . ' AND status_ra = 0', 'interets');
        $irr                    = 0;

        if ($this->getUser()->getLevel() > 0) {
            $aLastIRR = $oLenderAccountStats->getLastIRRForLender($lender->id_lender_account);
            if ($aLastIRR) {
                $irr = $ficelle->formatNumber($aLastIRR['tri_value']);
            } else {
                $fLossRate = $oLenderAccountStats->getLossRate($lender->id_lender_account, $lender);

                if ($fLossRate > 0) {
                    $irr = $ficelle->formatNumber(-$fLossRate);
                }
            }
        }
        $ongoingBidsByProject = [];
        $newPublishedProjects = [];

        foreach ($ongoingProjects as $iKey => $aProject) {
            $project->get($aProject['id_project']);
            $projectStats = $this->get('unilend.frontbundle.service.project_display_manager')->getProjectFundingStatistic($project);
            if (0 < $bid->counter('id_project = ' . $aProject['id_project'] . ' AND id_lender_account = ' . $lender->id_lender_account)) {
                $ongoingBidsByProject[$iKey]                 = [
                    'title'            => $aProject['title'],
                    'amount'           => $aProject['amount'],
                    'publication_date' => $aProject['date_publication_full'],
                    'days_left'        => $aProject['daysLeft'],
                    'finished'         => ($aProject['status'] > \projects_status::EN_FUNDING || (new \DateTime($aProject['date_retrait_full'])) < (new \DateTime('NOW'))),
                    'end_date'         => $aProject['date_retrait_full'],
                    'funding_duration' => $projectStats['fundingTime']->days
                ];
                $ongoingBidsByProject[$iKey]['aPendingBids'] = $bid->getBidsByStatus(\bids::STATUS_BID_PENDING, $aProject['id_project'], $lender->id_lender_account);
            }

            if ((new \DateTime($aProject['date_publication_full']))->diff(new \DateTime($this->getUser()->getLastLoginDate()))->days > 0 && $aProject['daysLeft'] >= 0) {
                $company->get($aProject['id_company']);
                $newPublishedProjects[] = [
                    'title'            => $aProject['title'],
                    'slug'             => $aProject['slug'],
                    'company_address'  => (false === empty($company->city)) ? $company->city . ', ' : '' . $company->zip,
                    'amount'           => $aProject['amount'],
                    'days_left'        => $aProject['daysLeft'],
                    'risk'             => $aProject['risk'],
                    'average_rate'     => $aProject['avgrate'],
                    'bid_count'        => count($bid->getBidsByStatus(\bids::STATUS_BID_PENDING, $aProject['id_project'])),
                    'finished'         => ($aProject['status'] > \projects_status::EN_FUNDING || (new \DateTime($aProject['date_retrait_full'])) < (new \DateTime('NOW'))),
                    'end_date'         => $aProject['date_retrait_full'],
                    'funding_duration' => $projectStats['fundingTime']->days
                ];
            }
        }
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\IRRManager $oIRRManager */
        $oIRRManager = $this->get('unilend.service.irr_manager');
        /** @var LenderAccountDisplayManager $lenderDisplayManager */
        $lenderDisplayManager = $this->get('unilend.frontbundle.service.lender_account_display_manager');

        $aLastUnilendIRR        = $oIRRManager->getLastUnilendIRR();
        $IRRUnilend             = $ficelle->formatNumber($aLastUnilendIRR['value']);
        $lenderRepaymentsData   = $echeancier->getRepaymentAmountDetailsByPeriod($lender->id_lender_account);
        $repaymentDataPerPeriod = $this->getQuarterAndYearSum($lenderRepaymentsData);
        $repaymentDateRange     = $echeancier->getFirstAndLastRepaymentDates($lender->id_lender_account);

        $monthAxisData   = $this->getMonthAxis($repaymentDateRange);
        $quarterAxisData = $this->getQuarterAxis($lenderRepaymentsData);
        $yearAxisData    = $this->getYearAxis($repaymentDateRange);


        return $this->render(
            '/pages/lender_dashboard/lender_dashboard.html.twig',
            [
                'dashboardPanels'    => $this->getDashboardPreferences(),
                'lenderDetails'      => [
                    'balance'             => $balance,
                    'level'               => $this->getUser()->getLevel(),
                    'unilend_irr'         => $IRRUnilend,
                    'irr'                 => $irr,
                    'initials'            => $this->getUser()->getInitials(),
                    'number_of_companies' => $lender->countCompaniesLenderInvestedIn($lender->id_lender_account),
                    'numberOfLoans'       => $loan->getLoansCount($lender->id_lender_account),
                    'numberOfBorrowers'   => $loan->getProjectsCount($lender->id_lender_account),
                ],
                'walletData'         => [
                    'by_sector' => $lenderDisplayManager->getLenderLoansAllocationByCompanySector($lender->id_lender_account),
                    'by_region' => $lenderDisplayManager->getLenderLoansAllocationByRegion($lender->id_lender_account),
                ],
                'amountDetails'      => [
                    'loaned_amount'     => round($loan->sumPrets($lender->id_lender_account), 2),
                    'blocked_amount'    => round($ongoingBidsSum, 2),
                    'expected_earnings' => round($repaidGrossInterests + $upcomingGrossInterests - $problematicProjects['interests'], 2),
                    'deposited_amount'  => $wallet_line->getSumDepot($lender->id_lender_account, '10,30')
                ],
                'capitalDetails'     => [
                    'repaid_capital'        => round($echeancier->getSumRemb($lender->id_lender_account, 'capital'), 2),
                    'owed_capital'          => round($echeancier->getSumARemb($lender->id_lender_account, 'capital') - $problematicProjects['capital'], 2),
                    'capital_in_difficulty' => round($problematicProjects['capital'], 2)
                ],
                'interestsDetails'   => [
                    'received_interests'      => round($repaidGrossInterests, 2),
                    'upcoming_interests'      => round($upcomingGrossInterests - $problematicProjects['interests'], 2),
                    'interests_in_difficulty' => round($problematicProjects['interests'], 2)
                ],
                'ongoingBids'        => $bid->counter('id_lender_account = ' . $lender->id_lender_account . ' AND status = ' . \bids::STATUS_BID_PENDING),
                'ongoingProjects'    => $ongoingBidsByProject,
                'newOngoingProjects' => $newPublishedProjects,
                'timeAxis'           => [
                    'month'   => $monthAxisData['monthAxis'],
                    'quarter' => $quarterAxisData['quarterAxis'],
                    'year'    => $yearAxisData['yearAxis']
                ],
                'monthSum'           => [
                    'capital'   => array_column($lenderRepaymentsData, 'capital'),
                    'interests' => array_column($lenderRepaymentsData, 'interests'),
                    'tax'       => array_column($lenderRepaymentsData, 'tax'),
                ],
                'quarterSum'         => [
                    'capital'   => $repaymentDataPerPeriod['quarterCapital'],
                    'interests' => $repaymentDataPerPeriod['quarterInterests'],
                    'tax'       => $repaymentDataPerPeriod['quarterTax'],
                ],
                'yearSum'            => [
                    'capital'   => $repaymentDataPerPeriod['yearCapital'],
                    'interests' => $repaymentDataPerPeriod['yearInterests'],
                    'tax'       => $repaymentDataPerPeriod['yearTax'],
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
     * @Route("synthese/preferences", name="save_user_preferences")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function saveUserDisplayPreferencesAction(Request $request)
    {
        /** @var \user_preferences $userPreferences */
        $userPreferences = $this->get('unilend.service.entity_manager')->getRepository('user_preferences');
        $pageName        = 'lender_dashboard';
        $postData        = $request->request->get('panels');

        if ($request->getMethod() === 'PUT') {
            try {
                $preferences = $userPreferences->getUserPreferencesByPage($this->getUser()->getClientId(), $pageName);

                foreach ($postData as $panel) {
                    if (isset($preferences[$panel['id']])) {
                        if ($preferences[$panel['id']]['hidden'] != $panel['hidden'] || $preferences[$panel['id']]['panel_order'] != $panel['order']) {
                            $userPreferences->get($preferences[$panel['id']]['id_user_preferences']);
                            $userPreferences->hidden      = ('true' == $panel['hidden']) ? 1 : 0;
                            $userPreferences->panel_order = $panel['order'];
                            $userPreferences->updated     = date('Y-m-d H:i:s');
                            $userPreferences->update();
                        }
                    } else {
                        $userPreferences->id_client   = $this->getUser()->getClientId();
                        $userPreferences->page_name   = $pageName;
                        $userPreferences->panel_name  = $panel['id'];
                        $userPreferences->panel_order = $panel['order'];
                        $userPreferences->hidden      = ('true' == $panel['hidden']) ? 1 : 0;
                        $userPreferences->added       = date('Y-m-d H:i:s');
                        $userPreferences->updated     = date('Y-m-d H:i:s');

                        $userPreferences->create();
                    }
                }
                $result = ['success' => 1, 'data' => $postData, 'preferences' => $preferences];
            } catch (\Exception $exception) {
                $result = ['error' => 1, 'msg' => $exception->getMessage(), 'data' => $postData];
            }
        } elseif ($request->getMethod() === 'GET') {
            try {
                $preferences = $userPreferences->getUserPreferencesByPage($this->getUser()->getClientId(), $pageName);

                if (false === empty($preferences)) {
                    foreach ($preferences as $panelName => $panel) {
                        $data['panels'][] = ['id' => $panelName, 'order' => $panel['panel_order'], 'hidden' => ($panel['hidden'] == 1) ? true : false];
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
        /** @var \user_preferences $userPreferences */
        $userPreferences = $this->get('unilend.service.entity_manager')->getRepository('user_preferences');

        $pageName           = 'lender_dashboard';
        $defaultPreferences = [
            'myaccount'   => ['order' => 0, 'id' => 'myaccount', 'hidden' => false],
            'userlevel'   => ['order' => 1, 'id' => 'userlevel', 'hidden' => false],
            'mywallet'    => ['order' => 2, 'id' => 'mywallet', 'hidden' => false],
            'myoffers'    => ['order' => 3, 'id' => 'myoffers', 'hidden' => false],
            'projects'    => ['order' => 4, 'id' => 'projects', 'hidden' => false],
            'myrembourse' => ['order' => 5, 'id' => 'myrembourse', 'hidden' => false],
        ];

        try {
            $preferences = $userPreferences->getUserPreferencesByPage($this->getUser()->getClientId(), $pageName);

            if (false === empty($preferences)) {
                foreach ($preferences as $panelName => $panel) {
                    $userPreferencesData[$panelName] = ['id' => $panelName, 'order' => $panel['panel_order'], 'hidden' => ($panel['hidden'] == 1) ? true : false];
                }
            } else {
                $userPreferencesData = $defaultPreferences;
            }
        } catch (\Exception $exception) {
            $userPreferencesData = $defaultPreferences;
        }
        return $userPreferencesData;
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
        $monthNames = $this->getMonthNames()['shortNames'];
        $quarterLabels     = [1 => $monthNames[1] . '-' . $monthNames[3], 2 => $monthNames[4] . '-' . $monthNames[6], 3 => $monthNames[7] . '-' . $monthNames[9], 4 => $monthNames[10] . '-' . $monthNames[12]];
        $quarterAxis       = [];
        $quarterBandOrigin = 0;

        foreach ($lenderRepaymentsData as $lenderRepayment) {
            if (date('Y-m') == $lenderRepayment['month']) {
                $quarterBandOrigin = count($quarterAxis) - 1.5;
            }

            if (false === in_array($quarterLabel = $quarterLabels[$lenderRepayment['quarter']] . ' ' . $lenderRepayment['year'], $quarterAxis)) {
                $quarterAxis[] = $quarterLabel;
            }
        }

        return ['quarterAxis' => $quarterAxis, 'quarterBandOrigin' => $quarterBandOrigin];
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
            $quarterInterests[$lenderRepayment['year']][$lenderRepayment['quarter']] += $lenderRepayment['interests'];
            $quarterTax[$lenderRepayment['year']][$lenderRepayment['quarter']] += $lenderRepayment['tax'];

            $yearCapital[$lenderRepayment['year']] += $lenderRepayment['capital'];
            $yearInterests[$lenderRepayment['year']] += $lenderRepayment['interests'];
            $yearTax[$lenderRepayment['year']] += $lenderRepayment['tax'];
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
