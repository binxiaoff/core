<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\{
    BorrowingMotive, ProjectsStatus, Users, UsersTypes, Zones
};
use Unilend\Bundle\CoreBusinessBundle\Service\ProjectRequestManager;

class dashboardController extends bootstrap
{
    private static $saleCollapsedStatus = [
        ProjectsStatus::POSTPONED,
        ProjectsStatus::PENDING_ANALYSIS,
        ProjectsStatus::ANALYSIS_REVIEW,
        ProjectsStatus::COMITY_REVIEW,
        ProjectsStatus::SUSPENSIVE_CONDITIONS,
        ProjectsStatus::A_FUNDER,
        ProjectsStatus::AUTO_BID_PLACED,
        ProjectsStatus::EN_FUNDING,
        ProjectsStatus::BID_TERMINATED
    ];

    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess(Zones::ZONE_LABEL_DASHBOARD);

        $this->menu_admin = 'dashboard';
    }

    public function _default()
    {
        /** @var \users $user */
        $user = $this->loadData('users');
        $user->get($_SESSION['user']['id_user']);

        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\BackOfficeUserManager $userManager */
        $userManager = $this->get('unilend.service.back_office_user_manager');

        if (
            $userManager->isUserGroupRisk($this->userEntity)
            || isset($this->params[0]) && 'risk' === $this->params[0] && ($userManager->isUserGroupManagement($this->userEntity) || $userManager->isUserGroupIT($this->userEntity))
        ) {
            $this->template     = 'risk';
            $this->userProjects = $this->getRiskUserProjects($user);
            $this->teamProjects = $this->getRiskTeamProjects($user);
        } elseif (
            $userManager->isUserGroupSales($this->userEntity)
            || isset($this->params[0]) && 'sales' === $this->params[0] && ($userManager->isUserGroupManagement($this->userEntity) || $userManager->isUserGroupIT($this->userEntity))
        ) {
            $this->template                     = 'sale';
            $this->userProjects                 = $this->getSaleUserProjects($user);
            $this->teamProjects                 = $this->getSaleTeamProjects($user);
            $this->upcomingProjects             = $this->getSaleUpcomingProjects();
            $this->impossibleEvaluationProjects = $this->getImpossibleEvaluationProjects();
            $this->collapsedStatus              = self::$saleCollapsedStatus;
            $this->salesPeople                  = $user->select('status = ' . Users::STATUS_ONLINE . ' AND id_user_type = ' . UsersTypes::TYPE_COMMERCIAL, 'firstname ASC, name ASC');
        } else {
            header('Location: ' . $this->lurl);
            die;
        }
    }

    public function _salesperson_projects()
    {
        $this->hideDecoration();
        $this->autoFireView = false;

        /** @var \users $user */
        $user = $this->loadData('users');

        if (($userId = filter_input(INPUT_POST, 'userId', FILTER_VALIDATE_INT)) && $user->get($userId)) {
            $this->templateProjects = $this->getSaleUserProjects($user);
        } else {
            $user->get($_SESSION['user']['id_user']);
            $this->templateProjects = $this->getSaleTeamProjects($user);
        }

        $this->collapsedStatus = self::$saleCollapsedStatus;

        ob_start();
        $this->fireView('saleProjects');
        $projectsList = ob_get_contents();
        ob_end_clean();

        echo json_encode([
            'count'    => $this->templateProjects['count'],
            'projects' => $projectsList
        ]);
    }

    /**
     * @param \users $user
     *
     * @return array
     */
    private function getRiskUserProjects(\users $user)
    {
        /** @var \projects $project */
        $project  = $this->loadData('projects');
        $projects = $project->getRiskUserProjects($user);

        return [
            'count'    => count($projects),
            'projects' => $this->formatProjects($projects)
        ];
    }

    /**
     * @param \users $user
     *
     * @return array
     */
    private function getRiskTeamProjects(\users $user)
    {
        /** @var \projects $project */
        $project  = $this->loadData('projects');
        $projects = $project->getRiskProjectsExcludingUser($user);

        return [
            'count'    => count($projects),
            'projects' => $this->formatProjects($projects)
        ];
    }

    /**
     * @param \users $user
     *
     * @return array
     */
    private function getSaleUserProjects(\users $user)
    {
        /** @var \projects $project */
        $project  = $this->loadData('projects');
        $projects = $project->getSaleUserProjects($user);

        return [
            'count'    => count($projects),
            'assignee' => false,
            'projects' => $this->formatProjects($projects)
        ];
    }

    /**
     * @param \users $user
     *
     * @return array
     */
    private function getSaleTeamProjects(\users $user)
    {
        /** @var \projects $project */
        $project  = $this->loadData('projects');
        $projects = $project->getSaleProjectsExcludingUser($user);

        return [
            'count'    => count($projects),
            'assignee' => true,
            'projects' => $this->formatProjects($projects)
        ];
    }

    /**
     * @return array
     */
    private function getSaleUpcomingProjects()
    {
        /** @var \projects $project */
        $project  = $this->loadData('projects');
        $projects = $project->getUpcomingSaleProjects();

        return [
            'count'    => count($projects),
            'assignee' => false,
            'projects' => $this->formatProjects($projects)
        ];
    }

    /**
     * @return array
     */
    private function getImpossibleEvaluationProjects()
    {
        /** @var \projects $project */
        $project  = $this->loadData('projects');
        $projects = $project->getImpossibleEvaluationProjects();

        return array_map(function ($project) {
            $project['creation'] = \DateTime::createFromFormat('Y-m-d H:i:s', $project['creation']);
            return $project;
        }, $projects);
    }

    /**
     * @param array $projects
     *
     * @return array
     */
    private function formatProjects(array $projects)
    {
        $formattedProjects = [];

        foreach ($projects as $project) {
            if (false === isset($formattedProjects[$project['status']])) {
                $formattedProjects[$project['status']] = [
                    'label'    => $project['status_label'],
                    'count'    => 0,
                    'projects' => []
                ];
            }

            $project['creation'] = \DateTime::createFromFormat('Y-m-d H:i:s', $project['creation']);

            if (isset($project['risk_status_datetime'])) {
                $project['risk_status_datetime'] = \DateTime::createFromFormat('Y-m-d H:i:s', $project['risk_status_datetime']);
            }

            if (isset($project['memo_datetime'])) {
                $project['memo_datetime'] = \DateTime::createFromFormat('Y-m-d H:i:s', $project['memo_datetime']);
            }

            $project['hasMonitoringEvent'] = $this->get('unilend.service.risk_data_monitoring_manager')->hasMonitoringEvent($project['siren']);

            $formattedProjects[$project['status']]['count']++;
            $formattedProjects[$project['status']]['projects'][] = $project;
        }

        return $formattedProjects;
    }

    public function _evaluate_projects()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var \projects $project */
        $project = $this->loadData('projects');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectStatusManager $projectStatusManager */
        $projectStatusManager = $this->get('unilend.service.project_status_manager');
        /** @var ProjectRequestManager $projectRequestManager */
        $projectRequestManager = $this->get('unilend.service.project_request_manager');

        $projects = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->findBy(['status' => ProjectsStatus::IMPOSSIBLE_AUTO_EVALUATION]);
        foreach ($projects as $projectEntity) {
            $project->get($projectEntity->getIdProject());

            if (null === $projectRequestManager->checkProjectRisk($project, $_SESSION['user']['id_user'])) {
                $status = empty($projectEntity->getIdCompany()->getIdClientOwner()->getTelephone()) ? ProjectsStatus::INCOMPLETE_REQUEST : ProjectsStatus::COMPLETE_REQUEST;
                $projectStatusManager->addProjectStatus($this->userEntity, $status, $project);
                $projectRequestManager->assignEligiblePartnerProduct($project, $_SESSION['user']['id_user'], true);
            }
        }

        header('Location: ' . $this->lurl . '/dashboard');
        die;
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    public function _activite()
    {
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\BackOfficeUserManager $userManager */
        $userManager = $this->get('unilend.service.back_office_user_manager');
        if (
            $userManager->isUserGroupSales($this->userEntity)
            || isset($this->params[0]) && 'sales' === $this->params[0] && ($userManager->isUserGroupManagement($this->userEntity) || $userManager->isUserGroupIT($this->userEntity))
        ) {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');

            $projectRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects');

            $projectsInSalesTreatmentStatusNb = count($projectRepository->findBy(['status' => ProjectsStatus::COMMERCIAL_REVIEW]));

            $firstDayOfThisYear = new DateTime('first day of this year');
            $today              = new DateTime();
            $firstOfLastYear    = new DateTime('first day of last year');
            $sameDayOfLastYear  = clone $today;
            $sameDayOfLastYear->modify('-1 year');
            $yearOverYear = $this->getReleaseProjectAndDelta($firstDayOfThisYear, $today, $firstOfLastYear, $sameDayOfLastYear);

            $firstDayOfLastMonth   = new DateTime('first day of last month');
            $lastDayOfLastMonth    = new DateTime('last day of last month');
            $firstDayOfTwoMonthAgo = new DateTime('first day of 2 month ago');
            $lastDayOfTwoMonthAgo  = new DateTime('last day of 2 month ago');
            $monthOverMonth        = $this->getReleaseProjectAndDelta($firstDayOfLastMonth, $lastDayOfLastMonth, $firstDayOfTwoMonthAgo, $lastDayOfTwoMonthAgo);

            $twelveMonthAgo = clone $firstDayOfLastMonth;
            $twelveMonthAgo->modify('-11 months');
            $twelveMonths = $this->get12rollingMonths($twelveMonthAgo);

            $statSentToAnalysis = $this->getProjectCountInStatusFor12RollingMonths(ProjectsStatus::PENDING_ANALYSIS, $twelveMonthAgo, $lastDayOfLastMonth, $twelveMonths);
            $statRepayment      = $this->getProjectCountInStatusFor12RollingMonths(ProjectsStatus::REMBOURSEMENT, $twelveMonthAgo, $lastDayOfLastMonth, $twelveMonths);

            $lastDayOfLastYear        = new DateTime('last day of december last year');
            $releasedProjectsThisYear = $projectRepository->getStatisticsByStatusByMonth(ProjectsStatus::REMBOURSEMENT, false, $firstDayOfThisYear, $today);
            $releasedProjectsLastYear = $projectRepository->getStatisticsByStatusByMonth(ProjectsStatus::REMBOURSEMENT, false, $firstOfLastYear, $lastDayOfLastYear);

            $borrowingMotives = [
                BorrowingMotive::ID_MOTIVE_PURCHASE_MATERIAL,
                BorrowingMotive::ID_MOTIVE_DEVELOPMENT,
                BorrowingMotive::ID_MOTIVE_REAL_ESTATE,
                BorrowingMotive::ID_MOTIVE_WORK,
                BorrowingMotive::ID_MOTIVE_CASH_FLOW,
                BorrowingMotive::ID_MOTIVE_OTHER
            ];

            $delays = [
                ['label' => 'Fundé', 'data' => $this->getDelayByStatus(ProjectsStatus::FUNDE, $borrowingMotives)],
                ['label' => 'En funding', 'data' => $this->getDelayByStatus(ProjectsStatus::EN_FUNDING, $borrowingMotives)],
                ['label' => 'Prép funding + conditions suspensives', 'data' => $this->getDelayByStatus(ProjectsStatus::PREP_FUNDING, $borrowingMotives)],
                ['label' => 'Reveue analyste', 'data' => $this->getDelayByStatus(ProjectsStatus::ANALYSIS_REVIEW, $borrowingMotives)],
                ['label' => 'Attent analyste', 'data' => $this->getDelayByStatus(ProjectsStatus::PENDING_ANALYSIS, $borrowingMotives)],
                ['label' => 'Traitement commerciale', 'data' => $this->getDelayByStatus(ProjectsStatus::COMMERCIAL_REVIEW, $borrowingMotives)],
                ['label' => 'Demande complète', 'data' => $this->getDelayByStatus(ProjectsStatus::COMPLETE_REQUEST, $borrowingMotives)]
            ];

            $this->render(null, [
                'projectsInSalesTreatmentStatusNb' => $projectsInSalesTreatmentStatusNb,
                'releasedProjectThisYearNb'        => $yearOverYear['number'],
                'releasedProjectThisYearAmount'    => $yearOverYear['amount'],
                'deltaYoyNbInPercentage'           => $yearOverYear['deltaNbInPercentage'],
                'deltaYoyAmountInPercentage'       => $yearOverYear['deltaAmountInPercentage'],
                'releasedProjectLastMonthNb'       => $monthOverMonth['number'],
                'releasedProjectLastMonthAmount'   => $monthOverMonth['amount'],
                'deltaMomNbInPercentage'           => $monthOverMonth['deltaNbInPercentage'],
                'deltaMomAmountInPercentage'       => $monthOverMonth['deltaAmountInPercentage'],
                'twelveMonths'                     => $twelveMonths,
                'statSentToAnalysisHighcharts'     => $statSentToAnalysis,
                'statRepaymentHighcharts'          => $statRepayment,
                'releasedProjectsThisYear'         => $releasedProjectsThisYear,
                'releasedProjectsLastYear'         => $releasedProjectsLastYear,
                'delays'                           => $delays,
                'borrowingMotives'                 => $borrowingMotives,
            ]);
        } else {
            header('Location: ' . $this->url);
            die;
        }
    }

    /**
     * @param DateTime $from
     * @param DateTime $end
     * @param DateTime $compareWithFrom
     * @param DateTime $compareWithEnd
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getReleaseProjectAndDelta(DateTime $from, DateTime $end, DateTime $compareWithFrom, DateTime $compareWithEnd) : array
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        $projectRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects');

        $releasedProject       = $projectRepository->findProjectsHavingHadStatusBetweenDates([ProjectsStatus::REMBOURSEMENT], $from, $end);
        $releasedProjectNb     = count($releasedProject);
        $releasedProjectAmount = 0;
        foreach ($releasedProject as $project) {
            $releasedProjectAmount += $project['amount'];
        }

        $releasedProjectToCompare       = $projectRepository->findProjectsHavingHadStatusBetweenDates([ProjectsStatus::REMBOURSEMENT], $compareWithFrom, $compareWithEnd);
        $releasedProjectToCompareNb     = count($releasedProjectToCompare);
        $releasedProjectToCompareAmount = 0;
        foreach ($releasedProjectToCompare as $project) {
            $releasedProjectToCompareAmount += $project['amount'];
        }

        $deltaNbInPercentage     = round(bcmul(bcdiv(($releasedProjectNb - $releasedProjectToCompareNb), $releasedProjectToCompareNb, 5), 100, 2), 1);
        $deltaAmountInPercentage = round(bcmul(bcdiv(bcsub($releasedProjectAmount, $releasedProjectToCompareAmount, 4), $releasedProjectToCompareAmount, 5), 100, 2), 1);

        return [
            'number'                  => $releasedProjectNb,
            'amount'                  => $releasedProjectAmount,
            'deltaNbInPercentage'     => $deltaNbInPercentage,
            'deltaAmountInPercentage' => $deltaAmountInPercentage,
        ];
    }

    /**
     * @param DateTime $start
     *
     * @return array
     */
    private function get12rollingMonths(DateTime $start) : array
    {
        $months     = [];
        $firstMonth = clone $start;

        for ($i = 1; $i <= 12; $i++) {
            $months[] = $firstMonth->format('m/Y');
            $firstMonth->modify('+1 month');
        }

        return $months;
    }

    /**
     * @param int      $status
     * @param DateTime $start
     * @param DateTime $end
     * @param array    $twelveMonths
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getProjectCountInStatusFor12RollingMonths(int $status, DateTime $start, DateTime $end, array $twelveMonths) : array
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        $countInStatus = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->getStatisticsByStatusByMonth($status, true, $start, $end);

        $countInStatusHighcharts = [];
        foreach ($countInStatus as $item) {
            $countInStatusHighcharts[$item['partner']][$item['month']] = (int) $item['number'];
        }
        ksort($countInStatusHighcharts);
        foreach ($countInStatusHighcharts as &$partnerStat) {
            foreach ($twelveMonths as $month) {
                if (false === isset($partnerStat[$month])) {
                    $partnerStat[$month] = 0;
                }
            }
            ksort($partnerStat);
        }

        return $countInStatusHighcharts;
    }

    private function getDelayByStatus(int $status, $motives)
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $delays        = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->getDelayByStatus($status, $motives);

        $formattedDelays = [];
        foreach ($delays as $index => $delay) {
            $formattedDelays[$delay['id_motive']] = round(bcdiv($delay['diff'], 1440, 3), 2);
        }

        return $formattedDelays;
    }

}
