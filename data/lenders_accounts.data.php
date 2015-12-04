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

use Unilend\librairies\ULogger;

class lenders_accounts extends lenders_accounts_crud
{

    public function __construct($bdd, $params = '')
    {
        parent::lenders_accounts($bdd, $params);
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
     * @param int|null $iLendersAccountId unique identifier of the lender
     * @return array with dates and values of loans and dues
     * @throws Exception when there is no id_lender_account
     */
    private function getValuesForIRR($iLendersAccountId = null)
    {
        if ($iLendersAccountId === null) {
            if ($this->id_lender_account != null) {
                $iLendersAccountId = $this->id_lender_account;
            } else {
                throw new Exception('No id_lender_account');
            }
        }

        $aValuesIRR = array();
        //get loans values as negativ , dates and project status
        $sql = 'SELECT -l.amount AS loan, psh.added AS date
                FROM loans l
                INNER JOIN projects_status_history psh ON l.id_project = psh.id_project
                INNER JOIN projects_status ps ON psh.id_project_status = ps.id_project_status
                WHERE ps.status = ' . \projects_status::REMBOURSEMENT . '
                AND l.id_lender = ' . $iLendersAccountId . '
                GROUP BY l.id_project,l.id_loan';

        $result = $this->bdd->query($sql);
        while ($record = $this->bdd->fetch_array($result)) {
            $aValuesIRR[] = array($record["date"] => $record["loan"]);

        }
        //get echeancier values
        $sql = 'SELECT
                        e.montant AS montant,
                        e.date_echeance_reel AS date_echeance_reel,
                        e.date_echeance AS date_echeance,
                        e.status AS echeance_status,
                            (
                            SELECT ps.status
                            FROM projects_status ps
                                    LEFT JOIN projects_status_history psh ON (
                                    ps.id_project_status = psh.id_project_status)
                                    WHERE psh.id_project = p.id_project
                                    ORDER BY psh.added DESC LIMIT 1) AS project_status
                        FROM echeanciers e
                            LEFT JOIN projects p ON e.id_project = p.id_project
                            INNER JOIN loans l ON e.id_loan = l.id_loan
                        WHERE e.id_lender = ' . $iLendersAccountId;

        $result = $this->bdd->query($sql);

        $aStatusKo = array(\projects_status::PROBLEME, \projects_status::RECOUVREMENT);
        while ($record = $this->bdd->fetch_array($result)) {

            if ($record["project_status"] >= \projects_status::REMBOURSEMENT) {

                if (in_array($record["project_status"], $aStatusKo) && 0 == $record["echeance_status"]) {
                    $record["montant"] = 0;
                }

                if ($record["date_echeance_reel"] == "0000-00-00 00:00:00") {
                    $record["date_echeance_reel"] = $record["date_echeance"];
                }

                $aValuesIRR[] = array($record["date_echeance_reel"] => $record["montant"]);
            }
        }
        return $aValuesIRR;
    }

    /**
     * @param int|null $iLendersAccountId unique identifier of the lender for who the IRR should be calculated
     * @return float with IRR value
     * @throws Exception when there is no id_lender_account,
     * when not values are available to be used in the calculation,
     * when the result is not in the accepted range
     */
    public function calculateIRR($iLendersAccountId = null)
    {
        if ($iLendersAccountId === null) {
            if ($this->id_lender_account != null) {
                $iLendersAccountId = $this->id_lender_account;
            } else {
                throw new Exception('No id_lender_account');
            }
        }

        try {
            $aValuesIRR = $this->getValuesForIRR($iLendersAccountId);
        } catch (Exception $e){
            $oLoggerIRR    = new ULogger('Calculate IRR', $this->logPath, 'IRR.log');
            $oLoggerIRR->addRecord(ULogger::WARNING, 'Caught Exception: '.$e->getMessage(). ' '. $e->getTraceAsString());
        }

        foreach ($aValuesIRR as $aValues) {
            foreach ($aValues as $date => $value) {
                $aDates[] = $date;
                $aSums[]  = $value;
            }
        }

        $oFinancial = new \PHPExcel_Calculation_Financial();
        $fXIRR      = round($oFinancial->XIRR($aSums, $aDates) * 100, 2);

        if (abs($fXIRR) > 100) {
            throw new Exception('IRR not in range for '.$iLendersAccountId. ' IRR : '. $fXIRR);
        }
        return $fXIRR;
    }

    /**
     * @param int $iLendersAccountId unique identifier of the lender account
     * @return array of attachments
     */
    public function getAttachments($iLendersAccountId)
    {

        $sql = 'SELECT a.id, a.id_type, a.id_owner, a.type_owner, a.path, a.added, a.updated, a.archived
                FROM attachment a
                WHERE a.id_owner = ' . $iLendersAccountId . '
                AND a.type_owner = "lenders_accounts";';

        $result       = $this->bdd->query($sql);
        $aAttachments = array();
        while ($record = $this->bdd->fetch_array($result)) {
            $aAttachments[$record["id_type"]] = $record;
        }
        return $aAttachments;
    }

    /**
     * @param int $iLimit number of lender accounts to be selected
     * @return array with lenders
     */
    public function selectLendersForIRR($iLimit)
    {
        $sql = 'SELECT
                    b.id_lender_account,
                    la.added,
                    MAX(las.tri_date) AS last_tri_date
                FROM
                    lenders_accounts la
                    INNER JOIN clients c ON la.id_client_owner = c.id_client
                    INNER JOIN bids b ON b.id_lender_account = la.id_lender_account
                    LEFT JOIN lenders_account_stats las ON la.id_lender_account = las.id_lender_account
                WHERE
                    c.status = 1
                GROUP BY
                    b.id_lender_account
                ORDER BY
                    last_tri_date ASC,
                    la.added DESC
                LIMIT ' . $iLimit;
        $result   = $this->bdd->query($sql);
        $aLenders = array();
        while ($record = $this->bdd->fetch_array($result)) {
            $aLenders[] = $record;
        }
        return $aLenders;
    }

    public function getInfosben($oProjectsStatus, $iLimit = null, $iOffset = null)
    {
        $sOffset = '';
        if (null !== $iOffset) {
            $iOffset = $this->bdd->escape_string($iOffset);
            $sOffset = 'OFFSET ' . $iOffset;
        }

        $sLimit = '';
        if (null !== $iLimit) {
            $iLimit  = $this->bdd->escape_string($iLimit);
            $sLimit = 'LIMIT ' . $iLimit;
        }

        $sql = 'SELECT DISTINCT (c.id_client), c.prenom, c.nom
                FROM clients c
                INNER JOIN lenders_accounts la ON la.id_client_owner = c.id_client
                INNER JOIN loans l on l.id_lender = la.id_lender_account
                INNER JOIN projects p ON p.id_project = l.id_project
                INNER JOIN projects_last_status_history plsh ON p.id_project = plsh.id_project
                INNER JOIN projects_status_history psh USING (id_project_status_history)
                INNER JOIN projects_status ps USING (id_project_status)
                WHERE ps.status > '. projects_status::REMBOURSEMENT . ' ' . $sLimit. ' '. $sOffset;

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    public function getLendersToMatchCity($iLimit)
    {
        $iLimit  = $this->bdd->escape_string($iLimit);

        $sql = 'SELECT * FROM (
                  SELECT c.id_client, ca.id_adresse, c.prenom, c.nom, ca.cp_fiscal AS zip, ca.ville_fiscal AS city, ca.cp, ca.ville, 0 AS is_company
                  FROM clients_adresses ca
                  INNER JOIN clients c ON ca.id_client = c.id_client
                  INNER JOIN lenders_accounts la ON la.id_client_owner = ca.id_client
                  WHERE c.status = 1
                      AND (ca.id_pays_fiscal = 1 OR ca.id_pays_fiscal = 0)
                      AND la.id_company_owner = 0
                      AND (
                        NOT EXISTS (SELECT cp FROM villes v WHERE v.cp = ca.cp_fiscal)
                        OR (SELECT COUNT(*) FROM villes v WHERE v.cp = ca.cp_fiscal AND v.ville = ca.ville_fiscal) <> 1
                      )
                  LIMIT '. floor($iLimit / 2).'
                ) perso
                UNION
                SELECT * FROM (
                    SELECT c.id_client, ca.id_adresse, c.prenom, c.nom, co.zip, co.city, ca.cp, ca.ville, 1 AS is_company
                    FROM clients_adresses ca
                      INNER JOIN clients c ON ca.id_client = c.id_client
                      INNER JOIN lenders_accounts la ON la.id_client_owner = ca.id_client
                      INNER JOIN companies co ON co.id_client_owner = ca.id_client
                    WHERE c.status = 1
                    AND (ca.id_pays_fiscal = 1 OR ca.id_pays_fiscal = 0)
                    AND (
                      NOT EXISTS (SELECT cp FROM villes v WHERE v.cp = co.zip)
                      OR (SELECT COUNT(*) FROM villes v WHERE v.cp = co.zip AND v.ville = co.city) <> 1
                    )  LIMIT '. floor($iLimit / 2).'
                ) company';

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }

    public function getLendersToMatchBirthCity($iLimit = '', $iOffset = '')
    {
        $iOffset = $this->bdd->escape_string($iOffset);
        $iLimit  = $this->bdd->escape_string($iLimit);

        $sOffset = '';
        if ('' !== $iOffset) {
            $sOffset = 'OFFSET ' . $iOffset;
        }

        $sLimit = '';
        if ('' !== $iLimit) {
            $sLimit = 'LIMIT ' . $iLimit;
        }

        $sql = 'SELECT c.id_client, c.prenom, c.nom, c.ville_naissance
                FROM clients c
                INNER JOIN lenders_accounts la ON la.id_client_owner = c.id_client
                WHERE c.status = 1
                AND id_pays_naissance = 1
                AND c.insee_birth = ""
                ' . $sLimit. ' '. $sOffset;

        $resultat = $this->bdd->query($sql);
        $result   = array();
        while ($record = $this->bdd->fetch_array($resultat)) {
            $result[] = $record;
        }
        return $result;
    }
}
