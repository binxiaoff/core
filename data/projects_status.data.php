<?php

class projects_status extends projects_status_crud
{
    const IMPOSSIBLE_AUTO_EVALUATION = 1;
    const NOT_ELIGIBLE               = 2;
    const SIMULATION                 = 3;
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
    const PREP_FUNDING               = 35;
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

    const NON_ELIGIBLE_REASON_PRODUCT_NOT_FOUND                    = 'product_not_found';
    const NON_ELIGIBLE_REASON_INACTIVE                             = 'entity_inactive';
    const NON_ELIGIBLE_REASON_UNKNOWN_SIREN                        = 'unknown_siren';
    const NON_ELIGIBLE_REASON_PROCEEDING                           = 'in_proceeding';
    const NON_ELIGIBLE_REASON_NEGATIVE_RAW_OPERATING_INCOMES       = 'negative_raw_operating_incomes';
    const NON_ELIGIBLE_REASON_NEGATIVE_CAPITAL_STOCK               = 'negative_capital_stock';
    const NON_ELIGIBLE_REASON_NEGATIVE_EQUITY_CAPITAL              = 'negative_equity_capital';
    const NON_ELIGIBLE_REASON_LOW_TURNOVER                         = 'low_turnover';
    const NON_ELIGIBLE_REASON_TOO_MUCH_PAYMENT_INCIDENT            = 'too_much_payment_incident';
    const NON_ELIGIBLE_REASON_NON_ALLOWED_PAYMENT_INCIDENT         = 'non_allowed_payment_incident';
    const NON_ELIGIBLE_REASON_UNILEND_XERFI_ELIMINATION_SCORE      = 'unilend_xerfi_elimination_score';
    const NON_ELIGIBLE_REASON_UNILEND_XERFI_VS_ALTARES_SCORE       = 'unilend_xerfi_vs_altares_score';
    const NON_ELIGIBLE_REASON_LOW_ALTARES_SCORE                    = 'low_altares_score';
    const NON_ELIGIBLE_REASON_LOW_INFOLEGALE_SCORE                 = 'low_infolegale_score';
    const NON_ELIGIBLE_REASON_EULER_TRAFFIC_LIGHT                  = 'euler_traffic_light';
    const NON_ELIGIBLE_REASON_EULER_TRAFFIC_LIGHT_VS_ALTARES_SCORE = 'euler_traffic_light_vs_altares_score';
    const NON_ELIGIBLE_REASON_EULER_TRAFFIC_LIGHT_VS_UNILEND_XERFI = 'euler_traffic_light_vs_unilend_xerfi';
    const NON_ELIGIBLE_REASON_EULER_GRADE_VS_UNILEND_XERFI         = 'euler_grade_vs_unilend_xerfi';
    const NON_ELIGIBLE_REASON_EULER_GRADE_VS_ALTARES_SCORE         = 'euler_grade_vs_altares_score';
    const NON_ELIGIBLE_REASON_INFOGREFFE_PRIVILEGES                = 'has_infogreffe_privileges';
    const UNEXPECTED_RESPONSE                                      = 'unexpected_response_from_';

    /**
     * List of projects with pending repayments
     * @var array $runningRepayment
     */
    public static $runningRepayment = [
        self::REMBOURSEMENT,
        self::PROBLEME,
        self::PROBLEME_J_X,
        self::RECOUVREMENT,
        self::PROCEDURE_SAUVEGARDE,
        self::REDRESSEMENT_JUDICIAIRE,
        self::LIQUIDATION_JUDICIAIRE
    ];

    /**
     * List of project status after repayment
     * @var array
     */
    public static $afterRepayment = [
        self::REMBOURSEMENT,
        self::REMBOURSE,
        self::REMBOURSEMENT_ANTICIPE,
        self::PROBLEME,
        self::PROBLEME_J_X,
        self::RECOUVREMENT,
        self::PROCEDURE_SAUVEGARDE,
        self::REDRESSEMENT_JUDICIAIRE,
        self::LIQUIDATION_JUDICIAIRE,
        self::DEFAUT
    ];

    /**
     * List of project status when project should be assigned to a commercial
     * @var array
     */
    public static $saleTeam = [
        self::POSTPONED,
        self::COMMERCIAL_REVIEW,
        self::PENDING_ANALYSIS,
        self::ANALYSIS_REVIEW,
        self::COMITY_REVIEW,
        self::PREP_FUNDING,
        self::A_FUNDER,
        self::AUTO_BID_PLACED,
        self::EN_FUNDING,
        self::BID_TERMINATED,
        self::FUNDE
    ];

    /**
     * List of project status when project is considered as part of the commercial team pipe
     * @var array
     */
    public static $upcomingSaleTeam = [
        self::INCOMPLETE_REQUEST,
        self::COMPLETE_REQUEST
    ];

    /**
     * List of project status when project is considered as part of the risk team pipe
     * @var array
     */
    public static $riskTeam = [
        self::PENDING_ANALYSIS,
        self::ANALYSIS_REVIEW,
        self::COMITY_REVIEW
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
