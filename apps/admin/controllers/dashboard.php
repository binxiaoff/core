<?php

class dashboardController extends bootstrap
{
    private static $saleCollapsedStatus = [
        \projects_status::PENDING_ANALYSIS,
        \projects_status::ANALYSIS_REVIEW,
        \projects_status::COMITY_REVIEW,
        \projects_status::A_FUNDER,
        \projects_status::AUTO_BID_PLACED,
        \projects_status::EN_FUNDING,
        \projects_status::BID_TERMINATED
    ];

    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess('dashboard');

        $this->catchAll   = true;
        $this->menu_admin = 'dashboard';
    }

    public function _default()
    {
        /** @var \users $user */
        $user = $this->loadData('users');
        $user->get($_SESSION['user']['id_user']);

        switch ($user->id_user_type) {
            case \users_types::TYPE_RISK:
                $this->template     = 'risk';
                $this->userProjects = $this->getRiskUserProjects($user);
                $this->teamProjects = $this->getRiskTeamProjects($user);
                break;
            case \users_types::TYPE_COMMERCIAL:
                $this->template                     = 'sale';
                $this->userProjects                 = $this->getSaleUserProjects($user);
                $this->teamProjects                 = $this->getSaleTeamProjects($user);
                $this->upcomingProjects             = $this->getSaleUpcomingProjects();
                $this->impossibleEvaluationProjects = $this->getImpossibleEvaluationProjects();
                $this->collapsedStatus              = self::$saleCollapsedStatus;
                $this->salesPeople                  = $user->select('status = 1 AND id_user_type = ' . \users_types::TYPE_COMMERCIAL, 'firstname ASC, name ASC');
                break;
            default:
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
     * @param users $user
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
     * @param users $user
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
     * @param users $user
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
     * @param users $user
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

        return array_map(function($project) {
            $project['creation'] = \DateTime::createFromFormat('Y-m-d H:i:s', $project['creation']);
            return $project;
        }, $projects);
    }

    /**
     * @param array $projects
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

            $formattedProjects[$project['status']]['count']++;
            $formattedProjects[$project['status']]['projects'][] = $project;
        }

        return $formattedProjects;
    }

    public function _evaluate_projects()
    {
        // @todo re-evaluate projects in status \projects_status::IMPOSSIBLE_AUTO_EVALUATION (DEV-1198)
        /** @var \projects $project */
        $project  = $this->loadData('projects');
        /** @var \companies $company */
        $company  = $this->loadData('companies');
        /** @var \Unilend\Bundle\CoreBusinessBundle\Service\ProjectRequestManager $projectRequestManager */
        $projectRequestManager = $this->get('unilend.service.project_request_manager');
        $projects = $project->getImpossibleEvaluationProjects();

        if (is_array($projects)) {
            foreach ($projects as $p) {
                $project->get($p['id_project']);
                $company->get($project->id_company);
                $projectRequestManager->checkProjectRisk($company, $project, $_SESSION['user']['id_user']);
            }
        }

        header('Location: ' . $this->lurl . '/dashboard');
        die;
    }
}
