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

class lenders_accounts extends lenders_accounts_crud
{

    public function lenders_accounts($bdd, $params = '')
    {
        parent::lenders_accounts($bdd, $params);
    }

    public function get($id, $field = 'id_lender_account')
    {
        return parent::get($id, $field);
    }

    public function update($cs = '')
    {
        parent::update($cs);
    }

    public function delete($id, $field = 'id_lender_account')
    {
        parent::delete($id, $field);
    }

    public function create($cs = '')
    {
        $id = parent::create($cs);
        return $id;
    }

    public function select($where = '', $order = '', $start = '', $nb = '')
    {
        if ($where != '') {
            $where = ' WHERE ' . $where;
        }
        if ($order != '') {
            $order = ' ORDER BY ' . $order;
        }
        $sql = 'SELECT * FROM `lenders_accounts`' . $where . $order . ($nb != '' && $start != '' ? ' LIMIT ' . $start . ',' . $nb : ($nb != '' ? ' LIMIT ' . $nb : ''));

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

        $sql = 'SELECT count(*) FROM `lenders_accounts` ' . $where;

        $result = $this->bdd->query($sql);
        return (int)($this->bdd->result($result, 0, 0));
    }

    public function exist($id, $field = 'id_lender_account')
    {
        $sql    = 'SELECT * FROM `lenders_accounts` WHERE ' . $field . '="' . $id . '"';
        $result = $this->bdd->query($sql);
        return ($this->bdd->fetch_array($result, 0, 0) > 0);
    }

    /**
     * @param $oProjectStatus object needed for constants
     * @param null $iLender int unique identifier of lender whose values are needed
     * @return array with dates and values of loans and returns
     */
    private function getValuesforTRI($oProjectStatus, $iLender = null)
    {
        if ($iLender === null) {
            if ($this->id_lender_account != null) {
                $iLender = $this->id_lender_account;
            } else {
                return false;
            }
        }

        $aValuesTRI = array();
        //get loans values as negativ , dates and project status
        $sSql = 'SELECT (l.amount *-1) as loan, psh.added as date
                FROM loans l
                INNER JOIN projects_status_history psh USING(id_project)
                INNER JOIN projects_status ps using(id_project_status)
                where ps.status = ' . projects_status::REMBOURSEMENT . '
                AND l.id_lender = ' . $iLender . '
                GROUP BY l.id_project,l.id_loan';

        $result = $this->bdd->query($sSql);
        while ($record = $this->bdd->fetch_array($result)) {
            $aValuesTRI[] = array($record["date"] => $record["loan"]);

        }
        //get echeancier values
        $sSql = 'SELECT
						e.montant as montant,
						e.date_echeance_reel as date_echeance_reel,
						e.date_echeance as date_echeance,
						e.status as echeance_status,
							(
							SELECT ps.status
							FROM projects_status ps
									LEFT JOIN projects_status_history psh ON (
									ps.id_project_status = psh.id_project_status)
									WHERE psh.id_project = p.id_project
									ORDER BY psh.added DESC LIMIT 1) as project_status
						FROM echeanciers e
							LEFT JOIN projects p ON e.id_project = p.id_project
							INNER JOIN loans l ON e.id_loan = l.id_loan
						WHERE e.id_lender = ' . $iLender . ';';

        $result = $this->bdd->query($sSql);

        $aStatusKo = array(projects_status::PROBLEME, projects_status::RECOUVREMENT, projects_status::PROBLEME_J_PLUS_X);
        while ($record = $this->bdd->fetch_array($result)) {
            if (in_array($record["project_status"], $aStatusKo) && 0 === (int)$record["echeance_status"]) {
                $record["montant"] = 0;
            }

            if ($record["date_echeance_reel"] == "0000-00-00 00:00:00") {
                $record["date_echeance_reel"] = $record["date_echeance"];
            }

            if (array_key_exists($record["date_echeance_reel"], $aValuesTRI)) {
                $aValuesTRI[] += array($record["date_echeance_reel"] => $record["montant"]);
            } else {
                $aValuesTRI[] = array($record["date_echeance_reel"] => $record["montant"]);
            }
        }

        return $aValuesTRI;
    }

    /**
     * Function that calculates the Internal Rate of Return for a lender portfolio
     * @param $oProjectStatus Object projects_status, needed for getValuesforTRI function
     * @param null $iLender int unique id of the lender for which the TRI should be calculated
     * @return bool|int|string with value as %
     */
    public function calculTRI($oProjectStatus, $iLender = null)
    {
        if ($iLender === null) {
            if ($this->id_lender_account != null) {
                $iLender = $this->id_lender_account;
            } else {
                return false;
            }
        }

        $aValuesTRI = $this->getValuesforTRI($oProjectStatus, $iLender);

        if (empty($aValuesTRI)) {
            return 0;
        }

        foreach ($aValuesTRI as $aValues) {

            foreach ($aValues as $date => $value) {
                $aDates[] = $date;
                $aSums[]  = $value;
            }
        }
        $oFinancial = new \PHPExcel_Calculation_Financial();
        $fXIRR      = round($oFinancial->XIRR($aSums, $aDates) * 100, 2);

        if ($fXIRR >= -100 && $fXIRR <= 100) {
            $sXIRR = (string)$fXIRR;

        } else {
            $sXIRR = 'non calculable';
        }
        return $sXIRR;
    }

    /**
     * @param $iLender unique id of the lender whose attachements are needed
     * @return array with all attachements
     */
    public function getAttachments($iLender)
    {

        $sql = 'SELECT a.id, a.id_type, a.id_owner, a.type_owner, a.path, a.added, a.updated, a.archived
				FROM attachment a
				WHERE a.id_owner = ' . $iLender . '
					AND a.type_owner = "lenders_accounts";';

        $result      = $this->bdd->query($sql);
        $attachments = array();
        while ($record = $this->bdd->fetch_array($result)) {

            $attachments[$record["id_type"]] = $record;
        }
        return $attachments;

    }

    /**
     * Function to select lenders for TRI cron
     * @param $iLimit number of accounts that need to be selected
     * @return array with lenders
     */
    public function selectLendersForTRI($iLimit)
    {

        $sSql = 'SELECT
                    la.id_lender_account,
                    la.added,
                    (
                        SELECT
                            las.tri_date
                        FROM
                            lenders_account_stats
                        ORDER BY
                            las.tri_date DESC
                        LIMIT
                            1
                    ) as tri_date
                FROM
                    lenders_accounts la
                    LEFT JOIN lenders_account_stats las ON la.id_lender_account = las.id_lender
                    LEFT JOIN clients c ON la.id_client_owner = c.id_client
                WHERE
                    c.status = 1
                    AND EXISTS(
                        SELECT
                            NULL
                        FROM
                            bids b
                        WHERE
                            b.id_lender_account = la.id_lender_account
                    )
                ORDER BY
                    las.tri_date ASC,
                    la.added DESC
                LIMIT ' . $iLimit . ';';

        $result   = $this->bdd->query($sSql);
        $aLenders = array();
        while ($record = $this->bdd->fetch_array($result)) {
            $aLenders[] = $record;
        }
        return $aLenders;
    }
}
