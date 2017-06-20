<?php

use \Unilend\Bundle\CoreBusinessBundle\Service\ProjectManager;
use \Unilend\Bundle\CoreBusinessBundle\Service\ProjectRequestManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;


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

        $this->users->checkAccess('dashboard');

        $this->catchAll   = true;
        $this->menu_admin = 'dashboard';
    }

    public function _default()
    {
        /** @var \users $user */
        $user = $this->loadData('users');
        $user->get($_SESSION['user']['id_user']);

        if (
            \users_types::TYPE_RISK == $user->id_user_type
            || $user->id_user == 28
            || isset($this->params[0]) && 'risk' == $this->params[0] && in_array($user->id_user_type, [\users_types::TYPE_ADMIN, \users_types::TYPE_IT])
        ) { // Risk team or Alain
            $this->template     = 'risk';
            $this->userProjects = $this->getRiskUserProjects($user);
            $this->teamProjects = $this->getRiskTeamProjects($user);
        } elseif (
            \users_types::TYPE_COMMERCIAL == $user->id_user_type
            || $user->id_user == 23
            || isset($this->params[0]) && 'sales' == $this->params[0] && in_array($user->id_user_type, [\users_types::TYPE_ADMIN, \users_types::TYPE_IT])
        ) { // Sales team or Arnaud
            $this->template                     = 'sale';
            $this->userProjects                 = $this->getSaleUserProjects($user);
            $this->teamProjects                 = $this->getSaleTeamProjects($user);
            $this->upcomingProjects             = $this->getSaleUpcomingProjects();
            $this->impossibleEvaluationProjects = $this->getImpossibleEvaluationProjects();
            $this->collapsedStatus              = self::$saleCollapsedStatus;
            $this->salesPeople                  = $user->select('status = 1 AND id_user_type = ' . \users_types::TYPE_COMMERCIAL, 'firstname ASC, name ASC');
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

        return array_map(function($project) {
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
        /** @var \clients $client */
        $client = $this->loadData('clients');
        /** @var ProjectManager $projectManager */
        $projectManager = $this->get('unilend.service.project_manager');
        /** @var ProjectRequestManager $projectRequestManager */
        $projectRequestManager = $this->get('unilend.service.project_request_manager');

        $projects = $entityManager->getRepository('UnilendCoreBusinessBundle:Projects')->findBy(['status' => ProjectsStatus::IMPOSSIBLE_AUTO_EVALUATION]);
        foreach ($projects as $projectEntity) {
            $project->get($projectEntity->getIdProject());

            if (null === $projectRequestManager->checkProjectRisk($project, $_SESSION['user']['id_user'])) {
                $client->get($projectEntity->getIdCompany()->getIdClientOwner());
                $status = empty($client->telephone) ? ProjectsStatus::INCOMPLETE_REQUEST : ProjectsStatus::COMPLETE_REQUEST;
                $projectManager->addProjectStatus($_SESSION['user']['id_user'], $status, $project);
                $projectRequestManager->assignEligiblePartnerProduct($project, $_SESSION['user']['id_user'], true);
            }
        }

        header('Location: ' . $this->lurl . '/dashboard');
        die;
    }
}
