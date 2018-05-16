<?php

class projects_status extends projects_status_crud
{
    const COMMERCIAL_REVIEW          = 20;
    const PENDING_ANALYSIS           = 30;
    const ANALYSIS_REVIEW            = 31;
    const COMITY_REVIEW              = 33;
    const PREP_FUNDING               = 37;
    const A_FUNDER                   = 40;
    const AUTO_BID_PLACED            = 45;
    const EN_FUNDING                 = 50;
    const FUNDE                      = 60;
    const FUNDING_KO                 = 70;
    const PRET_REFUSE                = 75;
    const REMBOURSEMENT              = 80;
    const REMBOURSE                  = 90;
    const REMBOURSEMENT_ANTICIPE     = 95;

    public function __construct($bdd, $params = '')
    {
        parent::__construct($bdd, $params);
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        $sql = 'SELECT * FROM `projects_status`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_assoc($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    public function counter($where = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        $sql = 'SELECT COUNT(*) FROM `projects_status` ' . $where;

        $result = $this->bdd->query($sql);
        return (int)($this->bdd->result($result, 0, 0));
    }

    public function exist($id, $field = 'id_project_status')
    {
        $result = $this->bdd->query('SELECT * FROM `projects_status` WHERE ' . $field . ' = "' . $id . '"');
        return ($this->bdd->fetch_array($result) > 0);
    }

    public function getIdStatus($status)
    {
        $result = $this->bdd->query('SELECT id_project_status FROM `projects_status` WHERE status = "' . $status . '"');
        return (int)($this->bdd->result($result, 0, 0));
    }

    public function getLabel($status)
    {
        $result = $this->bdd->query('SELECT label FROM `projects_status` WHERE status = "' . $status . '"');
        return ($this->bdd->result($result, 0, 0));
    }
}
