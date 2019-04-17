<?php

namespace Unilend\Controller\Unilend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\{Bids, Clients, ClientsStatus, Echeanciers, LenderStatistic, Loans, Operation, OperationType, Product, Projects, ProjectsStatus, Wallet, WalletType};
use Unilend\Repository\ProjectsRepository;

class LenderDashboardController extends Controller
{
    const REPAYMENT_TIME_FRAME_MONTH   = 'month';
    const REPAYMENT_TIME_FRAME_QUARTER = 'quarter';
    const REPAYMENT_TIME_FRAME_YEAR    = 'year';

    /**
     * @Route("synthese", name="lender_dashboard")
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param UserInterface|Clients|null $client
     *
     * @return Response
     */
    public function indexAction(?UserInterface $client): Response
    {
        if (ClientsStatus::STATUS_CREATION === $client->getIdClientStatusHistory()->getIdStatus()->getId()) {
            return $this->redirectToRoute('lender_subscription_documents', ['clientHash' => $client->getHash()]);
        }

        if (false === $client->isGrantedLenderRead()) {
            return $this->redirectToRoute('home');
        }

        $entityManagerSimulator = $this->get('unilend.service.entity_manager');
        /** @var \loans $loan */
        $loan = $entityManagerSimulator->getRepository('loans');
        /** @var \echeanciers $lenderRepayment */
        $lenderRepayment = $entityManagerSimulator->getRepository('echeanciers');
        /** @var \bids $bid */
        $bid = $entityManagerSimulator->getRepository('bids');

        $entityManager               = $this->get('doctrine.orm.entity_manager');
        $walletRepository            = $entityManager->getRepository(Wallet::class);
        $repaymentScheduleRepository = $entityManager->getRepository(Echeanciers::class);
        $projectRepository           = $entityManager->getRepository(Projects::class);

        /** @var Wallet $wallet */
        $wallet          = $walletRepository->getWalletByType($client, WalletType::LENDER);
        $products        = $entityManager->getRepository(Product::class)->findAvailableProductsByClient($wallet->getIdClient());

        $ongoingProjects = $projectRepository->findByWithCustomSort(
            ['status' => ProjectsStatus::STATUS_ONLINE, 'idProduct' => $products],
            [ProjectsRepository::SORT_FIELD_END => 'ASC'],
            30,
            0,
            true
        );

        $problematicProjects    = $lenderRepayment->getProblematicProjects($wallet->getId());
        $upcomingGrossInterests = $lenderRepayment->getOwedInterests(['id_lender' => $wallet->getId()]);
        $repaidGrossInterests   = $lenderRepayment->getRepaidInterests(['id_lender' => $wallet->getId()]);

        $ongoingBidsByProject = [];
        $publishedProjects    = [];

        /** @var Projects $project */
        foreach ($ongoingProjects as $project) {
            if (0 < $bid->counter('id_project = ' . $project->getIdProject() . ' AND id_wallet = ' . $wallet->getId())) {
                $ongoingBidsByProject[] = [
                    'title'            => $project->getTitle(),
                    'slug'             => $project->getSlug(),
                    'amount'           => $project->getAmount(),
                    'publication_date' => $project->getDatePublication(),
                    'days_left'        => $project->getDateRetrait()->diff(new \DateTime('NOW'))->days,
                    'finished'         => $project->getStatus() > ProjectsStatus::STATUS_ONLINE || $project->getDateRetrait() < new \DateTime('NOW'),
                    'end_date'         => $project->getDateRetrait(),
                    'pending_bids'     => $bid->getBidsByStatus(Bids::STATUS_PENDING, $project->getIdProject(), $wallet->getId())
                ];
            }

            $company = $project->getIdCompany();
            $publishedProjects[] = [
                'title'            => $project->getTitle(),
                'slug'             => $project->getSlug(),
                'company_address'  => $company->getIdAddress() ? $company->getIdAddress()->getCity() . ', ' . $company->getIdAddress()->getZip() : '',
                'amount'           => $project->getAmount(),
                'days_left'        => $project->getDateRetrait()->diff(new \DateTime('NOW'))->days,
                'risk'             => $project->getRisk(),
                'average_rate'     => $projectRepository->getAverageInterestRate($project),
                'bid_count'        => count($bid->getBidsByStatus(Bids::STATUS_PENDING, $project->getIdProject())),
                'finished'         => $project->getStatus() > ProjectsStatus::STATUS_ONLINE || $project->getDateRetrait() < new \DateTime('NOW'),
                'end_date'         => $project->getDateRetrait()
            ];
        }

        $lenderDisplayManager    = $this->get('unilend.frontbundle.service.lender_account_display_manager');
        $repaymentDateRange      = $lenderRepayment->getFirstAndLastRepaymentDates($wallet->getId());
        $lenderRepaymentsDetails = $repaymentScheduleRepository->getLenderRepaymentsDetails($wallet);
        $lenderRepaymentsData    = [];

        foreach ($lenderRepaymentsDetails as $lenderRepaymentDetail) {
            $lenderRepaymentsData[$lenderRepaymentDetail['month']]                 = $lenderRepaymentDetail;
            $lenderRepaymentsData[$lenderRepaymentDetail['month']]['capital']      = (float) $lenderRepaymentDetail['capital'];
            $lenderRepaymentsData[$lenderRepaymentDetail['month']]['netInterests'] = (float) $lenderRepaymentDetail['netInterests'];
            $lenderRepaymentsData[$lenderRepaymentDetail['month']]['taxes']        = (float) $lenderRepaymentDetail['taxes'];
        }
        $lenderRepaymentsData += $this->getPaddingData($repaymentDateRange);
        ksort($lenderRepaymentsData);
        $repaymentDataPerPeriod = $this->getQuarterAndYearSum($lenderRepaymentsData);
        $monthAxisData          = $this->getMonthAxis($repaymentDateRange);
        $quarterAxisData        = $this->getQuarterAxis($lenderRepaymentsData);
        $yearAxisData           = $this->getYearAxis($repaymentDateRange);

        $lenderOperationsManager = $this->get('unilend.service.lender_operations_manager');
        try {
            $provisionAmount = $lenderOperationsManager->getTotalProvisionAmount($wallet);
            $withdrawAmount  = $lenderOperationsManager->getTotalWithdrawalAmount($wallet);
            $depositedAmount = round(bcsub($provisionAmount, $withdrawAmount, 4), 2);
        } catch (\Exception $exception) {
            $depositedAmount = 0;
            $this->get('logger')->error('An error occurred when try to get lender deposite amount. Error: ' . $exception->getMessage(), [
                'id_wallet' => $wallet->getId(),
                'class'     => __CLASS__,
                'function'  => __FUNCTION__,
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine()
            ]);
        }

        $irrData          = $this->getIRRDetailsForUserLevelWidget($client);
        $hasBids          = 0 < $entityManager->getRepository(Bids::class)->countByClientInPeriod($wallet->getAdded(), new \DateTime('NOW'), $wallet->getIdClient()->getIdClient());
        $hasAcceptedLoans = 0 < $entityManager->getRepository(Operation::class)->sumDebitOperationsByTypeSince($wallet, [OperationType::LENDER_LOAN]);

        $loansRepository = $entityManager->getRepository(Loans::class);

        return $this->render(
            'lender_dashboard/index.html.twig',
            [
                'dashboardPanels'   => $this->getDashboardPreferences($client),
                'lenderDetails' => [
                    'numberOfLoans'             => $loansRepository->getDefinitelyAcceptedLoansCount($wallet),
                    'companiesLenderInvestedIn' => $irrData['companiesLenderInvestedIn'],
                    'hasBids'                   => $hasBids,
                    'hasAcceptedLoans'          => $hasAcceptedLoans
                ],
                'irrData'           => $irrData,
                'walletData'        => [
                    'by_sector' => $lenderDisplayManager->getLenderLoansAllocationByCompanySector($wallet->getIdClient()),
                    'by_region' => $lenderDisplayManager->getLenderLoansAllocationByRegion($wallet->getIdClient()),
                ],
                'amountDetails'     => [
                    'loaned_amount'     => round($loan->sumPrets($wallet->getId()), 2),
                    'blocked_amount'    => round($wallet->getCommittedBalance(), 2),
                    'expected_earnings' => round($repaidGrossInterests + $upcomingGrossInterests - $problematicProjects['interests'], 2),
                    'deposited_amount'  => $depositedAmount
                ],
                'capitalDetails'    => [
                    'repaid_capital'        => round($lenderRepayment->getRepaidCapital(['id_lender' => $wallet->getId()]), 2),
                    'owed_capital'          => round($lenderRepayment->getOwedCapital(['id_lender' => $wallet->getId()]) - $problematicProjects['capital'], 2),
                    'capital_in_difficulty' => round($problematicProjects['capital'], 2)
                ],
                'interestsDetails'  => [
                    'received_interests'      => round($repaidGrossInterests, 2),
                    'upcoming_interests'      => round($upcomingGrossInterests - $problematicProjects['interests'], 2),
                    'interests_in_difficulty' => round($problematicProjects['interests'], 2)
                ],
                'ongoingBids'       => $bid->counter('id_wallet = ' . $wallet->getId() . ' AND status = ' . Bids::STATUS_PENDING),
                'ongoingProjects'   => $ongoingBidsByProject,
                'publishedProjects' => $publishedProjects,
                'timeAxis'          => [
                    'month'   => $monthAxisData['monthAxis'],
                    'quarter' => $quarterAxisData['quarterAxis'],
                    'year'    => $yearAxisData['yearAxis']
                ],
                'monthSum'          => [
                    'capital'   => array_column($lenderRepaymentsData, 'capital'),
                    'interests' => array_column($lenderRepaymentsData, 'netInterests'),
                    'tax'       => array_column($lenderRepaymentsData, 'taxes'),
                    'max'       => $repaymentScheduleRepository->getMaxRepaymentAmountForLender($wallet->getId(), self::REPAYMENT_TIME_FRAME_MONTH)
                ],
                'quarterSum'        => [
                    'capital'   => $repaymentDataPerPeriod['quarterCapital'],
                    'interests' => $repaymentDataPerPeriod['quarterInterests'],
                    'tax'       => $repaymentDataPerPeriod['quarterTax'],
                    'max'       => $repaymentScheduleRepository->getMaxRepaymentAmountForLender($wallet->getId(), self::REPAYMENT_TIME_FRAME_QUARTER)
                ],
                'yearSum'           => [
                    'capital'   => $repaymentDataPerPeriod['yearCapital'],
                    'interests' => $repaymentDataPerPeriod['yearInterests'],
                    'tax'       => $repaymentDataPerPeriod['yearTax'],
                    'max'       => $repaymentScheduleRepository->getMaxRepaymentAmountForLender($wallet->getId(), self::REPAYMENT_TIME_FRAME_YEAR)
                ],
                'bandOrigin'        => [
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
     * @param Request                    $request
     * @param UserInterface|Clients|null $client
     *
     * @return JsonResponse
     */
    public function saveUserDisplayPreferencesAction(Request $request, ?UserInterface $client): JsonResponse
    {
        if (false === $client->isGrantedLenderRead()) {
            return $this->json(['error' => 1, 'msg' => '']);
        }

        $entityManagerSimulator = $this->get('unilend.service.entity_manager');
        $entityManager          = $this->get('doctrine.orm.entity_manager');
        /** @var \lender_panel_preference $panelPreferences */
        $panelPreferences = $entityManagerSimulator->getRepository('lender_panel_preference');

        $wallet   = $entityManager->getRepository(Wallet::class)->getWalletByType($client, WalletType::LENDER);
        $pageName = 'lender_dashboard';
        $postData = $request->request->get('panels');
        $result   = ['error' => 1, 'msg' => ''];

        if ($request->getMethod() === 'PUT') {
            try {
                $preferences = $panelPreferences->getLenderPreferencesByPage($wallet->getId(), $pageName);
                foreach ($postData as $panel) {
                    if (
                        $panel['order'] == (int) $panel['order']
                        && isset($panel['hidden'], $panel['order'])
                    ) {
                        if (
                            isset ($preferences[$panel['id']])
                            && ($preferences[$panel['id']]['hidden'] != $panel['hidden']
                            || $preferences[$panel['id']]['panel_order'] != $panel['order'])
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
     * @param Clients $client
     *
     * @return array
     */
    private function getDashboardPreferences(Clients $client): array
    {
        $entityManagerSimulator = $this->get('unilend.service.entity_manager');
        $entityManager          = $this->get('doctrine.orm.entity_manager');
        /** @var \lender_panel_preference $panelPreferences */
        $panelPreferences = $entityManagerSimulator->getRepository('lender_panel_preference');

        $wallet               = $entityManager->getRepository(Wallet::class)->getWalletByType($client, WalletType::LENDER);
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
     *
     * @return array
     * @throws \Exception
     */
    private function getPaddingData(array $repaymentDateRange): array
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
     *
     * @return array
     * @throws \Exception
     */
    private function getMonthAxis(array $repaymentDateRange): array
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
     *
     * @return array
     */
    private function getQuarterAxis(array $lenderRepaymentsData): array
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
     *
     * @return array
     * @throws \Exception
     */
    private function getMonthNames(): array
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
     *
     * @return array
     */
    private function getYearAxis(array $repaymentDateRange): array
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
     *
     * @return array
     */
    private function getQuarterAndYearSum(array $lenderRepaymentsData): array
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

    /**
     * @param Clients $client
     *
     * @return array
     *
     * @throws \Doctrine\DBAL\Cache\CacheException
     * @throws \Exception
     */
    private function getIRRDetailsForUserLevelWidget(Clients $client): array
    {
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $lenderManager = $this->get('unilend.service.lender_manager');

        /** @var Wallet $wallet */
        $wallet                    = $entityManager->getRepository(Wallet::class)->getWalletByType($client, WalletType::LENDER);
        $hasLossRate               = false;
        $widgetValue               = 0;
        $irrHasBeenCalculated      = false;
        $irrTranslationType        = '';
        $companiesLenderInvestedIn = $entityManager->getRepository(Projects::class)->countCompaniesLenderInvestedIn($wallet->getId());

        if (0 < $companiesLenderInvestedIn) {
            /** @var LenderStatistic $lastIRR */
            $lastIRR = $entityManager->getRepository(LenderStatistic::class)->findOneBy(['idWallet' => $wallet, 'typeStat' => LenderStatistic::TYPE_STAT_IRR], ['added' => 'DESC']);
            if (null !== $lastIRR) {
                $irrHasBeenCalculated  = true;
                switch ($lastIRR->getStatus()) {
                    case LenderStatistic::STAT_VALID_OK:
                        $widgetValue        = $lastIRR->getValue();
                        $irrTranslationType = $lastIRR->getValue() >= 0 ? 'positive-' : 'negative-';
                        break;
                    case LenderStatistic::STAT_VALID_NOK:
                        $lossRate           = $lenderManager->getLossRate($wallet->getIdClient());
                        $hasLossRate        = true;
                        if ($lossRate > 0) {
                            $widgetValue = -$lossRate;
                        }
                        break;
                    default:
                        //should not happen, there are only 2 status
                        break;
                }
            }
        }

        return [
            'widgetValue'               => $widgetValue,
            'irrTranslation'            => $irrTranslationType,
            'companiesLenderInvestedIn' => $companiesLenderInvestedIn,
            'hasLossRate'               => $hasLossRate,
            'irrHasBeenCalculated'      => $irrHasBeenCalculated
        ];
    }
}
