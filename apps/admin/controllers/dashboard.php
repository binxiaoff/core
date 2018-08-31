<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\{
    BorrowingMotive, Partner, Projects, ProjectsStatus, Users, UsersTypes, Zones
};
use Unilend\Bundle\CoreBusinessBundle\Service\ProjectRequestManager;
use Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessageProvider;

class dashboardController extends bootstrap
{
    const SALES_MY_PROJECTS_COLLAPSED_STATUS = [
        ProjectsStatus::EN_FUNDING,
        ProjectsStatus::SUSPENSIVE_CONDITIONS,
        ProjectsStatus::ANALYSIS_REVIEW,
        ProjectsStatus::PENDING_ANALYSIS,
        ProjectsStatus::POSTPONED,
        ProjectsStatus::INCOMPLETE_REQUEST
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

        $this->showProjectsToDecline = false;

        if (
            $userManager->isUserGroupRisk($this->userEntity)
            || isset($this->params[0]) && 'risk' === $this->params[0] && ($userManager->isUserGroupManagement($this->userEntity) || $userManager->isUserGroupIT($this->userEntity))
        ) {
            $this->template     = 'risk';
            $this->userProjects = $this->getRiskUserProjects($user);
            $this->teamProjects = $this->getRiskTeamProjects($user);

            if ($userManager->isUserGroupRisk($this->userEntity) || $userManager->isUserGroupCompliance($this->userEntity)) {
                $this->showProjectsToDecline = true;
            }
        } elseif (
            $userManager->isUserGroupSales($this->userEntity)
            || isset($this->params[0]) && 'sales' === $this->params[0] && ($userManager->isUserGroupManagement($this->userEntity) || $userManager->isUserGroupIT($this->userEntity))
        ) {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager                      = $this->get('doctrine.orm.entity_manager');
            $this->template                     = 'sale';
            $this->userProjects                 = $this->getSaleUserProjects($this->userEntity);
            $this->teamProjects                 = $this->getSaleTeamProjects($this->userEntity);
            $this->upcomingProjects             = $this->getSaleUpcomingProjects();
            $this->impossibleEvaluationProjects = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->findImpossibleEvaluationProjects();
            $this->collapsedStatus              = self::SALES_MY_PROJECTS_COLLAPSED_STATUS;
            $this->salesPeople                  = $user->select('status = ' . Users::STATUS_ONLINE . ' AND id_user_type = ' . UsersTypes::TYPE_COMMERCIAL, 'firstname ASC, name ASC');
            $this->otherTasksProjects           = [
                'Transfet de fonds'       => $this->getProjectsWithFundsToRelease(),
                'Changement Rib en cours' => $this->getProjectsWithMandatesPendingSignature()
            ];
        } else {
            header('Location: ' . $this->lurl);
            die;
        }
    }

