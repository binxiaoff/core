<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;

class projects_status extends projects_status_crud
{
    const IMPOSSIBLE_AUTO_EVALUATION = 1;
    const NOT_ELIGIBLE               = 2;
    const INCOMPLETE_REQUEST         = 5;
    const COMPLETE_REQUEST           = 10;
    const ABANDONED                  = 15;
    const POSTPONED                  = 19;
    const COMMERCIAL_REVIEW          = 20;
    const COMMERCIAL_REJECTION       = 25;
    const PENDING_ANALYSIS           = 30;
    const ANALYSIS_REVIEW            = 31;
    const ANALYSIS_REJECTION         = 32;
    const COMITY_REVIEW              = 33;
    const COMITY_REJECTION           = 34;
    const PREP_FUNDING               = 37;
    const A_FUNDER                   = 40;
    const AUTO_BID_PLACED            = 45;
    const EN_FUNDING                 = 50;
    const BID_TERMINATED             = 55;
    const FUNDE                      = 60;
    const FUNDING_KO                 = 70;
    const PRET_REFUSE                = 75;
    const REMBOURSEMENT              = 80;
    const REMBOURSE                  = 90;
    const REMBOURSEMENT_ANTICIPE     = 95;
    const PROBLEME                   = 100;
    const PROBLEME_J_X               = 110;
    const RECOUVREMENT               = 120;
    const PROCEDURE_SAUVEGARDE       = 130;
    const REDRESSEMENT_JUDICIAIRE    = 140;
    const LIQUIDATION_JUDICIAIRE     = 150;
    const DEFAUT                     = 160;

    /**
     * List of projects with pending repayments
     * @var array $runningRepayment
     */
    public static $runningRepayment = [
        ProjectsStatus::REMBOURSEMENT,
        ProjectsStatus::PROBLEME,
        ProjectsStatus::PROBLEME_J_X,
        ProjectsStatus::RECOUVREMENT,
        ProjectsStatus::PROCEDURE_SAUVEGARDE,
        ProjectsStatus::REDRESSEMENT_JUDICIAIRE,
        ProjectsStatus::LIQUIDATION_JUDICIAIRE
    ];

    /**
     * List of project status after repayment
     * @var array
     */
    public static $afterRepayment = [
        ProjectsStatus::REMBOURSEMENT,
        ProjectsStatus::REMBOURSE,
        ProjectsStatus::REMBOURSEMENT_ANTICIPE,
        ProjectsStatus::PROBLEME,
        ProjectsStatus::PROBLEME_J_X,
        ProjectsStatus::RECOUVREMENT,
        ProjectsStatus::PROCEDURE_SAUVEGARDE,
        ProjectsStatus::REDRESSEMENT_JUDICIAIRE,
        ProjectsStatus::LIQUIDATION_JUDICIAIRE,
        ProjectsStatus::DEFAUT
    ];

    /**
     * List of project status when project should be assigned to a commercial
     * @var array
     */
    public static $saleTeam = [
        ProjectsStatus::POSTPONED,
        ProjectsStatus::COMMERCIAL_REVIEW,
        ProjectsStatus::PENDING_ANALYSIS,
        ProjectsStatus::ANALYSIS_REVIEW,
        ProjectsStatus::COMITY_REVIEW,
        ProjectsStatus::SUSPENSIVE_CONDITIONS,
        ProjectsStatus::PREP_FUNDING,
        ProjectsStatus::A_FUNDER,
        ProjectsStatus::AUTO_BID_PLACED,
        ProjectsStatus::EN_FUNDING,
        ProjectsStatus::BID_TERMINATED,
        ProjectsStatus::FUNDE
    ];

    /**
     * List of project status when project is considered as part of the commercial team pipe
     * @var array
     */
    public static $upcomingSaleTeam = [
        ProjectsStatus::INCOMPLETE_REQUEST,
        ProjectsStatus::COMPLETE_REQUEST
    ];

    /**
     * List of project status when project is considered as part of the risk team pipe
     * @var array
     */
    public static $riskTeam = [
        ProjectsStatus::PENDING_ANALYSIS,
        ProjectsStatus::ANALYSIS_REVIEW,
        ProjectsStatus::COMITY_REVIEW,
        ProjectsStatus::SUSPENSIVE_CONDITIONS
    ];

    public function __construct($bdd, $params = '')
    {
        parent::projects_status($bdd, $params);
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

    public function getLastStatutByMonth($id_project, $month, $year)
    {
        $sql = '
            SELECT id_project_status
            FROM `projects_status_history`
            WHERE id_project = ' . $id_project . '
                AND MONTH(added) = ' . $month . '
                AND YEAR(added) = ' . $year . '
            ORDER BY projects_status_history.added DESC, id_project_status_history DESC
            LIMIT 1';

        $result            = $this->bdd->query($sql);
        $id_project_statut = (int)($this->bdd->result($result, 0, 0));

        return parent::get($id_project_statut, 'id_project_status');
    }
}
