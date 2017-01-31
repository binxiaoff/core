<?php
// **************************************************************************************************** //
// ***************************************    ASPARTAM    ********************************************* //
// **************************************************************************************************** //
//
// Copyright (c) 2008-2011, equinoa
// Permission is hereby granted, free of charge, to any person obtaining a copy of this software and
// associated documentation files (the "Software"), to deal in the Software without restriction,
// including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
// and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so,
// subject to the following conditions:
// The above copyright notice and this permission notice shall be included in all copies
// or substantial portions of the Software.
// The Software is provided "as is", without warranty of any kind, express or implied, including but
// not limited to the warranties of merchantability, fitness for a particular purpose and noninfringement.
// In no event shall the authors or copyright holders equinoa be liable for any claim,
// damages or other liability, whether in an action of contract, tort or otherwise, arising from,
// out of or in connection with the software or the use or other dealings in the Software.
// Except as contained in this notice, the name of equinoa shall not be used in advertising
// or otherwise to promote the sale, use or other dealings in this Software without
// prior written authorization from equinoa.
//
//  Version : 2.4.0
//  Date : 21/03/2011
//  Coupable : CM
//
// **************************************************************************************************** //

class projects_status extends projects_status_crud
{
    const DEMANDE_SIMULATEUR      = 4;
    const NOTE_EXTERNE_FAIBLE     = 5;
    const PAS_3_BILANS            = 6;
    const COMPLETUDE_ETAPE_2      = 7;
    const COMPLETUDE_ETAPE_3      = 8;
    const ABANDON                 = 9;
    const A_TRAITER               = 10;
    const EN_ATTENTE_PIECES       = 20;
    const ATTENTE_ANALYSTE        = 25;
    const REJETE                  = 30;
    const REVUE_ANALYSTE          = 31;
    const REJET_ANALYSTE          = 32;
    const COMITE                  = 33;
    const REJET_COMITE            = 34;
    const PREP_FUNDING            = 35;
    const A_FUNDER                = 40;
    const AUTO_BID_PLACED         = 45;
    const EN_FUNDING              = 50;
    const BID_TERMINATED          = 55;
    const FUNDE                   = 60;
    const FUNDING_KO              = 70;
    const PRET_REFUSE             = 75;
    const REMBOURSEMENT           = 80;
    const REMBOURSE               = 90;
    const REMBOURSEMENT_ANTICIPE  = 95;
    const PROBLEME                = 100;
    const PROBLEME_J_X            = 110;
    const RECOUVREMENT            = 120;
    const PROCEDURE_SAUVEGARDE    = 130;
    const REDRESSEMENT_JUDICIAIRE = 140;
    const LIQUIDATION_JUDICIAIRE  = 150;
    const DEFAUT                  = 160;

    const NON_ELIGIBLE_REASON_PRODUCT_NOT_FOUND               = 'product_not_found';
    const NON_ELIGIBLE_REASON_INACTIVE                        = 'entity_inactive';
    const NON_ELIGIBLE_REASON_UNKNOWN_SIREN                   = 'unknown_siren';
    const NON_ELIGIBLE_REASON_PROCEEDING                      = 'in_proceeding';
    const NON_ELIGIBLE_REASON_NEGATIVE_RAW_OPERATING_INCOMES  = 'negative_raw_operating_incomes';
    const NON_ELIGIBLE_REASON_NEGATIVE_CAPITAL_STOCK          = 'negative_capital_stock';
    const NON_ELIGIBLE_REASON_LOW_SCORE                       = 'low_score';
    const NON_ELIGIBLE_REASON_NEGATIVE_EQUITY_CAPITAL         = 'negative_equity_capital';
    const NON_ELIGIBLE_REASON_LOW_TURNOVER                    = 'low_turnover';
    const NON_ELIGIBLE_REASON_TOO_MUCH_PAYMENT_INCIDENT       = 'too_much_payment_incident';
    const NON_ELIGIBLE_REASON_NON_ALLOWED_PAYMENT_INCIDENT    = 'non_allowed_payment_incident';
    const NON_ELIGIBLE_REASON_NO_BALANCE_SHEET_FOUND          = 'no_balance_sheet_found';
    const NON_ELIGIBLE_REASON_UNILEND_XERFI_ELIMINATION_SCORE = 'unilend_xerfi_elimination_score';
    const NON_ELIGIBLE_REASON_UNILEND_XERFI_VS_ALTARES_SCORE  = 'unilend_xerfi_vs_altares_score';
    const NON_ELIGIBLE_REASON_LOW_ALTARES_SCORE               = 'low_altares_score';
    const NON_ELIGIBLE_REASON_LOW_INFOLEGALE_SCORE            = 'low_infolegale_score';
    const NON_ELIGIBLE_REASON_UNILEND_XERFI_VS_EULER_GRADE    = 'unilend_xerfi_vs_euler_grade';
    const NON_ELIGIBLE_REASON_EULER_GRADE_VS_ALTARES_SCORE    = 'euler_grade_vs_altares_score';
    const NON_ELIGIBLE_REASON_INFOGREFFE_PRIVILEGES           = 'has_infogreffe_privileges';

    /**
     * List of projects with pending repayments
     * @var array $runningRepayment
     */
    public static $runningRepayment = array(
        self::REMBOURSEMENT,
        self::PROBLEME,
        self::PROBLEME_J_X,
        self::RECOUVREMENT,
        self::PROCEDURE_SAUVEGARDE,
        self::REDRESSEMENT_JUDICIAIRE,
        self::LIQUIDATION_JUDICIAIRE
    );

    /**
     * List of project status after repayment
     * @var array
     */
    public static $afterRepayment = array(
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
    );

    /**
     * List of project status when project is considered as part of the commercial team pipe
     * @var array
     */
    public static $saleTeam = [
        self::A_TRAITER,
        self::EN_ATTENTE_PIECES,
        self::ATTENTE_ANALYSTE,
        self::REVUE_ANALYSTE,
        self::COMITE,
        self::PREP_FUNDING,
        self::A_FUNDER,
        self::AUTO_BID_PLACED,
        self::EN_FUNDING,
        self::BID_TERMINATED,
        self::FUNDE,
    ];

    /**
     * List of project status when project is considered as part of the risk team pipe
     * @var array
     */
    public static $riskTeam = [
        self::ATTENTE_ANALYSTE,
        self::REVUE_ANALYSTE,
        self::COMITE
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

        $sql = 'SELECT count(*) FROM `projects_status` ' . $where;

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

    public function getNextStatus($iStatus)
    {
        return (int) $this->bdd->result($this->bdd->query('SELECT status FROM projects_status WHERE status > ' . $iStatus . ' ORDER BY status ASC LIMIT 1'));
    }
}
