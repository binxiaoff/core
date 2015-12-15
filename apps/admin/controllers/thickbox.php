<?php

class thickboxController extends bootstrap
{
    public function __construct($command, $config, $app)
    {
        parent::__construct($command, $config, $app);

        $this->catchAll = true;

        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        $_SESSION['request_url'] = $this->url;
    }

    public function _loginError()
    {

    }

    public function _loginInterdit()
    {

    }

    public function _newPassword()
    {

    }

    public function _pop_up_edit_date_retrait()
    {
        $this->projects = $this->loadData('projects');
        $this->projects->get($this->params[0], 'id_project');

        $this->time_retrait = strtotime($this->projects->date_retrait);

        $date               = explode('-', $this->projects->date_retrait);
        $this->date_retrait = $date[2] . '/' . $date[1] . '/' . $date[0];

        $date      = explode(' ', $this->projects->date_retrait_full);
        $heure_min = explode(':', $date[1]);

        $this->heure_date_retrait  = $heure_min[0];
        $this->minute_date_retrait = $heure_min[1];

    }

    public function _popup_confirmation_send_email()
    {

    }

    public function _project_history()
    {
        $oProjects = $this->loadData('projects');

        $this->aHistory = array();

        if (isset($this->params[0]) && $oProjects->get($this->params[0])) {
            $oProjectsStatusHistory = $this->loadData('projects_status_history');
            $aProjectHistory        = $oProjectsStatusHistory->select('id_project = ' . $oProjects->id_project, 'id_project_status_history ASC');

            if (false === empty($aProjectHistory)) {
                $oProjectsStatus               = $this->loadData('projects_status');
                $oProjectsStatusHistoryDetails = $this->loadData('projects_status_history_details');
                $oUsers                        = $this->loadData('users');

                $this->aProjectHistoryDetails = $oProjectsStatusHistoryDetails->select(
                    'id_project_status_history IN (' . implode(', ', array_column($aProjectHistory, 'id_project_status_history')) . ')',
                    'id_project_status_history ASC',
                    '',
                    '',
                    'id_project_status_history'
                );

                foreach ($aProjectHistory as $aHistory) {
                    $oProjectsStatus->get($aHistory['id_project_status']);
                    $this->aHistory[] = array(
                        'status'           => $oProjectsStatus->label,
                        'date'             => $aHistory['added'],
                        'user'             => $oUsers->getName($aHistory['id_user']),
                        'decision_date'    => empty($this->aProjectHistoryDetails[$aHistory['id_project_status_history']]) ? '' : $this->aProjectHistoryDetails[$aHistory['id_project_status_history']]['date'],
                        'receiver'         => empty($this->aProjectHistoryDetails[$aHistory['id_project_status_history']]) ? '' : $this->aProjectHistoryDetails[$aHistory['id_project_status_history']]['receiver'],
                        'mail_content'     => empty($this->aProjectHistoryDetails[$aHistory['id_project_status_history']]) ? '' : $this->aProjectHistoryDetails[$aHistory['id_project_status_history']]['mail_content'],
                        'site_content'     => empty($this->aProjectHistoryDetails[$aHistory['id_project_status_history']]) ? '' : $this->aProjectHistoryDetails[$aHistory['id_project_status_history']]['site_content'],
                    );
                }
            }
        }
    }

    public function _project_status_problem()
    {
        $this->iProjectId = $this->params[0];
    }

    public function _project_status_recovery()
    {
        $this->iProjectId = $this->params[0];
    }

    public function _project_status_failure()
    {
        $this->iProjectId = $this->params[0];
    }

    public function _project_status_problem_x()
    {
        $this->iProjectId = $this->params[0];
    }

    public function _project_status_precautionary_process()
    {
        $this->iProjectId = $this->params[0];
    }

    public function _project_status_receivership()
    {
        $this->iProjectId = $this->params[0];
    }

    public function _project_status_compulsory_liquidation()
    {
        $this->iProjectId = $this->params[0];
    }
}