    public function _salesperson_projects()
    {
        $this->hideDecoration();
        $this->autoFireView = false;

        $userId = $this->request->request->getInt('userId');
        $user   = $this->get('doctrine.orm.entity_manager')
            ->getRepository('UnilendCoreBusinessBundle:Users')
            ->find($userId);

        $this->templateProjects = null == $user ? $this->getSaleTeamProjects($this->userEntity) : $this->getSaleUserProjects($user);
        $this->collapsedStatus  = ProjectsStatus::SALES_TEAM;

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
     * @param Users $user
     *
     * @return array
     */
    private function getSaleUserProjects(Users $user)
    {
        $projects = $this->get('doctrine.orm.entity_manager')
            ->getRepository('UnilendCoreBusinessBundle:Projects')
            ->getSaleUserProjects($user);

        return [
            'count'    => count($projects),
            'assignee' => false,
            'projects' => $this->formatProjects($projects)
        ];
    }

    /**
     * @param Users $user
     *
     * @return array
     */
    private function getSaleTeamProjects(Users $user)
    {
        $projects = $this->get('doctrine.orm.entity_manager')
            ->getRepository('UnilendCoreBusinessBundle:Projects')
            ->getSaleProjectsExcludingUser($user);

        return [
            'count'    => count($projects),
            'assignee' => true,
            'projects' => $this->formatProjects($projects)
        ];
    }

    /**
     * @return array
     */
    private function getSaleUpcomingProjects(): array
    {
        $projects = $this->get('doctrine.orm.entity_manager')
            ->getRepository('UnilendCoreBusinessBundle:Projects')
            ->getUpcomingSaleProjects();

        return [
            'count'    => count($projects),
            'assignee' => false,
            'projects' => $this->formatProjects($projects)
        ];
    }

    /**
     * @return array
     */
    private function getProjectsWithMandatesPendingSignature(): array
    {
        $pendingMandateProjects = [];
        $projects               = $this->get('doctrine.orm.entity_manager')
            ->getRepository('UnilendCoreBusinessBundle:Projects')
            ->getProjectsInRepaymentWithPendingMandate();

        foreach ($projects as $project) {
            $this->addAdditionalData($project);
            $pendingMandateProjects[] = $project;
        }

        return [
            'count'    => count($pendingMandateProjects),
            'projects' => $pendingMandateProjects
        ];
    }

    /**
     * @return array
     */
    private function getProjectsWithFundsToRelease(): array
    {
        $fundsToReleaseProjects = [];
        $projects               = $this->get('doctrine.orm.entity_manager')
            ->getRepository('UnilendCoreBusinessBundle:Projects')
            ->getProjectsWithFundsToRelease();

        foreach ($projects as $project) {
            $this->addAdditionalData($project);
            $fundsToReleaseProjects[] = $project;
        }

        return [
            'count'    => count($fundsToReleaseProjects),
            'projects' => $fundsToReleaseProjects
        ];
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

            $this->addAdditionalData($project);

            $formattedProjects[$project['status']]['count']++;
            $formattedProjects[$project['status']]['projects'][] = $project;
        }

        return $formattedProjects;
    }

    /**
     * @param array $project
     */
    private function addAdditionalData(array &$project): void
    {
        if (isset($project['risk_status_datetime'])) {
            $project['risk_status_datetime'] = \DateTime::createFromFormat('Y-m-d H:i:s', $project['risk_status_datetime']);
        }

        if (isset($project['memo_datetime']) && false === $project['memo_datetime'] instanceof \DateTime) {
            $project['memo_datetime'] = \DateTime::createFromFormat('Y-m-d H:i:s', $project['memo_datetime']);
        }

        $project['hasMonitoringEvent'] = $this->get('unilend.service.risk_data_monitoring_manager')->hasMonitoringEvent($project['siren']);
    }

    public function _evaluate_projects()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectStatusManager $projectStatusManager */
        $projectStatusManager = $this->get('unilend.service.project_status_manager');
        /** @var ProjectRequestManager $projectRequestManager */
        $projectRequestManager = $this->get('unilend.service.project_request_manager');
        $projectRepository     = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects');

        foreach ($projectRepository->findImpossibleEvaluationProjects() as $project) {
            if (null === $projectRequestManager->checkProjectRisk($project, $this->userEntity->getIdUser())) {
                $status = ProjectsStatus::INCOMPLETE_REQUEST;

                if ($project->getIdCompany() && $project->getIdCompany()->getIdClientOwner() && false === empty($project->getIdCompany()->getIdClientOwner()->getTelephone())) {
                    $status = ProjectsStatus::COMPLETE_REQUEST;
                }

                try {
                    $projectStatusManager->addProjectStatus($this->userEntity, $status, $project);
                } catch (\Doctrine\ORM\OptimisticLockException $exception) {
                    $this->get('logger')->error('Could not update project status into ' . $status . ' - Error message: ' . $exception->getMessage(), [
                        'id_project' => $project->getIdProject(),
                        'file'       => $exception->getFile(),
                        'line'       => $exception->getLine(),
                        'class'      => __CLASS__,
                        'function'   => __FUNCTION__
                    ]);

                    continue;
                }
                $projectRequestManager->assignEligiblePartnerProduct($project, $this->userEntity->getIdUser(), true);
            }

            if ($project->getStatus() === ProjectsStatus::NOT_ELIGIBLE) {
                $this->sendProjectRejectionEmail($project);
            }
        }

