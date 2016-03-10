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
        while ($record = $this->bdd->fetch_array($resultat)) {
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
        return ($this->bdd->fetch_array($result, 0, 0) > 0);
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

    public function getLastStatut($id_project)
    {
        $result            = $this->bdd->query('SELECT id_project_status FROM projects_status_history WHERE id_project = ' . $id_project . ' ORDER BY id_project_status_history DESC LIMIT 1');
        $id_project_statut = (int) $this->bdd->result($result, 0, 0);

        return parent::get($id_project_statut, 'id_project_status');
    }

    public function getLastStatutByMonth($id_project, $month, $year)
    {
        $sql = 'SELECT id_project_status
				FROM `projects_status_history`
				WHERE id_project = ' . $id_project . '
				AND MONTH(added) = ' . $month . ' AND YEAR(added) = ' . $year . '
				ORDER BY added DESC
				LIMIT 1
				';

        $result            = $this->bdd->query($sql);
        $id_project_statut = (int)($this->bdd->result($result, 0, 0));

        return parent::get($id_project_statut, 'id_project_status');
    }

    public function getNextStatus($iStatus)
    {
        return (int) $this->bdd->result($this->bdd->query('SELECT status FROM projects_status WHERE status > ' . $iStatus . ' ORDER BY status ASC LIMIT 1'));
    }

    /**
     * @param int                     $iProjectId
     * @param projects_status_history $oProjectStatusHistory
     * @return array
     */
    public function getPossibleStatus($iProjectId, projects_status_history $oProjectStatusHistory)
    {
        switch ($this->status) {
            case self::ABANDON:
                return $this->select('id_project_status = ' . $oProjectStatusHistory->getBeforeLastStatus($iProjectId) . ' OR status = ' . $this->status);
            case self::A_TRAITER:
            case self::EN_ATTENTE_PIECES:
                $sPossibleStatus = 'status IN (' . self::ABANDON . ', ' . $this->status . ', ' . $this->getNextStatus($this->status) . ')';
                break;
            case self::ATTENTE_ANALYSTE:
                $sPossibleStatus = 'status IN (' . self::ABANDON . ', ' . self::ATTENTE_ANALYSTE . ')';
                break;
            case self::PREP_FUNDING:
                $sPossibleStatus = 'status IN (' . self::ABANDON . ',' . self::PREP_FUNDING . ',' . self::A_FUNDER . ')';
                break;
            case self::LIQUIDATION_JUDICIAIRE:
                $sPossibleStatus = 'status IN (' . self::LIQUIDATION_JUDICIAIRE . ',' . self::DEFAUT . ')';
                break;
            case self::REMBOURSEMENT_ANTICIPE:
            case self::DEFAUT:
                return array();
            default:
                if ($this->status < self::REMBOURSEMENT) {
                    return array();
                }
                $sPossibleStatus = 'status >= '. self::REMBOURSEMENT . ' AND status != ' . self::DEFAUT;
                break;
        }

        return $this->select($sPossibleStatus, 'status ASC');
    }

    public static function checkStatusPostRepayment($iStatus)
    {
        return $iStatus >= self::REMBOURSEMENT;
    }

    public static function checkStatusKo($iStatus){

        $aStatusKo = array(self::PROBLEME, self::RECOUVREMENT);
        return in_array($iStatus, $aStatusKo);
    }
}
