<?php

class dashboardController extends bootstrap
{
    private static $saleCollapsedStatus = [
        \projects_status::ATTENTE_ANALYSTE,
        \projects_status::REVUE_ANALYSTE,
        \projects_status::COMITE,
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
                $this->template         = 'sale';
                $this->userProjects     = $this->getSaleUserProjects($user);
                $this->teamProjects     = $this->getSaleTeamProjects($user);
                $this->upcomingProjects = $this->getSaleUpcomingProjects();
                $this->collapsedStatus  = self::$saleCollapsedStatus;
                $this->salesPeople      = $user->select('status = 1 AND id_user_type = ' . \users_types::TYPE_COMMERCIAL, 'firstname ASC, name ASC');
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
}