        header('Location: ' . $this->lurl . '/dashboard');
        die;
    }

    /**
     * @param Projects                 $project
     */
    private function sendProjectRejectionEmail(Projects $project): void
    {
        $company = $project->getIdCompany();

        if (null !== $company && null !== $company->getIdClientOwner() && false === empty($company->getIdClientOwner()->getEmail())) {
            /** @var TemplateMessageProvider $messageProvider */
            $messageProvider = $this->get('unilend.swiftmailer.message_provider');
            /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
            $message = $messageProvider->newMessage('emprunteur-dossier-rejete', ['firstName' => $company->getIdClientOwner()->getPrenom()]);

            try {
                $message->setTo($company->getIdClientOwner()->getEmail());
                $mailer = $this->get('mailer');
                $mailer->send($message);
            } catch (\Exception $exception) {
                $this->get('logger')->warning('Could not send email: "emprunteur-dossier-rejete" on project: ' . $project->getIdProject() . ' - Exception: ' . $exception->getMessage(), [
                    'id_client'        => $company->getIdClientOwner()->getIdClient(),
                    'id_mail_template' => $message->getTemplateId(),
                    'class'            => __CLASS__,
                    'function'         => __FUNCTION__,
                    'file'             => $exception->getFile(),
                    'line'             => $exception->getLine(),
                ]);
            }
        }
    }

    public function _activite()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        $projectRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects');

        try {
            // nombre de projets en cours de traitement commercial
            $projectsInSalesTreatmentStatusCount = count($projectRepository->findBy(['status' => ProjectsStatus::COMMERCIAL_REVIEW]));

            // émissions yoy
            $firstDayOfThisYear = new DateTime('first day of this year');
            $today              = new DateTime();
            $firstOfLastYear    = new DateTime('first day of last year');
            $sameDayOfLastYear  = new DateTime('1 year ago');
            $yearOverYear       = $this->getReleaseProjectAndDelta($firstDayOfThisYear, $today, $firstOfLastYear, $sameDayOfLastYear);

            // émissions mom
            $firstDayOfLastMonth   = new DateTime('first day of last month');
            $lastDayOfLastMonth    = new DateTime('last day of last month');
            $firstDayOfTwoMonthAgo = new DateTime('first day of 2 month ago');
            $lastDayOfTwoMonthAgo  = new DateTime('last day of 2 month ago');
            $monthOverMonth        = $this->getReleaseProjectAndDelta($firstDayOfLastMonth, $lastDayOfLastMonth, $firstDayOfTwoMonthAgo, $lastDayOfTwoMonthAgo);

            // projets par canal d’arrivée
            $twelveMonthAgo     = new DateTime('first day of 11 months ago');
            $twelveMonths       = $this->getRollingMonths($twelveMonthAgo, $today);
            $statSentToAnalysis = $this->getProjectCountInStatus(ProjectsStatus::PENDING_ANALYSIS, $twelveMonthAgo, $today);
            $statRepayment      = $this->getProjectCountInStatus(ProjectsStatus::REMBOURSEMENT, $twelveMonthAgo, $today);

            // projets en cours
            $countableStatus        = $entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatus')->findBy([
                'status' => [
                    ProjectsStatus::COMMERCIAL_REVIEW,
                    ProjectsStatus::ANALYSIS_REVIEW,
                    ProjectsStatus::PREP_FUNDING,
                    ProjectsStatus::EN_FUNDING,
                    ProjectsStatus::FUNDE
                ]
            ], ['status' => 'ASC']);
            $statusAllCount         = $this->countByStatus($countableStatus);
            $statusCashFlowCount    = $this->countByStatus($countableStatus, [BorrowingMotive::ID_MOTIVE_CASH_FLOW]);
            $statusAcquisitionCount = $this->countByStatus($countableStatus, [BorrowingMotive::ID_MOTIVE_ACQUISITION_MERGER]);
            $statusPartnerCount     = $this->countByStatus($countableStatus, null, [
                Partner::PARTNER_U_CAR_ID,
                Partner::PARTNER_MEDILEND_ID,
                Partner::PARTNER_AXA_ID,
                Partner::PARTNER_MAPA_ID,
                Partner::PARTNER_UNILEND_PARTNERS_ID
            ]);

            // émissions n vs n-1 (12 mois glissants)
            $twentyFourMonthsAgo             = new DateTime('first day of 23 months ago');
            $twelveMonthsLastYear            = $this->getRollingMonths($twentyFourMonthsAgo, $sameDayOfLastYear);
            $releasedProjectsThisRollingYear = $projectRepository->getStatisticsByStatusByMonth(ProjectsStatus::REMBOURSEMENT, false, $twelveMonthAgo, $today);
            $releasedProjectsLastRollingYear = $projectRepository->getStatisticsByStatusByMonth(ProjectsStatus::REMBOURSEMENT, false, $twentyFourMonthsAgo, $sameDayOfLastYear);

            $borrowingMotives = [
                BorrowingMotive::ID_MOTIVE_PURCHASE_MATERIAL,
                BorrowingMotive::ID_MOTIVE_DEVELOPMENT,
                BorrowingMotive::ID_MOTIVE_REAL_ESTATE,
                BorrowingMotive::ID_MOTIVE_WORK,
                BorrowingMotive::ID_MOTIVE_CASH_FLOW,
                BorrowingMotive::ID_MOTIVE_OTHER
            ];

            $readyFundingDelay      = $this->getDelayByStatus(ProjectsStatus::SUSPENSIVE_CONDITIONS, $borrowingMotives);
            $suspenseConditionDelay = $this->getDelayByStatus(ProjectsStatus::PREP_FUNDING, $borrowingMotives);
            $beforeFundingDelay     = [];

            foreach ($borrowingMotives as $motive) {
                $beforeFundingDelay[$motive] = (isset($readyFundingDelay[$motive]) ? $readyFundingDelay[$motive] : 0) + (isset($suspenseConditionDelay[$motive]) ? $suspenseConditionDelay[$motive] : 0);
            }

            $delays = [
                ['label' => 'Fundé', 'data' => $this->getDelayByStatus(ProjectsStatus::FUNDE, $borrowingMotives)],
                ['label' => 'En funding', 'data' => $this->getDelayByStatus(ProjectsStatus::EN_FUNDING, $borrowingMotives)],
                ['label' => 'Prép funding + conditions suspensives', 'data' => $beforeFundingDelay],
                ['label' => 'Reveue analyste', 'data' => $this->getDelayByStatus(ProjectsStatus::ANALYSIS_REVIEW, $borrowingMotives)],
                ['label' => 'Attent analyste', 'data' => $this->getDelayByStatus(ProjectsStatus::PENDING_ANALYSIS, $borrowingMotives)],
                ['label' => 'Traitement commercial', 'data' => $this->getDelayByStatus(ProjectsStatus::COMMERCIAL_REVIEW, $borrowingMotives)],
                ['label' => 'Demande complète', 'data' => $this->getDelayByStatus(ProjectsStatus::COMPLETE_REQUEST, $borrowingMotives)]
            ];
        } catch (Exception $exception) {
            /** @var \Psr\Log\LoggerInterface $logger */
            $logger = $this->get('logger');
            $logger->error('Error occurs when displaying the sales activity dashboard. Error : ' . $exception->getMessage(),
                ['file' => $exception->getFile(), 'line' => $exception->getLine(), 'method' => __METHOD__]);

            header('Location: ' . $this->url);
            die;
        }

        $this->render(null, [
            'projectsInSalesTreatmentStatusCount' => $projectsInSalesTreatmentStatusCount,
            'releasedProjectThisYearCount'        => $yearOverYear['number'],
            'releasedProjectThisYearAmount'       => $yearOverYear['amount'],
            'deltaYoyCountInPercentage'           => $yearOverYear['deltaCountInPercentage'],
            'deltaYoyAmountInPercentage'          => $yearOverYear['deltaAmountInPercentage'],
            'releasedProjectLastMonthCount'       => $monthOverMonth['number'],
            'releasedProjectLastMonthAmount'      => $monthOverMonth['amount'],
            'deltaMomCountInPercentage'           => $monthOverMonth['deltaCountInPercentage'],
            'deltaMomAmountInPercentage'          => $monthOverMonth['deltaAmountInPercentage'],
            'twelveMonths'                        => $twelveMonths,
            'statSentToAnalysisHighcharts'        => $statSentToAnalysis,
            'statRepaymentHighcharts'             => $statRepayment,
            'twelveMonthsLastYear'                => $twelveMonthsLastYear,
            'releasedProjectsThisYear'            => $releasedProjectsThisRollingYear,
            'releasedProjectsLastYear'            => $releasedProjectsLastRollingYear,
            'delays'                              => $delays,
            'borrowingMotives'                    => $borrowingMotives,
            'countableStatus'                     => $countableStatus,
            'statusAllCount'                      => $statusAllCount,
            'statusCashFlowCount'                 => $statusCashFlowCount,
            'statusAcquisitionCount'              => $statusAcquisitionCount,
            'statusPartnerCount'                  => $statusPartnerCount,
            'dateLastMonth'                       => $lastDayOfLastMonth,
            'dateTwoMonthsAgo'                    => $lastDayOfTwoMonthAgo
        ]);
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

        $releasedProject       = $projectRepository->findProjectsHavingHadStatusBetweenDates(ProjectsStatus::REMBOURSEMENT, $from, $end);
        $releasedProjectCount  = count($releasedProject);
        $releasedProjectAmount = 0;
        foreach ($releasedProject as $project) {
            $releasedProjectAmount += $project['amount'];
        }

        $releasedProjectToCompare       = $projectRepository->findProjectsHavingHadStatusBetweenDates(ProjectsStatus::REMBOURSEMENT, $compareWithFrom, $compareWithEnd);
        $releasedProjectToCompareCount  = count($releasedProjectToCompare);
        $releasedProjectToCompareAmount = 0;
        foreach ($releasedProjectToCompare as $project) {
            $releasedProjectToCompareAmount += $project['amount'];
        }

        $deltaCountInPercentage  = $releasedProjectToCompareCount ? round(bcmul(bcdiv(bcsub($releasedProjectCount, $releasedProjectToCompareCount, 4), $releasedProjectToCompareCount, 5), 100, 2), 1) : 0;
        $deltaAmountInPercentage = $releasedProjectToCompareAmount ? round(bcmul(bcdiv(bcsub($releasedProjectAmount, $releasedProjectToCompareAmount, 4), $releasedProjectToCompareAmount, 5), 100, 2), 1) : 0;

        return [
            'number'                  => $releasedProjectCount,
            'amount'                  => $releasedProjectAmount,
            'deltaCountInPercentage'  => $deltaCountInPercentage,
            'deltaAmountInPercentage' => $deltaAmountInPercentage,
        ];
    }

    /**
     * @param DateTime $start
     * @param DateTime $end
     *
     * @return array
     */
    private function getRollingMonths(DateTime $start, DateTime $end) : array
    {
        $months     = [];
        $firstMonth = clone $start;
        $lastMonth  = clone $end;

        while ($firstMonth <= $lastMonth) {
            $months[] = $firstMonth->format('m/Y');
            $firstMonth->modify('+1 month');
        }

        return $months;
    }

    /**
     * @param int      $status
     * @param DateTime $start
     * @param DateTime $end
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getProjectCountInStatus(int $status, DateTime $start, DateTime $end) : array
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        $countInStatus = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->getStatisticsByStatusByMonth($status, true, $start, $end);

        $partnerColor = [
            Partner::PARTNER_U_CAR_ID            => '#FCA234',
            Partner::PARTNER_MEDILEND_ID         => '#15AAE8',
            Partner::PARTNER_AXA_ID              => '#133082',
            Partner::PARTNER_MAPA_ID             => '#EC3846',
            Partner::PARTNER_UNILEND_PARTNERS_ID => '#91ED81',
            Partner::PARTNER_UNILEND_ID          => '#B20066'
        ];

        $countInStatusHighcharts = [];
        foreach ($countInStatus as $item) {
            $countInStatusHighcharts[$item['partnerId']]['data'][$item['month']] = (int) $item['number'];
            $countInStatusHighcharts[$item['partnerId']]['label']                = $item['partner'];
            $countInStatusHighcharts[$item['partnerId']]['color']                = isset($partnerColor[$item['partnerId']]) ? $partnerColor[$item['partnerId']] : '';
        }
        ksort($countInStatusHighcharts);

        return $countInStatusHighcharts;
    }

    /**
     * @param  int   $status
     * @param  array $motives
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getDelayByStatus(int $status, array $motives) : array
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');
        $delays        = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->getDelayByStatus($status, $motives);

        $formattedDelays = [];
        foreach ($delays as $delay) {
            $formattedDelays[$delay['id_motive']] = round(bcdiv($delay['diff'], 1440, 3), 2);
        }

        return $formattedDelays;
    }

    /**
     * @param array      $countableStatus
     * @param array|null $borrowingMotives
     * @param array|null $partners
     *
     * @return array
     */
    private function countByStatus(array $countableStatus, ?array $borrowingMotives = null, ?array $partners = null) : array
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = $this->get('doctrine.orm.entity_manager');

        $status = [];
        foreach ($countableStatus as $item) {
            $status[] = $item->getStatus();
        }

        $statusCount = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->countByStatus($status, $borrowingMotives, $partners);

        $formattedStatusCount = [];
        foreach ($statusCount as $number) {
            $formattedStatusCount[$number['status']] = ['number' => $number['project_number']];
        }

        return $formattedStatusCount;
    }

    public function _projets_a_dechoir()
    {
        $this->hideDecoration();

        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\BackOfficeUserManager $userManager */
        $userManager = $this->get('unilend.service.back_office_user_manager');
        $error       = null;
        $data        = [];

        if ($userManager->isUserGroupRisk($this->userEntity) || $userManager->isUserGroupCompliance($this->userEntity)) {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager = $this->get('doctrine.orm.entity_manager');
            /** @var \Monolog\Logger $logger */
            $logger            = $this->get('logger');
            $projectRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects');
            try {
                $intervalSetting       = $entityManager->getRepository('UnilendCoreBusinessBundle:Settings')
                    ->findOneBy(['type' => 'Jours avant notification decheance du terme']);
                $projectsToDeclineSoon = $projectRepository->getProjectsWithUpcomingCloseOutNettingDate(null !== $intervalSetting ? $intervalSetting->getValue() : 15);

                foreach ($projectsToDeclineSoon as $projectRow) {
                    try {
                        $project = $projectRepository->find($projectRow['id_project']);
                        $data[]  = [
                            $project->getTitle(),
                            $project->getIdCompany()->getName(),
                            $projectRow['days_diff_con_limit_date'] === 0 ? 'Aujourd\'hui' : ($projectRow['days_diff_con_limit_date'] < 0 ? 'J' : 'J+') . $projectRow['days_diff_con_limit_date'],
                            (new \DateTime($projectRow['funding_date']))->format('d/m/Y'),
                            $projectRepository->getFundedProjectsBelongingToTheSameCompany($project->getIdProject(), $project->getIdCompany()->getSiren()),
                            $project->getIdProject()
                        ];
                    } catch (\Exception $exception) {
                        $logger->warning(
                            'Could not get the project details. Error: ' . $exception->getMessage(),
                            ['method' => __METHOD__, 'id_project' => $projectRow['id_project'], 'file' => $exception->getFile(), 'line' => $exception->getLine()]
                        );
                    }
                }
            } catch (\Exception $exception) {
                $error = 'Impossible de charger la liste des projets à déchoir';
                $logger->warning(
                    'Could not load the projects to decline. Error: ' . $exception->getMessage(),
                    ['method' => __METHOD__, 'file' => $exception->getFile(), 'line' => $exception->getLine()]
                );
            }
            echo json_encode(['data' => $data, 'error' => $error]);
            die;
        }
    }
}
