<?php

class thickboxController extends bootstrap
{
    public function __construct($command, $config, $app)
    {
        parent::__construct($command, $config, $app);

        $this->catchAll = true;

        // On masque les Head, header et footer originaux plus le debug
        $this->autoFireHeader = false;
        $this->autoFireHead   = false;
        $this->autoFireFooter = false;
        $this->autoFireDebug  = false;

        // On place le redirect sur la home
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
}
